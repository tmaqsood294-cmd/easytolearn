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

/**
 * FIXED: Grouping by class_name and section instead of c.id.
 * Yeh query database me majood duplicate name/section entries ko ek hi card me merge kr degi,
 * chahe unki database primary key (id) different hi kyun na ho.
 */
$classes_query = $conn->query("
    SELECT MIN(c.id) as id, c.class_name, c.section, COUNT(DISTINCT s.id) as total_students 
    FROM classes c 
    LEFT JOIN students s ON c.id = s.class_id 
    GROUP BY c.class_name, c.section
    ORDER BY 
        CASE WHEN c.class_name REGEXP '[0-9]+' THEN 0 ELSE 1 END,
        CAST(REGEXP_SUBSTR(c.class_name, '[0-9]+') AS UNSIGNED) ASC, 
        c.class_name ASC, 
        c.section ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Directory | School SMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .main-container { padding: 40px 15px; }
        .class-card { transition: all 0.3s ease; cursor: pointer; text-decoration: none !important; border-radius: 15px !important; }
        .class-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important; border: 1px solid #0d6efd !important; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm sticky-top">
  <div class="container-fluid px-4">
    <a class="navbar-brand fw-bold fs-4 text-uppercase" href="dashboard.php">
        <i class="fas fa-school text-warning me-2"></i>School <span class="text-warning">SMS</span>
    </a>
    <div class="collapse navbar-collapse" id="adminNav">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center gap-1">
        <li class="nav-item"><a class="nav-link text-white-50 px-3" href="dashboard.php"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle text-white active px-3" href="#" id="studentDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-user-graduate me-1 text-warning"></i>Students
          </a>
          <ul class="dropdown-menu dropdown-menu-dark shadow" aria-labelledby="studentDropdown">
            <li><a class="dropdown-item" href="add-student.php"><i class="fas fa-plus me-2 text-primary"></i>Add Student</a></li>
            <li><a class="dropdown-item active" href="view-students.php"><i class="fas fa-users-cog me-2 text-success"></i>Manage Rosters</a></li>
          </ul>
        </li>
        <li class="nav-item"><a class="nav-link text-white-50 px-3" href="manage-teachers.php"><i class="fas fa-chalkboard-teacher me-1"></i>Teachers</a></li>
        <li class="nav-item"><a class="nav-link text-white-50 px-3" href="fee-status.php"><i class="fas fa-money-bill-wave me-1"></i>Fees</a></li>
        <li class="nav-item ms-lg-3"><a class="btn btn-sm btn-danger px-3 fw-bold rounded-pill" href="../logout.php"><i class="fas fa-sign-out-alt me-1"></i>Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container main-container">
    <div class="col-md-11 mx-auto mb-4">
        
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-2 border-bottom">
            <div>
                <h3 class="text-dark fw-bold m-0"><i class="fas fa-chalkboard text-warning me-2"></i>Class Roster Directory</h3>
                <p class="text-muted m-0 small">Click on any class card to open its dedicated student list on a new page.</p>
            </div>
            <div class="mt-3 mt-md-0">
                <a href="add-student.php" class="btn btn-primary fw-bold shadow-sm rounded-3"><i class="fas fa-plus-circle me-2"></i>Admit New Student</a>
            </div>
        </div>

        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 g-3">
            <?php 
            if($classes_query && $classes_query->num_rows > 0): 
                while($c = $classes_query->fetch_assoc()): 
            ?>
                <div class="col">
                    <a href="class-details.php?class_id=<?php echo $c['id']; ?>" class="card h-100 border-0 shadow-sm p-3 text-center class-card">
                        <div class="card-body p-2">
                            <div class="text-secondary opacity-75 mb-2">
                                <i class="fas fa-graduation-cap fa-2x text-primary"></i>
                            </div>
                            <h5 class="card-title fw-bold text-dark mb-1"><?php echo htmlspecialchars($c['class_name']); ?></h5>
                            <?php if(!empty($c['section'])): ?>
                                <span class="badge bg-dark px-2 py-1 mb-2">Sec: <?php echo htmlspecialchars($c['section']); ?></span>
                            <?php else: ?>
                                <div class="mb-2" style="height: 21px;"></div>
                            <?php endif; ?>
                            <p class="card-text text-muted small mb-0 fw-medium"><?php echo $c['total_students']; ?> Students Enrolled</p>
                        </div>
                    </a>
                </div>
            <?php 
                endwhile; 
            else: 
            ?>
                <div class="col-12 text-center text-muted py-4 card border-0 shadow-sm">
                    <i class="fas fa-folder-open fa-2x mb-2 text-secondary"></i>
                    <p class="mb-0">No active classes found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>