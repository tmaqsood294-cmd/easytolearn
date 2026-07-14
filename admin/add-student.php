<?php
// 1. TEMPORARY: Turn on error reporting to see exactly what fails
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Include database connection
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $roll_no = mysqli_real_escape_string($conn, $_POST['roll_no']);
    $parent_name = mysqli_real_escape_string($conn, $_POST['parent_name']);
    $parent_phone = mysqli_real_escape_string($conn, $_POST['parent_phone']);
    $class_id = intval($_POST['class_id']); 

    $check = $conn->query("SELECT id FROM users WHERE email='$email'");
    if ($check && $check->num_rows > 0) {
        $msg = "<div class='alert alert-danger shadow-sm'>This Email is already registered!</div>";
    } else {
        $user_sql = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password', 'student')";
        if ($conn->query($user_sql) === TRUE) {
            $user_id = $conn->insert_id;
            $student_sql = "INSERT INTO students (user_id, class_id, roll_no, parent_name, parent_phone) VALUES ('$user_id', '$class_id', '$roll_no', '$parent_name', '$parent_phone')";
            $conn->query($student_sql);
            $msg = "<div class='alert alert-success shadow-sm'><i class='fas fa-check-circle me-2'></i>Student added successfully!</div>";
        } else {
            $msg = "<div class='alert alert-danger shadow-sm'>Database Error: " . $conn->error . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student | School SMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="dashboard.php"><i class="fas fa-school me-2"></i>School SMS</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link text-white" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link text-white active" href="add-student.php">Add Student</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="view-students.php">Manage Students</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container my-5">
    <div class="card border-0 shadow-sm p-4 col-md-10 mx-auto">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="text-primary mb-0"><i class="fas fa-user-plus me-2"></i>Add Student Record</h3>
            <a href="view-students.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-eye me-1"></i> View All Students</a>
        </div>
        
        <?php echo $msg; ?>
        
        <form method="POST">
            <input type="hidden" name="add_student" value="1">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label fw-semibold">Full Name</label><input type="text" name="name" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label fw-semibold">Roll Number</label><input type="text" name="roll_no" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label fw-semibold">Login Email</label><input type="email" name="email" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label fw-semibold">Account Password</label><input type="password" name="password" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label fw-semibold">Father Name</label><input type="text" name="parent_name" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label fw-semibold">Parent Phone</label><input type="text" name="parent_phone" class="form-control" required></div>
                
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Select Class</label>
                    <select name="class_id" class="form-select" required>
                        <option value="" disabled selected>-- Choose Class --</option>
                        <?php
                        // Safely handle dropdown generation
                        $dropdown_list = $conn->query("SELECT id, class_name, section FROM classes ORDER BY id ASC");
                        
                        if ($dropdown_list) {
                            while($cl = $dropdown_list->fetch_assoc()) {
                                $section_display = !empty($cl['section']) ? " (Section: ".$cl['section'].")" : "";
                                echo "<option value='".$cl['id']."'>".$cl['class_name'].$section_display."</option>";
                            }
                        } else {
                            // This prevents the page from breaking if the database table has issues
                            echo "<option value='' disabled>Error loading classes: " . htmlspecialchars($conn->error) . "</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-4 w-100 py-2 fw-bold"><i class="fas fa-save me-2"></i>Save Student Profile</button>
        </form>
    </div>
</div>
</body>
</html>