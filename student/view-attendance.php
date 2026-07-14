<?php
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch Student Profile
$student_stmt = $conn->query("SELECT s.id as student_id, s.roll_no, u.name FROM students s JOIN users u ON s.user_id = u.id WHERE u.id = $user_id");
$student = $student_stmt->fetch_assoc();
$student_id = $student['student_id'] ?? 0;

// Fetch Attendance Log
$attendance_res = null;
$present_count = 0;
$absent_count = 0;

if ($student_id > 0) {
    $attendance_res = $conn->query("SELECT date, status FROM attendance WHERE student_id = $student_id ORDER BY date DESC");
    
    // Calculate Summary Stats
    $stats_res = $conn->query("SELECT status, COUNT(*) as count FROM attendance WHERE student_id = $student_id GROUP BY status");
    while($row = $stats_res->fetch_assoc()){
        if($row['status'] == 'Present') $present_count = $row['count'];
        if($row['status'] == 'Absent') $absent_count = $row['count'];
    }
}
$total_days = $present_count + $absent_count;
$attendance_percentage = ($total_days > 0) ? ($present_count / $total_days) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance History | School SMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>body { background-color: #f4f6f9; } .main-container { padding: 30px; }</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="dashboard.php"><i class="fas fa-user-graduate me-2"></i>Student Portal</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link text-white" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link text-white active" href="view-attendance.php">View Attendance</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container main-container">
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 text-center bg-white rounded-3 mb-3">
                <h6 class="text-muted text-uppercase fw-bold">Attendance Rate</h6>
                <h2 class="text-primary fw-bold"><?php echo round($attendance_percentage, 1); ?>%</h2>
            </div>
            <div class="card border-0 shadow-sm p-3 text-center bg-success text-white rounded-3 mb-2">
                <p class="mb-0 fw-semibold">Days Present: <?php echo $present_count; ?></p>
            </div>
            <div class="card border-0 shadow-sm p-3 text-center bg-danger text-white rounded-3">
                <p class="mb-0 fw-semibold">Days Absent: <?php echo $absent_count; ?></p>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card border-0 shadow-sm p-4 bg-white rounded-3">
                <h4 class="mb-3 text-dark fw-bold"><i class="fas fa-clock text-warning me-2"></i>Detailed Attendance Logs</h4>
                <table class="table table-striped align-middle">
                    <thead class="table-dark"><tr><th>Session Date</th><th>Status Classification</th></tr></thead>
                    <tbody>
                        <?php if($attendance_res && $attendance_res->num_rows > 0): while($att = $attendance_res->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-bold"><?php echo date('d-M-Y', strtotime($att['date'])); ?></td>
                                <td>
                                    <span class="badge px-3 py-2 <?php echo $att['status'] == 'Present' ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $att['status']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="2" class="text-center text-muted py-3">No tracking logs currently exist for your profile.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>