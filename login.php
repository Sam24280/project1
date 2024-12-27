
<?php
require_once 'includes/functions.php';
require_once 'includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    $userType = sanitizeInput($_POST['user_type']);

    if ($userType == 'admin') {
        // Admin Login
        if ($username === 'admin' && $password === 'admin') {
            $_SESSION['is_admin'] = true;
            $_SESSION['user_id'] = 'admin';
            redirect('admin/dashboard.php');
        } else {
            $error = "Invalid admin credentials!";
        }
    } else {
        // User Login
        try {
            $stmt = $conn->prepare("SELECT user_id, password FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['is_admin'] = false;
                redirect('index.php');
            } else {
                $error = "Invalid username or password!";
            }
        } catch (PDOException $e) {
            $error = "Error during login. Please try again.";
        }
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs">
                        <li class="nav-item">
                            <a class="nav-link active" href="#user-login" data-bs-toggle="tab">User Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#admin-login" data-bs-toggle="tab">Admin Login</a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <div class="tab-content">
                        <!-- User Login Form -->
                        <div class="tab-pane fade show active" id="user-login">
                            <form method="POST" action="">
                                <input type="hidden" name="user_type" value="user">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Login</button>
                                <p class="mt-3">Don't have an account? <a href="register.php">Register here</a></p>
                            </form>
                        </div>

                        <!-- Admin Login Form -->
                        <div class="tab-pane fade" id="admin-login">
                            <form method="POST" action="">
                                <input type="hidden" name="user_type" value="admin">
                                <div class="mb-3">
                                    <label for="admin-username" class="form-label">Admin Username</label>
                                    <input type="text" class="form-control" id="admin-username" name="username" required>
                                </div>
                                <div class="mb-3">
                                    <label for="admin-password" class="form-label">Admin Password</label>
                                    <input type="password" class="form-control" id="admin-password" name="password" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Admin Login</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>