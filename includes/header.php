<?php
// Session check karna: Agar admin login nahi hai, to wapas login page par bhej dein
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
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
        body { background-color: #f4f6f9; }
        .main-container { padding: 30px; }
        .navbar-brand { font-weight: bold; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="#"><i class="fas fa-school me-2"></i>School SMS</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="adminNavbar">
      <ul class="navbar-collapse navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link active" href="dashboard.php"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle text-white" href="#" id="studentDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-user-graduate me-1"></i>Students
          </a>
          <ul class="dropdown-menu shadow" aria-labelledby="studentDropdown">
            <li><a class="dropdown-menu dropdown-item" href="add-student.php"><i class="fas fa-user-plus me-2"></i>Add Student</a></li>
            <li><a class="dropdown-menu dropdown-item" href="#"><i class="fas fa-list me-2"></i>Manage Students</a></li>
          </ul>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white" href="manage-teachers.php"><i class="fas fa-chalkboard-teacher me-1"></i>Teachers</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white" href="fee-status.php"><i class="fas fa-money-bill-wave me-1"></i>Fees</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-danger ms-lg-3" href="../logout.php"><i class="fas fa-sign-out-alt me-1"></i>Logout (<?php echo $_SESSION['name']; ?>)</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container main-container">