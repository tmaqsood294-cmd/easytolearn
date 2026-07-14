<?php
require_once '../config/db.php';

// Session verification to secure the page for Teachers only
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit;
}

$teacher_user_id = intval($_SESSION['user_id']); // Integer conversion for security

// FIXED QUERY: Directly matches the user_id assigned by the admin panel setup
$query = "SELECT ta.subject_name, ta.lecture_time, ta.class_id, ta.section, c.class_name 
          FROM teacher_assignments ta 
          LEFT JOIN classes c ON ta.class_id = c.id 
          WHERE ta.teacher_id = $teacher_user_id
          ORDER BY ta.class_id ASC, ta.section ASC";

$lectures = $conn->query($query);

// NEW QUERY: Fetch teacher's own attendance records from admin panel updates
$attendance_query = "SELECT attendance_date, lecture_no, status 
                    FROM teacher_attendance 
                    WHERE teacher_id = $teacher_user_id 
                    ORDER BY attendance_date DESC, lecture_no ASC";
$my_attendance = $conn->query($attendance_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard | School SMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .main-container { padding: 30px; }
        .feature-card { transition: transform 0.2s; }
        .feature-card:hover { transform: translateY(-5px); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-success">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="dashboard.php"><i class="fas fa-chalkboard-teacher me-2"></i>Teacher Portal</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#teacherNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="teacherNav">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link text-white active" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="mark-attendance.php">Mark Attendance</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="enter-marks.php">Enter Marks</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="manage-quizzes.php">Manage Quizzes</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="video-lectures.php">Video Lectures</a></li>
        <li class="nav-item"><a class="nav-link text-warning ms-lg-3" href="../logout.php"><i class="fas fa-sign-out-alt me-1"></i>Logout (<?php echo htmlspecialchars($_SESSION['name']); ?>)</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container main-container">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
        <span class="text-muted">Role: Teacher</span>
    </div>

    <!-- Top Feature Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm p-4 h-100 feature-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="fw-bold mb-0 text-success">Attendance</h5>
                    <i class="fas fa-calendar-check fa-2x text-success opacity-50"></i>
                </div>
                <p class="text-muted small">Daily attendance marking system for your assigned classes.</p>
                <a href="mark-attendance.php" class="btn btn-success btn-sm mt-auto"><i class="fas fa-check me-2"></i>Mark Sheet</a>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm p-4 h-100 feature-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="fw-bold mb-0 text-primary">Exam Marks</h5>
                    <i class="fas fa-edit fa-2x text-primary opacity-50"></i>
                </div>
                <p class="text-muted small">Enter and manage midterm, final, or class test marks.</p>
                <a href="enter-marks.php" class="btn btn-primary btn-sm mt-auto"><i class="fas fa-pen-alt me-2"></i>Enter Marks</a>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm p-4 h-100 feature-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="fw-bold mb-0 text-dark">Online Quizzes</h5>
                    <i class="fas fa-question-circle fa-2x text-warning opacity-50"></i>
                </div>
                <p class="text-muted small">Create new quizzes, add multiple choice questions, and view results.</p>
                <a href="manage-quizzes.php" class="btn btn-warning text-dark btn-sm mt-auto fw-semibold"><i class="fas fa-plus me-2"></i>Manage Quizzes</a>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm p-4 h-100 feature-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="fw-bold mb-0 text-danger">Video Lectures</h5>
                    <i class="fas fa-video fa-2x text-danger opacity-50"></i>
                </div>
                <p class="text-muted small">Upload or link YouTube/Drive lectures for your students.</p>
                <a href="video-lectures.php" class="btn btn-danger btn-sm mt-auto"><i class="fas fa-cloud-upload-alt me-2"></i>Upload Video</a>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Assigned Classes Column -->
        <div class="col-xl-7">
            <div class="card border-0 shadow-sm p-4 h-100 bg-white">
                <h4 class="mb-3 text-dark fw-bold"><i class="fas fa-book text-success me-2"></i>My Assigned Lectures & Classes</h4>
                <div class="table-responsive">
                    <table class="table table-striped align-middle text-center mt-2">
                        <thead class="table-dark">
                            <tr>
                                <th>Subject</th>
                                <th>Class (Section)</th>
                                <th>Lecture Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($lectures && $lectures->num_rows > 0): while($row = $lectures->fetch_assoc()): 
                                $className = !empty($row['class_name']) ? $row['class_name'] : "Class " . $row['class_id'];
                                $sectionName = !empty($row['section']) ? htmlspecialchars($row['section']) : 'A';
                            ?>
                                <tr>
                                    <td><strong class="text-success"><?php echo htmlspecialchars($row['subject_name']); ?></strong></td>
                                    <td>
                                        <span class="badge bg-secondary p-2 fs-6">
                                            <?php echo htmlspecialchars($className) . " (" . htmlspecialchars($sectionName) . ")"; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted text-uppercase fw-semibold">
                                            <i class="far fa-clock me-1 text-success"></i> 
                                            <?php echo !empty($row['lecture_time']) ? htmlspecialchars($row['lecture_time']) : "Not Set"; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">Currently, no classes are assigned to you.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- NEW: My Attendance History Column -->
        <div class="col-xl-5">
            <div class="card border-0 shadow-sm p-4 h-100 bg-white">
                <h4 class="mb-3 text-dark fw-bold"><i class="fas fa-user-check text-primary me-2"></i>My Attendance Log</h4>
                <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                    <table class="table table-hover align-middle text-center">
                        <thead class="table-primary sticky-top">
                            <tr>
                                <th>Date</th>
                                <th>Lecture</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($my_attendance && $my_attendance->num_rows > 0): while($att = $my_attendance->fetch_assoc()): 
                                $status = $att['status'];
                                $badge_class = 'bg-success';
                                if ($status == 'Absent') $badge_class = 'bg-danger';
                                if ($status == 'Leave') $badge_class = 'bg-warning text-dark';
                                
                                $formatted_date = date("d-M-Y", strtotime($att['attendance_date']));
                            ?>
                                <tr>
                                    <td class="fw-semibold small"><?php echo $formatted_date; ?></td>
                                    <td><span class="badge bg-light text-dark border">Lec-<?php echo $att['lecture_no']; ?></span></td>
                                    <td><span class="badge <?php echo $badge_class; ?> px-2 py-1"><?php echo $status; ?></span></td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">
                                        <i class="fas fa-clipboard mb-2 d-block fa-lg text-secondary"></i>
                                        No attendance record marked by Admin yet.
                                    </td>
                                </tr>
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