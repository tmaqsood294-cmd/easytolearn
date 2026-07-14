<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$db_path = __DIR__ . '/config/db.php';
if (!file_exists($db_path)) { die("Database Configuration Error."); }
require_once $db_path;

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    echo "<script>window.location.href='./admin/dashboard.php';</script>";
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        // Strict role check for admin
        $query = "SELECT * FROM users WHERE email = '$email' AND LOWER(role) = 'admin' LIMIT 1";
        $result = $conn->query($query);

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            if ($password === $user['password'] || md5($password) === $user['password'] || password_verify($password, $user['password'])) { 
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = strtolower($user['role']);
                $_SESSION['name'] = $user['name'];

                echo "<script>window.location.href='./admin/dashboard.php';</script>";
                exit;
            } else { $error = "Incorrect password."; }
        } else { $error = "No admin account found."; }
    } else { $error = "Please fill in all fields."; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | School SMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #141e30 0%, #243b55 100%); min-height: 100vh; font-family: 'Segoe UI', sans-serif; }
        .login-card { border: none; border-radius: 15px; background-color: #ffffff; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .btn-admin { background-color: #ee5253; color: white; font-weight: bold; }
        .btn-admin:hover { background-color: #ff6b6b; color: white; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card login-card p-4">
                <div class="text-center mb-4">
                    <span class="fs-1 text-danger"><i class="fas fa-user-shield"></i></span>
                    <h2 class="fw-bold text-dark mt-2">Admin Control</h2>
                    <p class="text-muted">Sign in to access management console</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 text-center"><?php echo $error; ?></div>
                <?php endif; ?>

                <form action="admin-login.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Admin Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-envelope text-muted"></i></span>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-admin w-100 py-2.5 shadow-sm fs-5">Login as Admin</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>