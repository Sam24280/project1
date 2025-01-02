<?php
require_once 'includes/functions.php';
require_once 'includes/header.php';

// Initialize variables
$from = isset($_GET['from']) ? sanitizeInput($_GET['from']) : '';
$to = isset($_GET['to']) ? sanitizeInput($_GET['to']) : '';
$date = isset($_GET['date']) ? sanitizeInput($_GET['date']) : '';

// Get all unique locations for dropdowns
try {
    $stmt = $conn->query("SELECT DISTINCT from_location FROM routes UNION SELECT DISTINCT to_location FROM routes ORDER BY from_location");
    $locations = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    $error = "Error fetching locations: " . $e->getMessage();
}

// Search schedules if form is submitted
$schedules = [];
if ($from && $to) {
    try {
        $query = "
    SELECT 
        s.schedule_id,
        r.from_location,
        r.to_location,
        b.bus_name,
        s.departure_time,
        s.fare,
        b.total_seats,
        (SELECT COALESCE(SUM(number_of_seats), 0) FROM bookings WHERE schedule_id = s.schedule_id) as booked_seats
    FROM schedules s
    JOIN routes r ON s.route_id = r.route_id
    JOIN buses b ON s.bus_id = b.bus_id
    WHERE r.from_location = :from 
    AND r.to_location = :to
    AND s.status = 'active'";
        
        if ($date) {
            $query .= " AND DATE(s.departure_time) = :date";
        }
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':from', $from);
        $stmt->bindParam(':to', $to);
        if ($date) {
            $stmt->bindParam(':date', $date);
        }
        $stmt->execute();
        $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $error = "Error searching schedules: " . $e->getMessage();
    }
}
?>

<div class="container mt-5">
    <!-- Search Form -->
    <div class="card mb-4">
        <div class="card-body">
            <h3 class="card-title mb-4">Search Bus Schedule</h3>
            <form method="GET" action="" id="searchForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="from" class="form-label">From</label>
                        <select class="form-select" name="from" id="from" required>
                            <option value="">Select Location</option>
                            <?php foreach($locations as $location): ?>
                                <option value="<?php echo htmlspecialchars($location); ?>" 
                                        <?php echo $from === $location ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($location); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="to" class="form-label">To</label>
                        <select class="form-select" name="to" id="to" required>
                            <option value="">Select Location</option>
                            <?php foreach($locations as $location): ?>
                                <option value="<?php echo htmlspecialchars($location); ?>"
                                        <?php echo $to === $location ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($location); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" name="date" id="date" 
                               value="<?php echo $date; ?>" min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Search Results -->
    <?php if ($from && $to): ?>
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Available Buses</h4>
                <?php if (empty($schedules)): ?>
                    <div class="alert alert-info">No schedules found for the selected route and date.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Bus Name</th>
                                    <th>Departure Time</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Available Seats</th>
                                    <th>Fare</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schedules as $schedule): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($schedule['bus_name']); ?></td>
                                        <td><?php echo date('Y-m-d h:i A', strtotime($schedule['departure_time'])); ?></td>
                                        <td><?php echo htmlspecialchars($schedule['from_location']); ?></td>
                                        <td><?php echo htmlspecialchars($schedule['to_location']); ?></td>
                                        <td><?php echo $schedule['total_seats'] - $schedule['booked_seats']; ?></td>
                                        <td>৳<?php echo number_format($schedule['fare'], 2); ?></td>
                                        <td>
                                            <?php if (isLoggedIn()): ?>
                                                <?php if ($schedule['total_seats'] > $schedule['booked_seats']): ?>
                                                    <a href="booking.php?schedule=<?php echo $schedule['schedule_id']; ?>" 
                                                       class="btn btn-primary btn-sm">Book Now</a>
                                                <?php else: ?>
                                                    <button class="btn btn-secondary btn-sm" disabled>Sold Out</button>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <a href="login.php" class="btn btn-info btn-sm">Login to Book</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Add custom scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set minimum date for the date input to today
    document.getElementById('date').min = new Date().toISOString().split('T')[0];
    
    // Prevent selecting same location for from and to
    document.getElementById('searchForm').addEventListener('submit', function(e) {
        const from = document.getElementById('from').value;
        const to = document.getElementById('to').value;
        
        if (from === to) {
            e.preventDefault();
            alert('Source and destination cannot be the same!');
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>