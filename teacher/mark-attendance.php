<?php
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit;
}

$msg = "";
// FIX: Date ab dynamically filter ya input se aayegi (Default: Aaj ki tareekh)
$date = isset($_GET['attendance_date']) ? mysqli_real_escape_string($conn, $_GET['attendance_date']) : date('Y-m-d');
$current_month = date('m', strtotime($date));
$current_year = date('Y', strtotime($date));

$teacher_id = intval($_SESSION['user_id']);

// URL se parameters sanitize kar ke nikalna
$selected_class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$selected_section = isset($_GET['section']) ? mysqli_real_escape_string($conn, trim($_GET['section'])) : '';

// Form Submit handling to save attendance
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_attendance_btn'])) {
    $conn->query("CREATE TABLE IF NOT EXISTS attendance (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT, status VARCHAR(20), date DATE)");
    
    // Form se jo date submit hui usko secure karna
    $posted_date = mysqli_real_escape_string($conn, $_POST['attendance_date']);
    
    if (isset($_POST['attendance']) && is_array($_POST['attendance'])) {
        foreach ($_POST['attendance'] as $student_id => $status) {
            $student_id = intval($student_id);
            $status = mysqli_real_escape_string($conn, $status);
            
            $check = $conn->query("SELECT id FROM attendance WHERE student_id=$student_id AND date='$posted_date'");
            if ($check && $check->num_rows > 0) {
                $conn->query("UPDATE attendance SET status='$status' WHERE student_id=$student_id AND date='$posted_date'");
            } else {
                $conn->query("INSERT INTO attendance (student_id, status, date) VALUES ($student_id, '$status', '$posted_date')");
            }
        }
        $msg = "<div class='alert alert-success shadow-sm'><i class='fas fa-check-circle me-2'></i>Attendance updated successfully for $posted_date!</div>";
        $date = $posted_date; // Update current view to the submitted date
    }
}

// 1. Fetch assigned Classes AND Sections for THIS specific teacher
$class_query = "SELECT DISTINCT ta.class_id, ta.section, c.class_name 
                FROM teacher_assignments ta
                INNER JOIN classes c ON ta.class_id = c.id 
                WHERE ta.teacher_id = $teacher_id";
$assigned_classes = $conn->query($class_query);

