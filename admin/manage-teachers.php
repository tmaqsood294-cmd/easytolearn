<?php
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$msg = "";

// DELETE ACTION FOR TEACHER
if (isset($_GET['delete_teacher'])) {
    $teacher_user_id = intval($_GET['delete_teacher']);
    $conn->query("SET FOREIGN_KEY_CHECKS = 0;");
    $conn->query("DELETE FROM teachers WHERE user_id = $teacher_user_id");
    $conn->query("DELETE FROM users WHERE id = $teacher_user_id AND LOWER(role) = 'teacher'");
    // Also clean up any active modern allocations assigned to this teacher
    $conn->query("DELETE FROM teacher_assignments WHERE teacher_id = $teacher_user_id");
    $conn->query("SET FOREIGN_KEY_CHECKS = 1;");
    $msg = "<div class='alert alert-warning shadow-sm'><i class='fas fa-trash me-2'></i>Teacher record and assignments removed permanently!</div>";
}

// INSERT ACTION FOR TEACHER
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_teacher'])) {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    
    // Secure 60-character bcrypt hash generation
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $subject = mysqli_real_escape_string($conn, trim($_POST['subject']));
    $qualification = mysqli_real_escape_string($conn, trim($_POST['qualification']));
    $salary = mysqli_real_escape_string($conn, trim($_POST['salary']));

    $check = $conn->query("SELECT id FROM users WHERE email='$email'");
    if ($check && $check->num_rows > 0) {
        $msg = "<div class='alert alert-danger shadow-sm'>Teacher email already exists!</div>";
    } else {
        // Enforcing structured clean lower-case value for unified role handling
        if ($conn->query("INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password', 'teacher')")) {
            $user_id = $conn->insert_id;
            $conn->query("INSERT INTO teachers (user_id, subject, qualification, salary) VALUES ('$user_id', '$subject', '$qualification', '$salary')");
            $msg = "<div class='alert alert-success shadow-sm'>Teacher registered successfully!</div>";
        } else {
            $msg = "<div class='alert alert-danger shadow-sm'>Error creating account. Please verify database connection.</div>";
        }
    }
}

$teachers = $conn->query("SELECT u.id as user_id, u.name, u.email, t.subject, t.qualification FROM users u JOIN teachers t ON u.id = t.user_id WHERE LOWER(u.role) = 'teacher'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers | School SMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>body { background-color: #f4f6f9; } .main-container { padding: 30px; }</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="dashboard.php"><i class="fas fa-school me-2"></i>School SMS</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link text-white" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="add-student.php">Add/Manage Students</a></li>
        <li class="nav-item"><a class="nav-link text-white active" href="manage-teachers.php">Teachers</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="assign-lecture.php">Assign Lectures</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="fee-status.php">Fees</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container main-container">
    <div class="row">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4">
                <h4 class="text-success mb-3"><i class="fas fa-plus me-2"></i>Add Teacher</h4>
                <?php echo $msg; ?>
                <form method="POST">
                    <input type="hidden" name="add_teacher" value="1">
                    <div class="mb-2"><label class="form-label">Full Name</label><input type="text" name="name" class="form-control" required></div>
                    <div class="mb-2"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
                    <div class="mb-2"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
                    <div class="mb-2"><label class="form-label">Assigned Subject</label><input type="text" name="subject" class="form-control" required></div>
                    <div class="mb-2"><label class="form-label">Qualification</label><input type="text" name="qualification" class="form-control" required></div>
                    <div class="mb-2"><label class="form-label">Monthly Salary</label><input type="number" name="salary" class="form-control" required></div>
                    <button type="submit" class="btn btn-success w-100 mt-2 py-2 fw-bold"><i class="fas fa-check me-2"></i>Register Teacher</button>
                </form>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card border-0 shadow-sm p-4">
                <h4 class="mb-3">Registered Teachers List</h4>
                <div class="table-responsive">
                    <table class="table table-striped align-middle text-center">
                        <thead class="table-dark">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Subject</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($teachers && $teachers->num_rows > 0): while($r = $teachers->fetch_assoc()): ?>
                                <tr>
                                    <td class="text-start fw-semibold"><?php echo htmlspecialchars($r['name']); ?></td>
                                    <td><?php echo htmlspecialchars($r['email']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($r['subject']); ?></span></td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-2">
                                            <a href="assign-lecture.php?teacher_id=<?php echo $r['user_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-calendar-plus"></i> Assign
                                            </a>
                                            <a href="manage-teachers.php?delete_teacher=<?php echo $r['user_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this teacher permanently?');">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="4" class="text-center text-muted py-3">No teachers registered yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>