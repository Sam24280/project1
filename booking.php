<?php
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Check if schedule_id is provided
if (!isset($_GET['schedule'])) {
    redirect('schedule.php');
}
//print_r($_SESSION);
//$test = $_SESSION['user_id'];
//print_r($test);
$schedule_id = sanitizeInput($_GET['schedule']);
$error = '';
$success = '';

try {
    // Get schedule details
    $stmt = $conn->prepare("
    SELECT 
        s.schedule_id,
        s.departure_time,
        s.fare,
        r.from_location,
        r.to_location,
        b.bus_name,
        b.total_seats,
        (SELECT COALESCE(SUM(number_of_seats), 0) FROM bookings WHERE schedule_id = s.schedule_id) as booked_seats
    FROM schedules s
    JOIN routes r ON s.route_id = r.route_id
    JOIN buses b ON s.bus_id = b.bus_id
    WHERE s.schedule_id = :schedule_id
");
    $stmt->bindParam(':schedule_id', $schedule_id);
    $stmt->execute();
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$schedule) {
        redirect('schedule.php');
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Validate inputs
        $passenger_name = sanitizeInput($_POST['passenger_name']);
        $mobile = sanitizeInput($_POST['mobile']);
        $gender = sanitizeInput($_POST['gender']);
        $num_seats = (int)sanitizeInput($_POST['num_seats']);
        $transaction_id = sanitizeInput($_POST['transaction_id']);

        // Basic validation
        if (empty($passenger_name) || empty($mobile) || empty($gender) || $num_seats < 1) {
            $error = "Please fill in all required fields";
        } 
        // Check if enough seats are available
        elseif ($num_seats > ($schedule['total_seats'] - $schedule['booked_seats'])) {
            $error = "Not enough seats available";
        }
        // Process booking
        else {
            try {
                $conn->beginTransaction();

                $stmt = $conn->prepare("
                    INSERT INTO bookings (
                        user_id, 
                        schedule_id, 
                        passenger_name, 
                        mobile_number,
                        gender,
                        number_of_seats, 
                        total_fare, 
                        transaction_id
                    ) VALUES (
                        :user_id, 
                        :schedule_id, 
                        :passenger_name, 
                        :mobile_number,
                        :gender,
                        :number_of_seats, 
                        :total_fare, 
                        :transaction_id
                    
                    )
                ");

                $total_fare = $schedule['fare'] * $num_seats;
                $user_id = $_SESSION['user_id'];

                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':schedule_id', $schedule_id);
                $stmt->bindParam(':passenger_name', $passenger_name);
                $stmt->bindParam(':mobile_number', $mobile);  // Changed parameter name to match
                $stmt->bindParam(':gender', $gender);
                $stmt->bindParam(':number_of_seats', $num_seats);
                $stmt->bindParam(':total_fare', $total_fare);
                $stmt->bindParam(':transaction_id', $transaction_id);

                $stmt->execute();
                $booking_id = $conn->lastInsertId();

                $conn->commit();
                
                // Redirect to ticket page
                redirect("ticket.php?booking=" . $booking_id);
                
            } catch (PDOException $e) {
                $conn->rollBack();
                $error = "Booking failed: " . $e->getMessage();
            }
        }
    }
} catch (PDOException $e) {
    $error = "Error loading schedule: " . $e->getMessage();
}

require_once 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Book Your Ticket</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <!-- Schedule Details -->
                    <div class="alert alert-info">
                        <h5 class="alert-heading">Journey Details</h5>
                        <p class="mb-0">
                            <strong>Bus:</strong> <?php echo htmlspecialchars($schedule['bus_name']); ?><br>
                            <strong>From:</strong> <?php echo htmlspecialchars($schedule['from_location']); ?><br>
                            <strong>To:</strong> <?php echo htmlspecialchars($schedule['to_location']); ?><br>
                            <strong>Departure:</strong> <?php echo date('Y-m-d h:i A', strtotime($schedule['departure_time'])); ?><br>
                            <strong>Fare:</strong> ৳<?php echo number_format($schedule['fare'], 2); ?> per seat<br>
                            <strong>Available Seats:</strong> <?php echo $schedule['total_seats'] - $schedule['booked_seats']; ?>
                        </p>
                    </div>

                    <!-- Booking Form -->
                    <form method="POST" action="" id="bookingForm">
                        <div class="mb-3">
                            <label for="passenger_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="passenger_name" name="passenger_name" required>
                        </div>

                        <div class="mb-3">
                            <label for="mobile" class="form-label">Mobile Number</label>
                            <input type="tel" class="form-control" id="mobile" name="mobile" required>
                        </div>

                        <div class="mb-3">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="num_seats" class="form-label">Number of Seats</label>
                            <input type="number" class="form-control" id="num_seats" name="num_seats" 
                                   min="1" max="<?php echo $schedule['total_seats'] - $schedule['booked_seats']; ?>" 
                                   required>
                            <div id="fareDisplay" class="form-text">
                                Total fare: ৳<span id="totalFare">0</span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h5 class="mb-3">Payment Details</h5>
                            <div class="alert alert-warning">
                                <p class="mb-2">Please send payment to bKash number: <strong>01XXXXXXXXX</strong></p>
                                <p class="mb-0">After sending payment, enter the Transaction ID below.</p>
                            </div>
                            <label for="transaction_id" class="form-label">bKash Transaction ID</label>
                            <input type="text" class="form-control" id="transaction_id" name="transaction_id" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Confirm Booking</button>
                        <a href="schedule.php" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const farePerSeat = <?php echo $schedule['fare']; ?>;
    const numSeatsInput = document.getElementById('num_seats');
    const totalFareSpan = document.getElementById('totalFare');
    
    // Update total fare when number of seats changes
    numSeatsInput.addEventListener('input', function() {
        const numSeats = this.value;
        const totalFare = numSeats * farePerSeat;
        totalFareSpan.textContent = totalFare.toFixed(2);
    });

    // Form validation
    document.getElementById('bookingForm').addEventListener('submit', function(e) {
        const mobile = document.getElementById('mobile').value;
        const transactionId = document.getElementById('transaction_id').value;

        // Validate mobile number (Bangladesh format)
        if (!/^01[3-9]\d{8}$/.test(mobile)) {
            e.preventDefault();
            alert('Please enter a valid Bangladeshi mobile number');
            return;
        }

        // Validate transaction ID
        if (transactionId.length < 8) {
            e.preventDefault();
            alert('Please enter a valid transaction ID');
            return;
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>