// 2. Load students matching the exact Class ID and section
$students = null;
if ($selected_class_id > 0) {
    $students_query = "
        SELECT s.id as student_id, u.name, s.roll_no, c.class_name, c.section
        FROM students s 
        JOIN users u ON s.user_id = u.id
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE s.class_id = $selected_class_id
        " . (!empty($selected_section) ? " AND (c.section = '$selected_section' OR s.section = '$selected_section')" : "") . "
        ORDER BY s.roll_no ASC
    ";
    $students = $conn->query($students_query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance | School SMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; } 
        .main-container { padding: 30px; }
        .stats-badge { font-size: 0.78rem; padding: 4px 8px; border-radius: 40px; font-weight: 600; cursor: pointer; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-success">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="dashboard.php"><i class="fas fa-chalkboard-teacher me-2"></i>Teacher Portal</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link text-white" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link text-white active" href="mark-attendance.php">Mark Attendance</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="enter-marks.php">Enter Marks</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container main-container">
    <div class="card border-0 shadow-sm p-4 col-md-10 mx-auto">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="text-success mb-0"><i class="fas fa-calendar-check me-2"></i>Daily Attendance Sheet</h3>
            <span class="badge bg-dark px-3 py-2 fs-6">Selected Date: <?php echo $date; ?></span>
        </div>
        
        <?php echo $msg; ?>

        <!-- Filter Area: Date picker aur Class selector dono ko aik hi line me set kiya -->
        <div class="row g-3 mb-4 bg-light p-3 rounded border">
            <div class="col-md-6">
                <label class="form-label fw-bold text-secondary">1. Attendance Date</label>
                <input type="date" id="attendance_date_picker" class="form-control" value="<?php echo $date; ?>" 
                       onchange="window.location.href='mark-attendance.php?class_id=<?php echo $selected_class_id; ?>&section=<?php echo $selected_section; ?>&attendance_date='+this.value">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold text-secondary">2. Select Your Assigned Class & Section</label>
                <select name="class_select" class="form-select" onchange="if(this.value){ var opt=this.value.split('|'); var dt=document.getElementById('attendance_date_picker').value; window.location.href='mark-attendance.php?class_id='+opt[0]+'&section='+opt[1]+'&attendance_date='+dt; }" required>
                    <option value="">-- Choose Assigned Class (Section) --</option>
                    <?php 
                    if ($assigned_classes && $assigned_classes->num_rows > 0): 
                        while($ac = $assigned_classes->fetch_assoc()): 
                            $c_id = $ac['class_id'];
                            $sec = $ac['section'];
                            $c_name = $ac['class_name'];
                            $val_string = "$c_id|$sec";
                            $is_selected = ($selected_class_id == $c_id && $selected_section == $sec) ? 'selected' : '';
                    ?>
                        <option value="<?php echo $val_string; ?>" <?php echo $is_selected; ?>>
                            <?php echo htmlspecialchars($c_name) . " (" . htmlspecialchars($sec) . ")"; ?>
                        </option>
                    <?php 
                        endwhile; 
                    else: 
                    ?>
                        <option value="" disabled>No classes assigned to you yet.</option>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <?php if ($selected_class_id > 0): ?>
            <form method="POST" action="mark-attendance.php?class_id=<?php echo $selected_class_id; ?>&section=<?php echo $selected_section; ?>">
                <!-- Post method me target date pass krne k liye input hidden -->
                <input type="hidden" name="attendance_date" value="<?php echo $date; ?>">
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle border">
                        <thead class="table-dark text-center">
                            <tr>
                                <th style="width: 12%;">Roll No</th>
                                <th style="width: 38%;" class="text-start">Student Name</th>
                                <th style="width: 25%;">This Month Summary</th>
                                <th style="width: 25%;">Status / Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if($students && $students->num_rows > 0): 
                                while($r = $students->fetch_assoc()): 
                                    $s_id = $r['student_id'];
                                    
                                    $absent_count = 0;
                                    $leave_count = 0;
                                    
                                    // Pora month ka counts summarize krna
                                    $stats_q = "SELECT status, COUNT(*) as total FROM attendance 
                                                WHERE student_id = $s_id 
                                                AND MONTH(date) = '$current_month' 
                                                AND YEAR(date) = '$current_year' 
                                                GROUP BY status";
                                    $stats_res = $conn->query($stats_q);
                                    if($stats_res) {
                                        while($stat = $stats_res->fetch_assoc()) {
                                            if(strtolower($stat['status']) == 'absent') $absent_count = $stat['total'];
                                            if(strtolower($stat['status']) == 'leave') $leave_count = $stat['total'];
                                        }
                                    }
                                    
                                    // Target select ki hui date ka status check krna
                                    $today_status = "Present"; 
                                    $today_check = $conn->query("SELECT status FROM attendance WHERE student_id=$s_id AND date='$date'");
                                    if($today_check && $today_check->num_rows > 0) {
                                        $today_status = $today_check->fetch_assoc()['status'];
                                    }
                            ?>
                                <tr>
                                    <td class="text-center"><span class="badge bg-secondary p-2 fs-6 w-100"><?php echo htmlspecialchars($r['roll_no']); ?></span></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($r['name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($r['class_name'])." (".htmlspecialchars($r['section']).")"; ?></small>
                                        <br>
                                        <button type="button" class="btn btn-sm btn-link text-primary p-0 mt-1 fw-bold text-decoration-none" onclick="openHistoryModal(<?php echo $s_id; ?>, '<?php echo htmlspecialchars($r['name'], ENT_QUOTES); ?>')">
                                            <i class="fas fa-history me-1"></i> View Full Month History
                                        </button>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-2 justify-content-center">
                                            <span class="stats-badge bg-danger text-white"><i class="fas fa-times-circle me-1"></i> Absents: <?php echo $absent_count; ?></span>
                                            <span class="stats-badge bg-warning text-dark"><i class="fas fa-envelope me-1"></i> Leaves: <?php echo $leave_count; ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-2">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="attendance[<?php echo $s_id; ?>]" id="p_<?php echo $s_id; ?>" value="Present" <?php echo (strtolower($today_status) == 'present') ? 'checked' : ''; ?> required>
                                                <label class="form-check-label text-success fw-bold" for="p_<?php echo $s_id; ?>">P</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="attendance[<?php echo $s_id; ?>]" id="a_<?php echo $s_id; ?>" value="Absent" <?php echo (strtolower($today_status) == 'absent') ? 'checked' : ''; ?>>
                                                <label class="form-check-label text-danger fw-bold" for="a_<?php echo $s_id; ?>">A</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="attendance[<?php echo $s_id; ?>]" id="l_<?php echo $s_id; ?>" value="Leave" <?php echo (strtolower($today_status) == 'leave') ? 'checked' : ''; ?>>
                                                <label class="form-check-label text-warning fw-bold" for="l_<?php echo $s_id; ?>">L</label>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php 
                                endwhile; 
                            else: 
                            ?>
                                <tr><td colspan="4" class="text-center text-muted py-4"><i class="fas fa-exclamation-triangle text-warning me-1"></i> No students found enrolled in this class and section.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if($students && $students->num_rows > 0): ?>
                    <button type="submit" name="save_attendance_btn" class="btn btn-success w-100 py-2 fw-bold mt-3 shadow-sm"><i class="fas fa-save me-2"></i>Save/Update Attendance</button>
                <?php endif; ?>
            </form>
        <?php else: ?>
            <div class="text-center py-5 border rounded bg-white text-muted shadow-sm">
                <i class="fas fa-users-cog fs-1 text-success mb-3"></i>
                <h5>Please select your assigned Class & Section from the dropdown above to load your students.</h5>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- POPUP MODAL: Student Monthly History Display Karne K Liye -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold" id="historyModalLabel"><i class="fas fa-user-clock me-2"></i>Attendance History</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 bg-light border-bottom">
                    <strong>Student:</strong> <span id="modal_student_name" class="text-success fw-bold"></span>
                </div>
                <div id="history_table_container" style="max-height: 400px; overflow-y: auto;" class="p-3">
                    <!-- Data AJAX k zariye yahan insert hoga -->
                    <div class="text-center py-3"><i class="fas fa-spinner fa-spin text-success fs-3"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openHistoryModal(studentId, studentName) {
    document.getElementById('modal_student_name').innerText = studentName;
    var container = document.getElementById('history_table_container');
    container.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin text-success fs-3"></i></div>';
    
    // Modal initialize aur open karna
    var myModal = new bootstrap.Modal(document.getElementById('historyModal'));
    myModal.show();
    
    // AJAX Request backend data lane ke liye
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'get-student-history.php?student_id=' + studentId, true);
    xhr.onload = function () {
        if (xhr.status === 200) {
            container.innerHTML = xhr.responseText;
        } else {
            container.innerHTML = '<div class="alert alert-danger">Error loading data.</div>';
        }
    };
    xhr.send();
}
</script>
</body>
</html>