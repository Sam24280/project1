
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
                $bus_name = sanitizeInput($_POST['bus_name']);
                $total_seats = (int)$_POST['total_seats'];
                
                try {
                    $stmt = $conn->prepare("INSERT INTO buses (bus_name, total_seats) VALUES (?, ?)");
                    $stmt->execute([$bus_name, $total_seats]);
                    $success = "Bus added successfully!";
                } catch (PDOException $e) {
                    $error = "Error adding bus: " . $e->getMessage();
                }
                break;
                
            case 'edit':
                $bus_id = (int)$_POST['bus_id'];
                $bus_name = sanitizeInput($_POST['bus_name']);
                $total_seats = (int)$_POST['total_seats'];
                
                try {
                    $stmt = $conn->prepare("UPDATE buses SET bus_name = ?, total_seats = ? WHERE bus_id = ?");
                    $stmt->execute([$bus_name, $total_seats, $bus_id]);
                    $success = "Bus updated successfully!";
                } catch (PDOException $e) {
                    $error = "Error updating bus: " . $e->getMessage();
                }
                break;
                
            case 'delete':
                $bus_id = (int)$_POST['bus_id'];
                
                try {
                    // Check if bus has any schedules
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM schedules WHERE bus_id = ?");
                    $stmt->execute([$bus_id]);
                    if ($stmt->fetchColumn() > 0) {
                        $error = "Cannot delete bus: It has associated schedules.";
                    } else {
                        $stmt = $conn->prepare("DELETE FROM buses WHERE bus_id = ?");
                        $stmt->execute([$bus_id]);
                        $success = "Bus deleted successfully!";
                    }
                } catch (PDOException $e) {
                    $error = "Error deleting bus: " . $e->getMessage();
                }
                break;
        }
    }
}

// Fetch all buses
try {
    $stmt = $conn->query("SELECT * FROM buses ORDER BY bus_id DESC");
    $buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching buses: " . $e->getMessage();
    $buses = [];
}

require_once '../includes/header.php';
?>

<!-- Admin Header -->
<div class="bg-primary text-white py-3">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h1><i class="fas fa-bus"></i> Manage Buses</h1>
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
                    <a class="nav-link active" href="manage_buses.php">Manage Buses</a>
                    <a class="nav-link" href="manage_routes.php">Manage Routes</a>
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

    <!-- Add Bus Button -->
    <div class="row mb-4">
        <div class="col-md-12">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBusModal">
                <i class="fas fa-plus"></i> Add New Bus
            </button>
        </div>
    </div>

    <!-- Buses Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Bus Name</th>
                            <th>Total Seats</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($buses as $bus): ?>
                        <tr>
                            <td><?php echo $bus['bus_id']; ?></td>
                            <td><?php echo htmlspecialchars($bus['bus_name']); ?></td>
                            <td><?php echo $bus['total_seats']; ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary edit-bus" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editBusModal"
                                        data-id="<?php echo $bus['bus_id']; ?>"
                                        data-name="<?php echo htmlspecialchars($bus['bus_name']); ?>"
                                        data-seats="<?php echo $bus['total_seats']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger delete-bus"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteBusModal"
                                        data-id="<?php echo $bus['bus_id']; ?>"
                                        data-name="<?php echo htmlspecialchars($bus['bus_name']); ?>">
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

<!-- Add Bus Modal -->
<div class="modal fade" id="addBusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Bus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="bus_name" class="form-label">Bus Name</label>
                        <input type="text" class="form-control" id="bus_name" name="bus_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="total_seats" class="form-label">Total Seats</label>
                        <input type="number" class="form-control" id="total_seats" name="total_seats" required min="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Bus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Bus Modal -->
<div class="modal fade" id="editBusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="bus_id" id="edit_bus_id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Bus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_bus_name" class="form-label">Bus Name</label>
                        <input type="text" class="form-control" id="edit_bus_name" name="bus_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_total_seats" class="form-label">Total Seats</label>
                        <input type="number" class="form-control" id="edit_total_seats" name="total_seats" required min="1">
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

<!-- Delete Bus Modal -->
<div class="modal fade" id="deleteBusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="bus_id" id="delete_bus_id">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Bus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the bus: <span id="delete_bus_name"></span>?</p>
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
    // Edit Bus Modal
    document.querySelectorAll('.edit-bus').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const seats = this.getAttribute('data-seats');
            
            document.getElementById('edit_bus_id').value = id;
            document.getElementById('edit_bus_name').value = name;
            document.getElementById('edit_total_seats').value = seats;
        });
    });

    // Delete Bus Modal
    document.querySelectorAll('.delete-bus').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            
            document.getElementById('delete_bus_id').value = id;
            document.getElementById('delete_bus_name').textContent = name;
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>