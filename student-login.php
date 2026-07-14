<?php
// 1. Live Server Strict Error Debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. Session Start
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// 3. Database Connection Path
$db_path = $_SERVER['DOCUMENT_ROOT'] . '/config/db.php';

if (!file_exists($db_path)) {
    die("Database Configuration Error: Database configuration file not found on the server.");
}
require_once $db_path;

$error_message = '';

// 4. Secure Login Processing Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_user = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($input_user) && !empty($password)) {
        $input_clean = mysqli_real_escape_string($conn, $input_user);
        
        // SMART FALLBACK QUERY: Pehle check karega table me kaunsa column exist karta hai
        $column_check = $conn->query("SHOW COLUMNS FROM `users` LIKE 'username'");
        $has_username_col = ($column_check && $column_check->num_rows > 0);
        
        // Agar 'username' column nahi hai, to standard systems ke mutabiq 'email' par fallback karega
        if ($has_username_col) {
            $login_query = "SELECT * FROM users WHERE username = '$input_clean' LIMIT 1";
        } else {
            $login_query = "SELECT * FROM users WHERE email = '$input_clean' LIMIT 1";
        }
        
        $result = $conn->query($login_query);

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Password dynamic checking framework
            $password_valid = false;
            if (password_verify($password, $user['password'])) {
                $password_valid = true;
            } elseif ($password === $user['password']) {
                $password_valid = true; 
            }

            if ($password_valid) {
                // Check if user is actually a student
                if (strtolower($user['role']) === 'student') {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['email'] ?? $user['username'] ?? $user['name'] ?? 'Student';
                    $_SESSION['role'] = 'student';
                    
                    header("Location: student/dashboard.php");
                    exit;
                } else {
                    $error_message = "Access Denied: This portal is exclusively for students.";
                }
            } else {
                $error_message = "Invalid Credentials: The password you entered is incorrect.";
            }
        } else {
            $error_message = "User Account not found in the system. Please verify your Email/Username.";
        }
    } else {
        $error_message = "Please fill all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login | School SMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; }
        .login-card { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 420px; background: #fff; }
        .btn-login { background-color: #212529; color: white; width: 100%; border-radius: 8px; font-weight: bold; }
        .btn-login:hover { background-color: #343a40; color: white; }
    </style>
</head>
<body>

<div class="card login-card p-4">
    <div class="text-center mb-4">
        <h2 class="fw-bold text-dark"><i class="fas fa-graduation-cap me-2 text-primary"></i>Student Login</h2>
        <p class="text-muted small">School Management System Portal</p>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger py-2 text-center small" role="alert">
            <i class="fas fa-exclamation-circle me-1"></i> <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="mb-3">
            <label class="form-label fw-semibold">Email / Username / Roll No</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="fas fa-user text-muted"></i></span>
                <input type="text" name="username" class="form-control" placeholder="Enter email or username" required autocomplete="off">
            </div>
        </div>
        
        <div class="mb-4">
            <label class="form-label fw-semibold">Password</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                <input type="password" name="password" class="form-control" placeholder="Enter password" required>
            </div>
        </div>

        <button type="submit" class="btn btn-login py-2 shadow-sm"><i class="fas fa-sign-in-alt me-2"></i> Log In</button>
    </form>
</div>

</body>
</html>