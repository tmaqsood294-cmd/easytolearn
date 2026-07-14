<?php
require_once 'config/db.php';

// Redirect user if already logged in based on their role
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') { header("Location: admin/dashboard.php"); exit; }
    elseif ($_SESSION['role'] == 'teacher') { header("Location: teacher/dashboard.php"); exit; }
    elseif ($_SESSION['role'] == 'student') { header("Location: student/dashboard.php"); exit; }
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Verify secure password hash from database
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] == 'admin') { header("Location: admin/dashboard.php"); }
            elseif ($user['role'] == 'teacher') { header("Location: teacher/dashboard.php"); }
            elseif ($user['role'] == 'student') { header("Location: student/dashboard.php"); }
            exit;
        } else {
            $error = "Incorrect password! Please try again.";
        }
    } else {
        $error = "This email address is not registered in our system.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Login | Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .btn-primary { background-color: #4e73df; border: none; }
        .btn-primary:hover { background-color: #2e59d9; }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card login-card p-4">
                <div class="text-center mb-4">
                    <h3 class="text-primary fw-bold">🏫 School SMS</h3>
                    <p class="text-muted">Log in to your account</p>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger text-center"><?php echo $error; ?></div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="text" name="email" class="form-control" placeholder="admin@school.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Login</button>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>