<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);

if ($class_id <= 0) {
    die("Invalid Class Selection.");
}

// -------------------------------------------------------------
// DELETE HANDLER ROUTINE (Jab user Delete par click kare)
// -------------------------------------------------------------
$delete_message = '';
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['student_id'])) {
    $del_id = intval($_GET['student_id']);
    
    if ($del_id > 0) {
        // Query to delete from students table
        $delete_stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
        $delete_stmt->bind_param("i", $del_id);
        
        if ($delete_stmt->execute()) {
            $delete_message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>Student record deleted successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
        } else {
            $delete_message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>Error deleting record: ' . $conn->error . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
        }
        $delete_stmt->close();
    }
}

// Fetch Class Info
$class_stmt = $conn->prepare("SELECT class_name, section FROM classes WHERE id = ?");
$class_stmt->bind_param("i", $class_id);
$class_stmt->execute();
$class_info = $class_stmt->get_result()->fetch_assoc();
$class_stmt->close();

// Fetch Students with INNER JOIN to users table
$students_stmt = $conn->prepare("
    SELECT s.*, u.name as student_name, u.email 
    FROM students s 
    INNER JOIN users u ON s.user_id = u.id 
    WHERE s.class_id = ? 
    ORDER BY s.id DESC
");
$students_stmt->bind_param("i", $class_id); 
$students_stmt->execute();
$students_result = $students_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Members Roster | School SMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .main-container { padding: 40px 15px; }
        .table-card { border-radius: 15px !important; overflow: hidden; }
        .btn-action { padding: 4px 10px; font-size: 0.85rem; border-radius: 6px; }
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
    <div class="col-md-11 mx-auto">
        
        <?php echo $delete_message; ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <div>
                <h3 class="text-dark fw-bold m-0">
                    <i class="fas fa-users text-primary me-2"></i>
                    <?php echo htmlspecialchars($class_info['class_name'] ?? 'Class'); ?> 
                    <?php echo !empty($class_info['section']) ? '('.htmlspecialchars($class_info['section']).')' : ''; ?>
                </h3>
                <p class="text-muted m-0 small">Manage your dynamic records. Click Edit to change profile or Delete to remove.</p>
            </div>
            <div>
                <a href="view-students.php" class="btn btn-outline-secondary fw-bold shadow-sm rounded-3">
                    <i class="fas fa-arrow-left me-1"></i> Back to Cards
                </a>
            </div>
        </div>

        <div class="card border-0 shadow-sm table-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Roll No</th>
                            <th>Student Name</th>
                            <th>Father's Name</th>
                            <th>Parent Contact</th>
                            <th>Email Address</th>
                            <th class="text-center pe-4" style="width: 180px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($students_result && $students_result->num_rows > 0): 
                            while($s = $students_result->fetch_assoc()): 
                                
                                $roll = !empty($s['roll_no']) ? $s['roll_no'] : $s['id'];
                                $name = !empty($s['student_name']) ? $s['student_name'] : 'N/A';
                                $father = !empty($s['parent_name']) ? $s['parent_name'] : 'N/A';
                                $phone = !empty($s['parent_phone']) ? $s['parent_phone'] : 'N/A';
                                $email = !empty($s['email']) ? $s['email'] : 'N/A';
                        ?>
                            <tr>
                                <td class="ps-4 fw-bold text-secondary">#<?php echo htmlspecialchars($roll); ?></td>
                                <td class="fw-semibold text-dark"><?php echo htmlspecialchars($name); ?></td>
                                <td><?php echo htmlspecialchars($father); ?></td>
                                <td><?php echo htmlspecialchars($phone); ?></td>
                                <td class="text-muted"><?php echo htmlspecialchars($email); ?></td>
                                <td class="text-center pe-4">
                                    <div class="d-flex gap-2 justify-content-center">
                                        <a href="edit-student.php?id=<?php echo $s['id']; ?>" class="btn btn-action btn-primary fw-medium">
                                            <i class="fas fa-user-edit me-1"></i> Edit
                                        </a>
                                        <a href="class-details.php?class_id=<?php echo $class_id; ?>&action=delete&student_id=<?php echo $s['id']; ?>" 
                                           class="btn btn-action btn-danger fw-medium" 
                                           onclick="return confirm('Kya aap waqai is student ka record delete karna chahte hain?');">
                                            <i class="fas fa-trash-alt me-1"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php 
                            endwhile; 
                        else: 
                        ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="fas fa-user-slash fa-2x mb-2 text-secondary opacity-50"></i>
                                    <p class="mb-0">Is class mein abhi koi student enrolled nahi hai.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>