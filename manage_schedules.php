<?php
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdmin()) {
    redirect('../login.php');
}

$error = '';
$success = '';

// Fetch all buses and routes for select options
try {
    $buses = $conn->query("SELECT * FROM buses ORDER BY bus_name")->fetchAll();
    $routes = $conn->query("SELECT * FROM routes ORDER BY from_location")->fetchAll();
} catch (PDOException $e) {
    $error = "Error fetching data: " . $e->getMessage();
    $buses = [];
    $routes = [];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        switch ($action) {
            case 'add':
                $bus_id = (int)$_POST['bus_id'];
                $route_id = (int)$_POST['route_id'];
                $departure_time = sanitizeInput($_POST['departure_time']);
                $fare = (float)$_POST['fare'];
                $status = sanitizeInput($_POST['status']);
                
                try {
                    $stmt = $conn->prepare("INSERT INTO schedules (bus_id, route_id, departure_time, fare, status) 
                                          VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$bus_id, $route_id, $departure_time, $fare, $status]);
                    $success = "Schedule added successfully!";
                } catch (PDOException $e) {
                    $error = "Error adding schedule: " . $e->getMessage();
                }
                break;
                
            case 'edit':
                $schedule_id = (int)$_POST['schedule_id'];
                $bus_id = (int)$_POST['bus_id'];
                $route_id = (int)$_POST['route_id'];
                $departure_time = sanitizeInput($_POST['departure_time']);
                $fare = (float)$_POST['fare'];
                $status = sanitizeInput($_POST['status']);
                
                try {
                    $stmt = $conn->prepare("UPDATE schedules 
                                          SET bus_id = ?, route_id = ?, departure_time = ?, 
                                              fare = ?, status = ? 
                                          WHERE schedule_id = ?");
                    $stmt->execute([$bus_id, $route_id, $departure_time, $fare, $status, $schedule_id]);
                    $success = "Schedule updated successfully!";
                } catch (PDOException $e) {
                    $error = "Error updating schedule: " . $e->getMessage();
                }
                break;
                
            case 'delete':
                $schedule_id = (int)$_POST['schedule_id'];
                
                try {
                    // Check if schedule has any bookings
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE schedule_id = ?");
                    $stmt->execute([$schedule_id]);
                    if ($stmt->fetchColumn() > 0) {
                        $error = "Cannot delete schedule: It has associated bookings.";
                    } else {
                        $stmt = $conn->prepare("DELETE FROM schedules WHERE schedule_id = ?");
                        $stmt->execute([$schedule_id]);
                        $success = "Schedule deleted successfully!";
                    }
                } catch (PDOException $e) {
                    $error = "Error deleting schedule: " . $e->getMessage();
                }
                break;
        }
    }
}

// Fetch all schedules with bus and route information
try {
    $stmt = $conn->query("SELECT s.*, b.bus_name, r.from_location, r.to_location 
                         FROM schedules s
                         JOIN buses b ON s.bus_id = b.bus_id
                         JOIN routes r ON s.route_id = r.route_id
                         ORDER BY s.departure_time");
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching schedules: " . $e->getMessage();
    $schedules = [];
}

require_once '../includes/header.php';
?>

<!-- Admin Header -->
<div class="bg-primary text-white py-3">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h1><i class="fas fa-clock"></i> Manage Schedules</h1>
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
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                    <a class="nav-link" href="manage_buses.php">Manage Buses</a>
                    <a class="nav-link" href="manage_routes.php">Manage Routes</a>
                    <a class="nav-link active" href="manage_schedules.php">Manage Schedules</a>
                </nav>
            </div>
        </div>
    </div>
</section>

<div class="container mt-4">
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <!-- Add Schedule Button -->
    <div class="row mb-4">
        <div class="col-md-12">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                <i class="fas fa-plus"></i> Add New Schedule
            </button>
        </div>
    </div>

    <!-- Schedules Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Bus</th>
                            <th>Route</th>
                            <th>Departure Time</th>
                            <th>Fare</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $schedule): ?>
                        <tr>
                            <td><?php echo $schedule['schedule_id']; ?></td>
                            <td><?php echo htmlspecialchars($schedule['bus_name']); ?></td>
                            <td><?php echo htmlspecialchars($schedule['from_location'] . ' to ' . $schedule['to_location']); ?></td>
                            <td><?php echo date('h:i A', strtotime($schedule['departure_time'])); ?></td>
                            <td><?php echo number_format($schedule['fare'], 2); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $schedule['status'] == 'active' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($schedule['status']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary edit-schedule" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editScheduleModal"
                                        data-id="<?php echo $schedule['schedule_id']; ?>"
                                        data-bus="<?php echo $schedule['bus_id']; ?>"
                                        data-route="<?php echo $schedule['route_id']; ?>"
                                        data-time="<?php echo $schedule['departure_time']; ?>"
                                        data-fare="<?php echo $schedule['fare']; ?>"
                                        data-status="<?php echo $schedule['status']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger delete-schedule"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteScheduleModal"
                                        data-id="<?php echo $schedule['schedule_id']; ?>"
                                        data-bus="<?php echo htmlspecialchars($schedule['bus_name']); ?>"
                                        data-route="<?php echo htmlspecialchars($schedule['from_location'] . ' to ' . $schedule['to_location']); ?>"
                                        data-time="<?php echo date('h:i A', strtotime($schedule['departure_time'])); ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Schedule Modal -->
<div class="modal fade" id="addScheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="bus_id" class="form-label">Bus</label>
                        <select class="form-select" id="bus_id" name="bus_id" required>
                            <option value="">Select Bus</option>
                            <?php foreach ($buses as $bus): ?>
                                <option value="<?php echo $bus['bus_id']; ?>">
                                    <?php echo htmlspecialchars($bus['bus_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="route_id" class="form-label">Route</label>
                        <select class="form-select" id="route_id" name="route_id" required>
                            <option value="">Select Route</option>
                            <?php foreach ($routes as $route): ?>
                                <option value="<?php echo $route['route_id']; ?>">
                                    <?php echo htmlspecialchars($route['from_location'] . ' to ' . $route['to_location']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="departure_time" class="form-label">Departure Time</label>
                        <input type="time" class="form-control" id="departure_time" name="departure_time" required>
                    </div>
                    <div class="mb-3">
                        <label for="fare" class="form-label">Fare</label>
                        <input type="number" class="form-control" id="fare" name="fare" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Schedule Modal -->
<div class="modal fade" id="editScheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="schedule_id" id="edit_schedule_id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_bus_id" class="form-label">Bus</label>
                        <select class="form-select" id="edit_bus_id" name="bus_id" required>
                            <?php foreach ($buses as $bus): ?>
                                <option value="<?php echo $bus['bus_id']; ?>">
                                    <?php echo htmlspecialchars($bus['bus_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_route_id" class="form-label">Route</label>
                        <select class="form-select" id="edit_route_id" name="route_id" required>
                            <?php foreach ($routes as $route): ?>
                                <option value="<?php echo $route['route_id']; ?>">
                                    <?php echo htmlspecialchars($route['from_location'] . ' to ' . $route['to_location']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_departure_time" class="form-label">Departure Time</label>
                        <input type="time" class="form-control" id="edit_departure_time" name="departure_time" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_fare" class="form-label">Fare</label>
                        <input type="number" class="form-control" id="edit_fare" name="fare" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <select class="form-select" id="edit_status" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

