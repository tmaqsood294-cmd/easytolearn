<?php
// Enable error reporting to catch any hidden issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../student-login.php");
    exit;
}

// Yeh bachi ki login user_id hai (e.g., 20)
$user_id = intval($_SESSION['user_id']);

// FIXED QUERY: Ab yeh perfect join karega taake real student_id (e.g., 28) nikle
$student_stmt = $conn->query("SELECT s.id AS real_student_id, s.roll_no, u.name, s.section, s.class_id 
                              FROM students s 
                              JOIN users u ON s.user_id = u.id 
                              WHERE u.id = $user_id LIMIT 1");

$student = $student_stmt->fetch_assoc();

// Agar profile mili toh s.id (28) ko use karenge marks dhoondne ke liye
$student_id = $student['real_student_id'] ?? 0;

// Fetch Marks
$marks_res = null;
if ($student_id > 0) {
    // Ab yeh exact student_id = 28 ke marks dhoondega jo teacher ne dale hain
    $marks_res = $conn->query("SELECT subject_name, total_marks, obtained_marks FROM marks WHERE student_id = $student_id");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Report Card | School SMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .main-container { padding: 30px; }
        .report-header { border-bottom: 3px double #1e3c72; padding-bottom: 15px; margin-bottom: 30px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="dashboard.php"><i class="fas fa-user-graduate me-2"></i>Student Portal</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link text-white" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link text-white active" href="report-card.php">Report Card</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container main-container">
    <div class="card border-0 shadow-sm p-5 col-md-9 mx-auto bg-white">
        <div class="text-center report-header">
            <h2 class="fw-bold text-uppercase text-primary">🏫 School Management System</h2>
            <h4 class="text-muted">Official Academic Achievement Report</h4>
        </div>

        <div class="row mb-4">
            <div class="col-6">
                <p class="mb-1"><strong>Student Name:</strong> <?php echo htmlspecialchars($student['name'] ?? 'N/A'); ?></p>
                <p class="mb-0"><strong>Roll Number:</strong> <?php echo htmlspecialchars($student['roll_no'] ?? 'N/A'); ?></p>
                <p class="mb-0"><strong>Class & Section:</strong> <?php echo htmlspecialchars(($student['class_id'] ?? 'N/A') . " (" . ($student['section'] ?? 'N/A') . ")"); ?></p>
            </div>
            <div class="col-6 text-end">
                <p class="mb-1"><strong>Date of Issue:</strong> <?php echo date('d-M-Y'); ?></p>
                <p class="mb-0"><strong>Status:</strong> <span class="badge bg-success">Compiled</span></p>
            </div>
        </div>

        <table class="table table-bordered align-middle text-center">
            <thead class="table-dark">
                <tr>
                    <th>Subject Name</th>
                    <th>Total Marks</th>
                    <th>Obtained Marks</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $grand_total = 0;
                $grand_obtained = 0;
                if($marks_res && $marks_res->num_rows > 0): 
                    while($mk = $marks_res->fetch_assoc()): 
                        $grand_total += $mk['total_marks'];
                        $grand_obtained += $mk['obtained_marks'];
                        $percentage = ($mk['total_marks'] > 0) ? ($mk['obtained_marks'] / $mk['total_marks']) * 100 : 0;
                ?>
                    <tr>
                        <td class="text-start fw-bold"><?php echo htmlspecialchars($mk['subject_name']); ?></td>
                        <td><?php echo $mk['total_marks']; ?></td>
                        <td><?php echo $mk['obtained_marks']; ?></td>
                        <td class="fw-bold text-primary"><?php echo round($percentage, 1); ?>%</td>
                    </tr>
                <?php 
                    endwhile; 
                    $overall_percentage = ($grand_total > 0) ? ($grand_obtained / $grand_total) * 100 : 0;
                ?>
                    <tr class="table-secondary fw-bold fs-5">
                        <td class="text-start">Grand Total:</td>
                        <td><?php echo $grand_total; ?></td>
                        <td><?php echo $grand_obtained; ?></td>
                        <td class="text-success"><?php echo round($overall_percentage, 1); ?>%</td>
                    </tr>
                <?php else: ?>
                    <tr><td colspan="4" class="text-center text-muted py-3">No examination records found for this student.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>