<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Strict admin routing check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../admin-login.php");
    exit;
}

// AUTOMATIC DATABASE FIX
$check_column = $conn->query("SHOW COLUMNS FROM `teacher_attendance` LIKE 'lecture_no'");
if ($check_column && $check_column->num_rows == 0) {
    $conn->query("ALTER TABLE `teacher_attendance` ADD `lecture_no` INT NOT NULL DEFAULT '1' AFTER `attendance_date`");
    $conn->query("ALTER TABLE `teacher_attendance` ADD UNIQUE KEY `unique_teacher_date_lecture` (`teacher_id`, `attendance_date`, `lecture_no`)");
}

$msg = "";
$attendance_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$lecture_no = isset($_GET['lecture']) ? intval($_GET['lecture']) : 1;

// Handle Attendance Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_attendance'])) {
    $attendance_data = isset($_POST['attendance']) ? $_POST['attendance'] : [];
    
    if (!empty($attendance_data)) {
        $success = true;
        $stmt = $conn->prepare("INSERT INTO teacher_attendance (teacher_id, attendance_date, lecture_no, status) 
                                VALUES (?, ?, ?, ?) 
                                ON DUPLICATE KEY UPDATE status = ?");
        
        foreach ($attendance_data as $t_id => $status) {
            $t_id = intval($t_id);
            $stmt->bind_param("isiss", $t_id, $attendance_date, $lecture_no, $status, $status);
            
            if (!$stmt->execute()) {
                $success = false;
            }
        }
        $stmt->close();
        
        if ($success) {
            $msg = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                        <i class='fas fa-check-circle me-2'></i> Attendance saved successfully for Lecture $lecture_no on $attendance_date!
                        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                    </div>";
        } else {
            $msg = "<div class='alert alert-danger' role='alert'>Error saving some attendance records: " . $conn->error . "</div>";
        }
    }
}

