<?php
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdmin()) {
    redirect('../login.php');
}

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        switch ($action) {
            case 'add':
                $from_location = sanitizeInput($_POST['from_location']);
                $to_location = sanitizeInput($_POST['to_location']);
                
                try {
                    $stmt = $conn->prepare("INSERT INTO routes (from_location, to_location) VALUES (?, ?)");
                    $stmt->execute([$from_location, $to_location]);
                    $success = "Route added successfully!";
                } catch (PDOException $e) {
                    $error = "Error adding route: " . $e->getMessage();
                }
                break;
                
            case 'edit':
                $route_id = (int)$_POST['route_id'];
                $from_location = sanitizeInput($_POST['from_location']);
                $to_location = sanitizeInput($_POST['to_location']);
                
                try {
                    $stmt = $conn->prepare("UPDATE routes SET from_location = ?, to_location = ? WHERE route_id = ?");
                    $stmt->execute([$from_location, $to_location, $route_id]);
                    $success = "Route updated successfully!";
                } catch (PDOException $e) {
                    $error = "Error updating route: " . $e->getMessage();
                }
                break;
                
            case 'delete':
                $route_id = (int)$_POST['route_id'];
                
                try {
                    // Check if route has any schedules
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM schedules WHERE route_id = ?");
                    $stmt->execute([$route_id]);
                    if ($stmt->fetchColumn() > 0) {
                        $error = "Cannot delete route: It has associated schedules.";
                    } else {
                        $stmt = $conn->prepare("DELETE FROM routes WHERE route_id = ?");
                        $stmt->execute([$route_id]);
                        $success = "Route deleted successfully!";
                    }
                } catch (PDOException $e) {
                    $error = "Error deleting route: " . $e->getMessage();
                }
                break;
        }
    }
}

// Fetch all routes
try {
    $stmt = $conn->query("SELECT * FROM routes ORDER BY route_id DESC");
    $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching routes: " . $e->getMessage();
    $routes = [];
}

require_once '../includes/header.php';
?>

<!-- Admin Header -->
<div class="bg-primary text-white py-3">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h1><i class="fas fa-route"></i> Manage Routes</h1>
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
                    <a class="nav-link active" href="manage_routes.php">Manage Routes</a>
                    <a class="nav-link" href="manage_schedules.php">Manage Schedules</a>
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

    <!-- Add Route Button -->
    <div class="row mb-4">
        <div class="col-md-12">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRouteModal">
                <i class="fas fa-plus"></i> Add New Route
            </button>
        </div>
    </div>

    <!-- Routes Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>From Location</th>
                            <th>To Location</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($routes as $route): ?>
                        <tr>
                            <td><?php echo $route['route_id']; ?></td>
                            <td><?php echo htmlspecialchars($route['from_location']); ?></td>
                            <td><?php echo htmlspecialchars($route['to_location']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary edit-route" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editRouteModal"
                                        data-id="<?php echo $route['route_id']; ?>"
                                        data-from="<?php echo htmlspecialchars($route['from_location']); ?>"
                                        data-to="<?php echo htmlspecialchars($route['to_location']); ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger delete-route"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteRouteModal"
                                        data-id="<?php echo $route['route_id']; ?>"
                                        data-from="<?php echo htmlspecialchars($route['from_location']); ?>"
                                        data-to="<?php echo htmlspecialchars($route['to_location']); ?>">
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

<!-- Add Route Modal -->
<div class="modal fade" id="addRouteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Route</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="from_location" class="form-label">From Location</label>
                        <input type="text" class="form-control" id="from_location" name="from_location" required>
                    </div>
                    <div class="mb-3">
                        <label for="to_location" class="form-label">To Location</label>
                        <input type="text" class="form-control" id="to_location" name="to_location" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Route</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Route Modal -->
<div class="modal fade" id="editRouteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="route_id" id="edit_route_id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Route</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_from_location" class="form-label">From Location</label>
                        <input type="text" class="form-control" id="edit_from_location" name="from_location" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_to_location" class="form-label">To Location</label>
                        <input type="text" class="form-control" id="edit_to_location" name="to_location" required>
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

<!-- Delete Route Modal -->
<div class="modal fade" id="deleteRouteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="route_id" id="delete_route_id">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Route</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the route: 
                        <span id="delete_route_from"></span> to <span id="delete_route_to"></span>?</p>
                    <p class="text-danger">This action cannot be undone!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add this before closing body tag -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit Route Modal
    document.querySelectorAll('.edit-route').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const from = this.getAttribute('data-from');
            const to = this.getAttribute('data-to');
            
            document.getElementById('edit_route_id').value = id;
            document.getElementById('edit_from_location').value = from;
            document.getElementById('edit_to_location').value = to;
        });
    });

    // Delete Route Modal
    document.querySelectorAll('.delete-route').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const from = this.getAttribute('data-from');
            const to = this.getAttribute('data-to');
            
            document.getElementById('delete_route_id').value = id;
            document.getElementById('delete_route_from').textContent = from;
            document.getElementById('delete_route_to').textContent = to;
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>