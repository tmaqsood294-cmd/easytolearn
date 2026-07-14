<?php
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = '';

// Catching the Class ID safely
$class_id = 0;
if (isset($_GET['id'])) {
    $class_id = intval($_GET['id']);
} elseif (isset($_GET['class_id'])) {
    $class_id = intval($_GET['class_id']);
}

if ($class_id <= 0) {
    die("Error: Invalid Class ID provided.");
}

// 1. Fetch Existing Class Details
$stmt = $conn->prepare("SELECT class_name, section FROM classes WHERE id = ?");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Error: Class not found in database.");
}
$class = $result->fetch_assoc();
$stmt->close();

// 2. Handle Form Submission (Update Process)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_name = trim($_POST['class_name']);
    $section = trim($_POST['section']);
    
    if (!empty($class_name)) {
        $update_stmt = $conn->prepare("UPDATE classes SET class_name = ?, section = ? WHERE id = ?");
        $update_stmt->bind_param("ssi", $class_name, $section, $class_id);
        
        if ($update_stmt->execute()) {
            $message = '<div class="alert alert-success rounded-3 shadow-sm"><i class="fas fa-check-circle me-2"></i>Class updated successfully!</div>';
            // Update local array to show new data in inputs immediately
            $class['class_name'] = $class_name;
            $class['section'] = $section;
        } else {
            $message = '<div class="alert alert-danger rounded-3 shadow-sm"><i class="fas fa-times-circle me-2"></i>Database Error. Could not update class.</div>';
        }
        $update_stmt->close();
    } else {
        $message = '<div class="alert alert-warning rounded-3 shadow-sm"><i class="fas fa-exclamation-triangle me-2"></i>Class Name cannot be empty!</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Class | School SMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .main-container { padding: 40px 15px; }
        .form-card { border-radius: 15px !important; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm sticky-top">
  <div class="container-fluid px-4">
    <a class="navbar-brand fw-bold fs-4 text-uppercase" href="dashboard.php">
        <i class="fas fa-school text-warning me-2"></i>School <span class="text-warning">SMS</span>
    </a>
  </div>
</nav>

<div class="container main-container">
    <div class="col-md-5 mx-auto">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="text-dark fw-bold m-0"><i class="fas fa-edit text-primary me-2"></i>Edit Class Details</h4>
            <a href="view-students.php" class="btn btn-outline-secondary btn-sm fw-bold rounded-3"><i class="fas fa-arrow-left me-1"></i> Back</a>
        </div>

        <?php echo $message; ?>

        <div class="card border-0 shadow-sm p-4 form-card">
            <form action="" method="POST">
                <div class="mb-3">
                    <label for="class_name" class="form-label fw-semibold">Class Name</label>
                    <input type="text" class="form-control px-3 py-2 rounded-3" id="class_name" name="class_name" value="<?php echo htmlspecialchars($class['class_name']); ?>" required>
                </div>
                
                <div class="mb-4">
                    <label for="section" class="form-label fw-semibold">Section</label>
                    <input type="text" class="form-control px-3 py-2 rounded-3" id="section" name="section" value="<?php echo htmlspecialchars($class['section']); ?>">
                    <div class="form-text small text-muted">Aap is field ko khali bhi chor sakte hain.</div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary fw-bold py-2 rounded-3 shadow-sm"><i class="fas fa-save me-2"></i>Update Class</button>
                </div>
            </form>
        </div>

    </div>
</div>

</body>
</html>