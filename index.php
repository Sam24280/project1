<?php
require_once 'includes/functions.php';
require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-content">
            <h1 class="display-4 mb-4">Welcome to Bus Ticket Booking System</h1>
            <p class="lead mb-4">Your Journey, Our Priority - Book Your Bus Tickets Online</p>
            <a href="schedule.php" class="btn btn-primary btn-lg">Book Now</a>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">Why Choose Us?</h2>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-ticket-alt fa-3x mb-3 text-primary"></i>
                        <h3 class="card-title h4">Easy Booking</h3>
                        <p class="card-text">Book your tickets with just a few clicks. Simple and hassle-free process.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-bus fa-3x mb-3 text-primary"></i>
                        <h3 class="card-title h4">Wide Network</h3>
                        <p class="card-text">Extensive network of buses covering all major routes and destinations.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-3x mb-3 text-primary"></i>
                        <h3 class="card-title h4">24/7 Support</h3>
                        <p class="card-text">Round-the-clock customer support to assist you with your booking needs.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="bg-light py-5">
    <div class="container">
        <h2 class="text-center mb-5">How It Works</h2>
        <div class="row">
            <div class="col-md-3 text-center mb-4">
                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <h3 class="m-0">1</h3>
                </div>
                <h4>Search</h4>
                <p>Select your route and date</p>
            </div>
            <div class="col-md-3 text-center mb-4">
                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <h3 class="m-0">2</h3>
                </div>
                <h4>Select</h4>
                <p>Choose your preferred bus</p>
            </div>
            <div class="col-md-3 text-center mb-4">
                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <h3 class="m-0">3</h3>
                </div>
                <h4>Book</h4>
                <p>Select seats and fill details</p>
            </div>
            <div class="col-md-3 text-center mb-4">
                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <h3 class="m-0">4</h3>
                </div>
                <h4>Pay</h4>
                <p>Make payment and get ticket</p>
            </div>
        </div>
    </div>
</section>

<!-- Popular Routes Section -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">Popular Routes</h2>
        <div class="row">
            <?php
            // Fetch popular routes from database
            try {
                $stmt = $conn->query("SELECT DISTINCT r.from_location, r.to_location, MIN(s.fare) as min_fare 
                                    FROM routes r 
                                    JOIN schedules s ON r.route_id = s.route_id 
                                    GROUP BY r.from_location, r.to_location 
                                    LIMIT 6");
                while ($route = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    ?>
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($route['from_location']); ?> to <?php echo htmlspecialchars($route['to_location']); ?></h5>
                                <p class="card-text">Starting from $<?php echo number_format($route['min_fare'], 2); ?></p>
                                <a href="schedule.php?from=<?php echo urlencode($route['from_location']); ?>&to=<?php echo urlencode($route['to_location']); ?>" class="btn btn-outline-primary">Check Schedule</a>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } catch (PDOException $e) {
                // If there's an error, show empty state
                echo "<div class='col-12 text-center'><p>Popular routes will be displayed here</p></div>";
            }
            ?>
        </div>
    </div>
</section>

<!-- Add Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<?php
require_once 'includes/footer.php';
?>