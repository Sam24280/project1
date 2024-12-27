
<?php
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdmin()) {
    redirect('../login.php');
}

require_once '../includes/header.php';

// Fetch statistics
try {
    // Total Users
    $stmt = $conn->query("SELECT COUNT(*) as total_users FROM users");
    $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

    // Total Bookings
    $stmt = $conn->query("SELECT COUNT(*) as total_bookings FROM bookings");
    $totalBookings = $stmt->fetch(PDO::FETCH_ASSOC)['total_bookings'];

    // Total Revenue
    $stmt = $conn->query("SELECT SUM(total_fare) as total_revenue FROM bookings WHERE booking_status = 'confirmed'");
    $totalRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0;

    // Today's Bookings
    $stmt = $conn->query("SELECT COUNT(*) as today_bookings FROM bookings WHERE DATE(created_at) = CURDATE()");
    $todayBookings = $stmt->fetch(PDO::FETCH_ASSOC)['today_bookings'];

    // Recent Bookings
    $stmt = $conn->query("SELECT b.*, u.username, r.from_location, r.to_location, s.departure_time 
                         FROM bookings b 
                         JOIN users u ON b.user_id = u.user_id 
                         JOIN schedules s ON b.schedule_id = s.schedule_id 
                         JOIN routes r ON s.route_id = r.route_id 
                         ORDER BY b.created_at DESC LIMIT 5");
    $recentBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!-- Admin Header -->
<div class="bg-primary text-white py-3">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h1><i class="fas fa-cog"></i> Admin Dashboard</h1>
            </div>
            <div class="col-md-6 text-end">
                <a href="<?php echo BASE_URL; ?>/logout.php" class="btn btn-light">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Admin Navigation -->
<section class="py-3 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <nav class="nav nav-pills">
                    <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    <a class="nav-link" href="manage_buses.php">Manage Buses</a>
                    <a class="nav-link" href="manage_routes.php">Manage Routes</a>
                    <a class="nav-link" href="manage_schedules.php">Manage Schedules</a>
                </nav>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Cards -->
<section class="py-4">
    <div class="container">
        <div class="row">
            <!-- Total Users Card -->
            <div class="col-md-3 mb-4">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">Total Users</h5>
                        <h2><?php echo number_format($totalUsers); ?></h2>
                        <i class="fas fa-users fa-2x position-absolute top-0 end-0 m-3 opacity-50"></i>
                    </div>
                </div>
            </div>

            <!-- Total Bookings Card -->
            <div class="col-md-3 mb-4">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">Total Bookings</h5>
                        <h2><?php echo number_format($totalBookings); ?></h2>
                        <i class="fas fa-ticket-alt fa-2x position-absolute top-0 end-0 m-3 opacity-50"></i>
                    </div>
                </div>
            </div>

            <!-- Total Revenue Card -->
            <div class="col-md-3 mb-4">
                <div class="card bg-warning text-dark h-100">
                    <div class="card-body">
                        <h5 class="card-title">Total Revenue</h5>
                        <h2>$<?php echo number_format($totalRevenue, 2); ?></h2>
                        <i class="fas fa-dollar-sign fa-2x position-absolute top-0 end-0 m-3 opacity-50"></i>
                    </div>
                </div>
            </div>

            <!-- Today's Bookings Card -->
            <div class="col-md-3 mb-4">
                <div class="card bg-info text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">Today's Bookings</h5>
                        <h2><?php echo number_format($todayBookings); ?></h2>
                        <i class="fas fa-calendar-day fa-2x position-absolute top-0 end-0 m-3 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Bookings Table -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Recent Bookings</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Booking ID</th>
                                        <th>User</th>
                                        <th>Route</th>
                                        <th>Time</th>
                                        <th>Seats</th>
                                        <th>Total Fare</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentBookings as $booking): ?>
                                    <tr>
                                        <td><?php echo $booking['booking_id']; ?></td>
                                        <td><?php echo htmlspecialchars($booking['username']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['from_location'] . ' to ' . $booking['to_location']); ?></td>
                                        <td><?php echo date('h:i A', strtotime($booking['departure_time'])); ?></td>
                                        <td><?php echo $booking['number_of_seats']; ?></td>
                                        <td>$<?php echo number_format($booking['total_fare'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $booking['booking_status'] == 'confirmed' ? 'success' : 
                                                ($booking['booking_status'] == 'pending' ? 'warning' : 'danger'); ?>">
                                                <?php echo ucfirst($booking['booking_status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>