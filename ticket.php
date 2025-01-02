<?php
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Check if booking ID is provided
if (!isset($_GET['booking'])) {
    redirect('schedule.php');
}

$booking_id = sanitizeInput($_GET['booking']);
$error = '';

try {
    // Get booking details with all related information
    $stmt = $conn->prepare("
        SELECT 
            b.booking_id,
            b.passenger_name,
            b.mobile_number,
            b.gender,
            b.number_of_seats,
            b.total_fare,
            b.transaction_id,
            s.departure_time,
            r.from_location,
            r.to_location,
            bs.bus_name
        FROM bookings b
        JOIN schedules s ON b.schedule_id = s.schedule_id
        JOIN routes r ON s.route_id = r.route_id
        JOIN buses bs ON s.bus_id = bs.bus_id
        WHERE b.booking_id = :booking_id AND b.user_id = :user_id
    ");
    
    $stmt->bindParam(':booking_id', $booking_id);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        redirect('schedule.php');
    }

} catch (PDOException $e) {
    $error = "Error retrieving ticket: " . $e->getMessage();
}

require_once 'includes/header.php';
?>

<div class="container mt-5 mb-5">
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php else: ?>
        <!-- Success Message -->
        <div class="alert alert-success">
            <h4 class="alert-heading">Booking Successful!</h4>
            <p>Your ticket has been booked successfully. Please save or print this ticket for your reference.</p>
        </div>

        <!-- Ticket Section -->
        <div class="card" id="ticketSection">
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <!-- Company Header -->
                        <div class="text-center mb-4">
                            <h2 class="mb-0">Bus Ticket Booking System</h2>
                            <p class="text-muted">E-Ticket / Booking Confirmation</p>
                        </div>

                        <!-- Booking Reference -->
                        <div class="row mb-4">
                            <div class="col-6">
                                <h5>Booking Reference:</h5>
                                <p class="mb-0">TICKET-<?php echo str_pad($ticket['booking_id'], 6, '0', STR_PAD_LEFT); ?></p>
                            </div>
                        </div>

                        <!-- Journey Details -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Journey Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>From:</strong> <?php echo htmlspecialchars($ticket['from_location']); ?></p>
                                        <p><strong>To:</strong> <?php echo htmlspecialchars($ticket['to_location']); ?></p>
                                        <p><strong>Date:</strong> <?php echo date('d M Y', strtotime($ticket['departure_time'])); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Bus Name:</strong> <?php echo htmlspecialchars($ticket['bus_name']); ?></p>
                                        <p><strong>Departure Time:</strong> <?php echo date('h:i A', strtotime($ticket['departure_time'])); ?></p>
                                        <p><strong>Number of Seats:</strong> <?php echo $ticket['number_of_seats']; ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Passenger Details -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Passenger Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Passenger Name:</strong> <?php echo htmlspecialchars($ticket['passenger_name']); ?></p>
                                        <p><strong>Gender:</strong> <?php echo htmlspecialchars($ticket['gender']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Mobile:</strong> <?php echo htmlspecialchars($ticket['mobile_number']); ?></p>
                                        <p><strong>Transaction ID:</strong> <?php echo htmlspecialchars($ticket['transaction_id']); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Details -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Payment Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Amount Paid:</strong> ৳<?php echo number_format($ticket['total_fare'], 2); ?></p>
                                        <p><strong>Payment Status:</strong> <span class="badge bg-success">PAID</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Terms and Conditions -->
                        <div class="mt-4">
                            <h6>Important Notes:</h6>
                            <ul class="small text-muted">
                                <li>Please arrive at least 30 minutes before departure time.</li>
                                <li>Show this ticket on your mobile or as a printout when boarding.</li>
                                <li>This is a computer-generated ticket and doesn't require a signature.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="text-center mt-4">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print Ticket
            </button>
            <a href="schedule.php" class="btn btn-secondary">
                <i class="fas fa-search"></i> Book Another Ticket
            </a>
        </div>
    <?php endif; ?>
</div>

<style>
/* Print Styles */
@media print {
    body * {
        visibility: hidden;
    }
    #ticketSection, #ticketSection * {
        visibility: visible;
    }
    #ticketSection {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    .btn, .alert {
        display: none !important;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>