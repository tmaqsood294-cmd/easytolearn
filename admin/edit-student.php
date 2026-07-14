<?php
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = '';
$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($student_id <= 0) {
    die("Invalid Student Selection.");
}

// 1. Fetch Student Current Data
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    die("Student record not found.");
}

// 2. Process Form Update Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name  = trim($_POST['first_name']);
    $father_name = trim($_POST['father_name']);
    $contact     = trim($_POST['contact']);
    $email       = trim($_POST['email']);
    $password    = trim($_POST['password']);

    if (!empty($first_name) && !empty($email)) {
        
        // Agar admin ne naya password type kiya hai to use badlein, warna purana hi rehne dein
        if (!empty($password)) {
            // Agar aap md5 ya password_hash use kar rahe hain to yahan change kar sakte hain
            // Filhal safe simple string handling hai (agar encrypted chahiye to password_hash lagayein)
            $enc_password = password_hash($password, PASSWORD_BCRYPT); 
            
            $update_stmt = $conn->prepare("UPDATE students SET first_name=?, father_name=?, contact=?, email=?, password=? WHERE id=?");
            $update_stmt->bind_param("sssssi", $first_name, $father_name, $contact, $email, $enc_password, $student_id);
        } else {
            // Password blank chora hai matlab password change nahi karna
            $update_stmt = $conn->prepare("UPDATE students SET first_name=?, father_name=?, contact=?, email=? WHERE id=?");
            $update_stmt->bind_param("ssssi", $first_name, $father_name, $contact, $email, $student_id);
        }

        if ($update_stmt->execute()) {
            $message = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i> Student profile updated successfully!</div>';
            
            // Reload updated local data
            $student['first_name']  = $first_name;
            $student['father_name'] = $father_name;
            $student['contact']     = $contact;
            $student['email']       = $email;
        } else {
            $message = '<div class="alert alert-danger">Error updating data. Email might already exist.</div>';
        }
        $update_stmt->close();
    } else {
        $message = '<div class="alert alert-warning">Name and Email fields are strictly required!</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student Profile | School SMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .main-container { padding: 40px 15px; }
        .form-card { border-radius: 15px !important; }
    </style>
</head>
<body>

<div class="container main-container">
    <div class="col-md-6 mx-auto">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="text-dark fw-bold m-0"><i class="fas fa-user-edit text-primary me-2"></i>Edit Student Profile</h4>
            <a href="view-students.php" class="btn btn-outline-secondary btn-sm fw-bold rounded-3"><i class="fas fa-arrow-left me-1"></i> Back</a>
        </div>

        <?php echo $message; ?>

        <div class="card border-0 shadow-sm p-4 form-card">
            <form action="" method="POST">
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Student Full Name</label>
                    <input type="text" class="form-control rounded-3" name="first_name" value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Father's Name</label>
                    <input type="text" class="form-control rounded-3" name="father_name" value="<?php echo htmlspecialchars($student['father_name'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Contact Number</label>
                    <input type="text" class="form-control rounded-3" name="contact" value="<?php echo htmlspecialchars($student['contact'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Address</label>
                    <input type="email" class="form-control rounded-3" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Account Password</label>
                    <input type="password" class="form-control rounded-3" name="password" placeholder="Leave blank to keep old password">
                    <div class="form-text text-muted small">Sirf tabhi bharein agar password change karna ho.</div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary fw-bold py-2 rounded-3 shadow-sm"><i class="fas fa-save me-2"></i>Save Record Changes</button>
                </div>
            </form>
        </div>

    </div>
</div>

</body>
</html>