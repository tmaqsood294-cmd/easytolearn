<?php
require_once '../config/db.php';

// Session verification to secure the page
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Error-safe queries to prevent page crash if tables are empty
$studentQuery = $conn->query("SHOW TABLES LIKE 'students'");
$studentCount = 0;
if ($studentQuery && $studentQuery->num_rows > 0) {
    $res = $conn->query("SELECT id FROM students");
    if ($res) { $studentCount = $res->num_rows; }
}

$teacherQuery = $conn->query("SHOW TABLES LIKE 'teachers'");
$teacherCount = 0;
if ($teacherQuery && $teacherQuery->num_rows > 0) {
    $res = $conn->query("SELECT id FROM teachers");
    if ($res) { $teacherCount = $res->num_rows; }
}

// FIXED: Now queries 'fee_challans' table dynamically to prevent counting failure
$feesQuery = $conn->query("SHOW TABLES LIKE 'fee_challans'");
$pendingFees = 0;
if ($feesQuery && $feesQuery->num_rows > 0) {
    // Matches 'unpaid', 'pending', or numeric flags safely
    $res = $conn->query("SELECT id FROM fee_challans WHERE LOWER(status) = 'unpaid' OR LOWER(status) = 'pending' OR status = '0'");
    if ($res) { $pendingFees = $res->num_rows; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | School SMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .main-container { padding: 40px 30px; }
        
        /* Modernized Stat Cards */
        .stat-card {
            border: none;
            border-radius: 15px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            text-decoration: none;
            display: block;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.12) !important;
        }
        .stat-card .icon-bg {
            position: absolute;
            right: -20px;
            bottom: -20px;
            font-size: 6rem;
            opacity: 0.15;
            transition: all 0.3s ease;
        }
        .stat-card:hover .icon-bg {
            transform: scale(1.1) rotate(-10deg);
        }

        /* Quick Links Action Buttons */
        .action-btn {
            border-radius: 10px;
            padding: 12px 20px;
            font-weight: 600;
            transition: all 0.2s;
            border-width: 2px;
        }
        .action-btn:hover {
            transform: scale(1.03);
        }
    </style>
</head>
<body>

<!-- Premium Navbar Layout -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm sticky-top">
  <div class="container-fluid px-4">
    <a class="navbar-brand fw-bold fs-4 text-uppercase tracking-wider" href="dashboard.php">
        <i class="fas fa-school text-warning me-2"></i>School <span class="text-warning">SMS</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="adminNav">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center gap-1">
        <li class="nav-item"><a class="nav-link text-white active px-3" href="dashboard.php"><i class="fas fa-tachometer-alt me-1 text-warning"></i>Dashboard</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle text-white-50 px-3" href="#" id="studentDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-user-graduate me-1"></i>Students
          </a>
          <ul class="dropdown-menu dropdown-menu-dark shadow" aria-labelledby="studentDropdown">
            <li><a class="dropdown-menu-item dropdown-item" href="add-student.php"><i class="fas fa-plus me-2 text-primary"></i>Add Student</a></li>
            <li><a class="dropdown-menu-item dropdown-item" href="view-students.php"><i class="fas fa-users-cog me-2 text-success"></i>Manage Rosters</a></li>
          </ul>
        </li>
        <li class="nav-item"><a class="nav-link text-white-50 px-3" href="manage-teachers.php"><i class="fas fa-chalkboard-teacher me-1"></i>Teachers</a></li>
        <li class="nav-item"><a class="nav-link text-white-50 px-3" href="manage-teacher-attendance.php"><i class="fas fa-calendar-check me-1"></i>Attendance</a></li>
        <li class="nav-item"><a class="nav-link text-white-50 px-3" href="fee-status.php"><i class="fas fa-money-bill-wave me-1"></i>Fees</a></li>
        <li class="nav-item"><a class="nav-link text-white-50 px-3" href="manage-passwords.php"><i class="fas fa-key me-1"></i>Passwords</a></li>
        <li class="nav-item ms-lg-3">
            <a class="btn btn-sm btn-danger px-3 fw-bold rounded-pill" href="../logout.php">
                <i class="fas fa-sign-out-alt me-1"></i>Logout (<?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Admin'; ?>)
            </a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container main-container">

    <!-- Dashboard Welcome Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-5 pb-3 border-bottom">
        <div>
            <h2 class="text-dark fw-bold m-0">System Overview</h2>
            <p class="text-muted m-0">Welcome back! Here is what's happening across your institution today.</p>
        </div>
        <div class="mt-2 mt-md-0">
            <span class="badge bg-dark px-3 py-2 fs-6 shadow-sm rounded-pill"><i class="fas fa-user-shield me-2 text-warning"></i>Administrator Control</span>
        </div>
    </div>

    <!-- Counters Grid -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <a href="view-students.php" class="card bg-primary text-white p-4 shadow-sm stat-card h-100">
                <div class="position-relative z-1">
                    <span class="text-white-50 text-uppercase small fw-bold tracking-wider">Total Enrolled</span>
                    <h1 class="display-5 fw-bold my-2"><?php echo $studentCount; ?></h1>
                    <p class="mb-0 small"><i class="fas fa-users-cog me-2"></i>Manage Student Records</p>
                </div>
                <i class="fas fa-user-graduate icon-bg"></i>
            </a>
        </div>
        <div class="col-md-4">
            <a href="manage-teachers.php" class="card bg-success text-white p-4 shadow-sm stat-card h-100">
                <div class="position-relative z-1">
                    <span class="text-white-50 text-uppercase small fw-bold tracking-wider">Active Faculty</span>
                    <h1 class="display-5 fw-bold my-2"><?php echo $teacherCount; ?></h1>
                    <p class="mb-0 small"><i class="fas fa-chalkboard-teacher me-2"></i>View Profile & Roster</p>
                </div>
                <i class="fas fa-chalkboard-teacher icon-bg"></i>
            </a>
        </div>
        <div class="col-md-4">
            <a href="fee-status.php" class="card bg-warning text-dark p-4 shadow-sm stat-card h-100">
                <div class="position-relative z-1">
                    <span class="text-dark-50 text-uppercase small fw-semibold tracking-wider">Unpaid Invoices</span>
                    <h1 class="display-5 fw-bold my-2 text-dark"><?php echo $pendingFees; ?></h1>
                    <p class="mb-0 small fw-medium"><i class="fas fa-file-invoice-dollar me-2"></i>Review Pending Defaulters</p>
                </div>
                <i class="fas fa-file-invoice-dollar icon-bg text-dark"></i>
            </a>
        </div>
    </div>

    <!-- Quick Management Matrix -->
    <div class="card border-0 shadow-sm p-4 bg-white rounded-4">
        <div class="mb-4 text-start">
            <h5 class="fw-bold text-dark m-0"><i class="fas fa-th-large text-warning me-2"></i>Quick Management Links</h5>
            <small class="text-muted">Direct short-cuts to frequently used administration control forms.</small>
        </div>
        <div class="row g-3">
            <div class="col-sm-6 col-md-4 col-xl-2 flex-fill">
                <a href="add-student.php" class="btn btn-outline-primary action-btn w-100 h-100 d-flex flex-column align-items-center justify-content-center gap-2">
                    <i class="fas fa-plus-circle fa-lg"></i> Admit Student
                </a>
            </div>
            <div class="col-sm-6 col-md-4 col-xl-2 flex-fill">
                <a href="view-students.php" class="btn btn-outline-success action-btn w-100 h-100 d-flex flex-column align-items-center justify-content-center gap-2">
                    <i class="fas fa-users-cog fa-lg"></i> Manage Students
                </a>
            </div>
            <div class="col-sm-6 col-md-4 col-xl-2 flex-fill">
                <a href="manage-teachers.php" class="btn btn-outline-secondary action-btn w-100 h-100 d-flex flex-column align-items-center justify-content-center gap-2">
                    <i class="fas fa-users fa-lg"></i> Faculty Desk
                </a>
            </div>
            <div class="col-sm-6 col-md-4 col-xl-2 flex-fill">
                <a href="manage-teacher-attendance.php" class="btn btn-outline-info action-btn w-100 h-100 d-flex flex-column align-items-center justify-content-center gap-2 text-dark">
                    <i class="fas fa-user-check fa-lg"></i> Log Attendance
                </a>
            </div>
            <div class="col-sm-6 col-md-4 col-xl-2 flex-fill">
                <a href="fee-status.php" class="btn btn-outline-warning action-btn w-100 h-100 d-flex flex-column align-items-center justify-content-center gap-2 text-dark">
                    <i class="fas fa-wallet fa-lg"></i> Fee Records
                </a>
            </div>
            <div class="col-sm-6 col-md-4 col-xl-2 flex-fill">
                <a href="manage-passwords.php" class="btn btn-outline-danger action-btn w-100 h-100 d-flex flex-column align-items-center justify-content-center gap-2">
                    <i class="fas fa-shield-alt fa-lg"></i> Passwords
                </a>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>