// FETCH TEACHERS
$teachers_res = $conn->query("SELECT users.id, users.name, users.email FROM users 
                              INNER JOIN teachers ON users.id = teachers.user_id 
                              WHERE users.role = 'teacher' ORDER BY users.name ASC");

// Fetch existing attendance
$existing_att = [];
$att_res = $conn->query("SELECT teacher_id, status FROM teacher_attendance WHERE attendance_date = '$attendance_date' AND lecture_no = $lecture_no");
if ($att_res) {
    while ($row = $att_res->fetch_assoc()) {
        $existing_att[$row['teacher_id']] = $row['status'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teacher Attendance | Admin Control</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .main-container { padding: 30px; }
        .attendance-radio { scale: 1.2; cursor: pointer; }
        .view-history-btn { font-size: 0.75rem; padding: 2px 8px; margin-top: 4px; display: inline-block; }
    </style>
</head>
<body>

<!-- Updated Navbar with Dashboard Option -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
  <div class="container-fluid px-4">
    <a class="navbar-brand fw-bold" href="dashboard.php"><i class="fas fa-user-shield me-2 text-warning"></i>Admin Console</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="adminNav">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center gap-1">
        <li class="nav-item"><a class="nav-link text-white-50 px-3" href="dashboard.php"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</a></li>
        <li class="nav-item ms-lg-2">
            <a class="btn btn-sm btn-danger px-3 fw-bold rounded-pill" href="../logout.php">
                <i class="fas fa-sign-out-alt me-1"></i>Logout
            </a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container main-container">
    <div class="card border-0 shadow-sm p-4 col-md-11 mx-auto bg-white">
        
        <!-- Header Section with Back to Dashboard Button -->
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4 gap-2">
            <div>
                <h3 class="text-dark fw-bold m-0"><i class="fas fa-user-check text-success me-2"></i>Lecture Attendance</h3>
            </div>
            <div>
                <a href="dashboard.php" class="btn btn-sm btn-secondary fw-semibold shadow-sm px-3">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>

        <div class="row align-items-center mb-4 border-top pt-3">
            <div class="col-lg-12">
                <form method="GET" action="" class="row g-2 justify-content-lg-start align-items-center">
                    <div class="col-auto d-flex align-items-center gap-2">
                        <label class="fw-bold text-secondary text-nowrap m-0">Select Date:</label>
                        <input type="date" name="date" class="form-control form-control-sm" value="<?php echo $attendance_date; ?>" onchange="this.form.submit()">
                    </div>
                    <div class="col-auto d-flex align-items-center gap-2">
                        <label class="fw-bold text-secondary text-nowrap m-0">Lecture:</label>
                        <select name="lecture" class="form-select form-select-sm" onchange="this.form.submit()">
                            <?php 
                            for ($i = 1; $i <= 6; $i++) {
                                $selected = ($lecture_no == $i) ? 'selected' : '';
                                echo "<option value='$i' $selected>Lecture $i</option>";
                            }
                            ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <?php echo $msg; ?>

        <form method="POST" action="manage-teacher-attendance.php?date=<?php echo $attendance_date; ?>&lecture=<?php echo $lecture_no; ?>">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light text-secondary">
                        <tr>
                            <th>Teacher Details</th>
                            <th>Email</th>
                            <th class="text-center" style="width: 12%;">Present</th>
                            <th class="text-center" style="width: 12%;">Absent</th>
                            <th class="text-center" style="width: 12%;">Leave</th>
                            <th class="text-center table-info" style="width: 25%;">Previous Status (Today)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($teachers_res && $teachers_res->num_rows > 0): while($row = $teachers_res->fetch_assoc()): 
                            $t_id = $row['id'];
                            $current_status = isset($existing_att[$t_id]) ? $existing_att[$t_id] : 'Present';

                            // Today's other lectures summary
                            $prev_records = [];
                            $history_res = $conn->query("SELECT lecture_no, status FROM teacher_attendance WHERE teacher_id = $t_id AND attendance_date = '$attendance_date' AND lecture_no != $lecture_no ORDER BY lecture_no ASC");
                            if($history_res) {
                                while($h_row = $history_res->fetch_assoc()) {
                                    $prev_records[] = "L" . $h_row['lecture_no'] . ": " . $h_row['status'];
                                }
                            }
                            $history_text = !empty($prev_records) ? implode(', ', $prev_records) : 'No other lectures';
                        ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['name']); ?></div>
                                    <!-- History Button Trigger -->
                                    <button type="button" class="btn btn-outline-primary btn-sm view-history-btn" onclick="loadHistory(<?php echo $t_id; ?>, '<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>')">
                                        <i class="fas fa-history me-1"></i> Full History
                                    </button>
                                </td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                
                                <td class="text-center">
                                    <input type="radio" class="form-check-input attendance-radio" name="attendance[<?php echo $t_id; ?>]" value="Present" <?php echo ($current_status == 'Present') ? 'checked' : ''; ?>>
                                </td>
                                <td class="text-center">
                                    <input type="radio" class="form-check-input attendance-radio" name="attendance[<?php echo $t_id; ?>]" value="Absent" <?php echo ($current_status == 'Absent') ? 'checked' : ''; ?>>
                                </td>
                                <td class="text-center">
                                    <input type="radio" class="form-check-input attendance-radio" name="attendance[<?php echo $t_id; ?>]" value="Leave" <?php echo ($current_status == 'Leave') ? 'checked' : ''; ?>>
                                </td>
                                <td class="text-center table-info">
                                    <small class="fw-semibold text-secondary"><?php echo $history_text; ?></small>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="6" class="text-center text-muted py-3">No records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if($teachers_res && $teachers_res->num_rows > 0): ?>
                <div class="text-end mt-4">
                    <button type="submit" name="save_attendance" class="btn btn-success fw-bold px-4 shadow-sm">
                        <i class="fas fa-save me-2"></i>Save Lecture <?php echo $lecture_no; ?> Attendance
                    </button>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- ================= HISTORY MODAL (POPUP) ================= -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title fw-bold"><i class="fas fa-history me-2 text-warning"></i>Attendance Log: <span id="modalTeacherName"></span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="max-height: 450px; overflow-y: auto;">
         <div id="historyTableContainer">
             <!-- AJAX Response yahan load hoga -->
             <p class="text-center text-muted m-0">Loading history data...</p>
         </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function loadHistory(teacherId, teacherName) {
    document.getElementById('modalTeacherName').innerText = teacherName;
    document.getElementById('historyTableContainer').innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Fetching log records...</p></div>';
    
    // Open Bootstrap Modal
    var myModal = new bootstrap.Modal(document.getElementById('historyModal'));
    myModal.show();

    // Fetch via AJAX
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            document.getElementById('historyTableContainer').innerHTML = this.responseText;
        }
    };
    xhttp.open("GET", "get-teacher-history.php?teacher_id=" + teacherId, true);
    xhttp.send();
}
</script>
</body>
</html>