<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Strict admin routing check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../admin-login.php");
    exit;
}

$msg = "";

// Handle Password Update Form Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_password'])) {
    $user_id = intval($_POST['user_id']);
    $new_password = trim($_POST['new_password']);

    if (!empty($new_password)) {
        $clean_password = mysqli_real_escape_string($conn, $new_password);
        
        // Directly updating password (humara login script is plain text ko accept kar lega)
        $update_query = "UPDATE users SET password = '$clean_password' WHERE id = $user_id";
        
        if ($conn->query($update_query)) {
            $msg = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                        <i class='fas fa-check-circle me-2'></i> Password updated successfully!
                    </div>";
        } else {
            $msg = "<div class='alert alert-danger' role='alert'>Error updating password: " . $conn->error . "</div>";
        }
    } else {
        $msg = "<div class='alert alert-warning' role='alert'>Password cannot be empty.</div>";
    }
}

// Fetch all users sorted by role
$users_res = $conn->query("SELECT id, name, email, role, password FROM users ORDER BY field(role, 'admin', 'teacher', 'student'), name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Passwords | Admin Control</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .main-container { padding: 30px; }
        .role-badge { font-size: 0.85rem; padding: 5px 10px; border-radius: 20px; font-weight: 600; }
        .badge-admin { background-color: #ee5253; color: white; }
        .badge-teacher { background-color: #10ac84; color: white; }
        .badge-student { background-color: #ff9f43; color: white; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="dashboard.php"><i class="fas fa-user-shield me-2"></i>Admin Console</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link text-white" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link text-white active" href="manage-passwords.php">Manage Passwords</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container main-container">
    <div class="card border-0 shadow-sm p-4 col-md-10 mx-auto bg-white">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="text-dark fw-bold m-0"><i class="fas fa-key text-danger me-2"></i>User Password Management</h3>
            <span class="text-muted">Total Users: <?php echo $users_res ? $users_res->num_rows : 0; ?></span>
        </div>
        
        <?php echo $msg; ?>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light text-secondary">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Current Password</th>
                        <th class="text-center" style="width: 35%;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($users_res && $users_res->num_rows > 0): while($row = $users_res->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['name']); ?></div>
                                <small class="text-muted">ID: <?php echo $row['id']; ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td>
                                <span class="role-badge badge-<?php echo strtolower($row['role']); ?>">
                                    <?php echo ucfirst($row['role']); ?>
                                </span>
                            </td>
                            <td>
                                <code class="text-dark bg-light px-2 py-1 rounded">
                                    <?php echo htmlspecialchars($row['password']); ?>
                                </code>
                            </td>
                            <td>
                                <form method="POST" action="manage-passwords.php" class="row g-2 align-items-center justify-content-center">
                                    <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                    <div class="col-sm-8">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="fas fa-lock-open fa-xs"></i></span>
                                            <input type="text" name="new_password" class="form-control" placeholder="Type new password" required>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <button type="submit" name="update_password" class="btn btn-danger btn-sm w-100 fw-semibold shadow-sm">
                                            <i class="fas fa-save me-1"></i> Update
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">No user records found in database.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>