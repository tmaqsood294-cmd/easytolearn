<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

$db_path = $_SERVER['DOCUMENT_ROOT'] . '/config/db.php';
if (!file_exists($db_path)) {
    $db_path = '/home/vol1_8/infinityfree.com/if0_42241533/htdocs/config/db.php';
}
require_once $db_path;

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../admin-login.php"); 
    exit;
}

$user_id = intval($_SESSION['user_id']);

// FIXED QUERY: Fetching section directly from classes table (c.section) instead of students table
$student_query = "
    SELECT u.*, s.id AS real_student_id, s.class_id AS raw_class_id, s.roll_no,
           c.class_name AS clean_class_name, c.section AS real_section
    FROM users u 
    LEFT JOIN students s ON u.id = s.user_id 
    LEFT JOIN classes c ON s.class_id = c.id
    WHERE u.id = $user_id LIMIT 1
";
$student_data = $conn->query($student_query);
$student = $student_data ? $student_data->fetch_assoc() : null;

$student_id = intval($student['real_student_id'] ?? 0);
$section = isset($student['real_section']) ? trim($student['real_section']) : '';
$class_display = !empty($student['clean_class_name']) ? trim($student['clean_class_name']) : trim($student['raw_class_id']);

$class_numeric = preg_replace('/[^0-9]/', '', $class_display);
if(empty($class_numeric)) { $class_numeric = $class_display; }

// ========================================================
// AUTOMATED CHALLAN SYSTEM ENGINE
// ========================================================
if ($student_id > 0) {
    $conn->query("CREATE TABLE IF NOT EXISTS fee_challans (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        student_id INT, 
        challan_no VARCHAR(50), 
        fee_month VARCHAR(50), 
        amount INT, 
        due_date DATE, 
        status VARCHAR(20) DEFAULT 'Unpaid'
    )");

    $check_column = $conn->query("SHOW COLUMNS FROM `fee_challans` LIKE 'fee_month'");
    $month_field = 'fee_month';
    
    if ($check_column && $check_column->num_rows === 0) {
        $check_old_column = $conn->query("SHOW COLUMNS FROM `fee_challans` LIKE 'month'");
        if ($check_old_column && $check_old_column->num_rows > 0) {
            $month_field = 'month';
        } else {
            $conn->query("ALTER TABLE `fee_challans` ADD `fee_month` VARCHAR(50) DEFAULT NULL AFTER `challan_no`");
        }
    }

    $current_billing_month = date('F Y'); 
    $check_challan = $conn->query("SELECT id FROM fee_challans WHERE student_id = $student_id AND $month_field = '$current_billing_month' LIMIT 1");
    
    if ($check_challan && $check_challan->num_rows === 0) {
        $generated_no = "SMS-" . date('Ym') . "-" . sprintf("%03d", $student_id);
        $default_amount = 2500; 
        $calculated_due = date('Y-m-10'); 
        
        $conn->query("INSERT INTO fee_challans (student_id, challan_no, $month_field, amount, due_date, status) 
                      VALUES ($student_id, '$generated_no', '$current_billing_month', $default_amount, '$calculated_due', 'Unpaid')");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal | School SMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { background-color: #212529; min-height: 100vh; color: white; }
        .nav-link { color: #adb5bd; }
        .nav-link.active, .nav-link:hover { color: white; background-color: #495057; border-radius: 5px; }
        .card-custom { border: none; border-radius: 12px; transition: transform 0.2s; }
        .card-custom:hover { transform: translateY(-5px); }
    </style>
</head>
<body>

<div class="alert alert-info text-center m-0 rounded-0 py-2">
    <strong>Logged in User ID:</strong> <?php echo $user_id; ?> | <strong>Real Student ID:</strong> <?php echo $student_id; ?>
</div>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 sidebar p-3 d-none d-md-block">
            <h4 class="text-center fw-bold text-primary mb-4"><i class="fas fa-graduation-cap me-2"></i>Portal</h4>
            <hr>
            <ul class="nav flex-column gap-2">
                <li class="nav-item"><a class="nav-link active" href="#overview"><i class="fas fa-home me-2"></i>Overview</a></li>
                <li class="nav-item"><a class="nav-link" href="#timetable"><i class="fas fa-calendar-alt me-2"></i>Timetable</a></li>
                <li class="nav-item"><a class="nav-link" href="#videos"><i class="fas fa-video me-2"></i>Video Lectures</a></li>
                <li class="nav-item"><a class="nav-link" href="#quizzes"><i class="fas fa-pen-alt me-2"></i>Online Quizzes</a></li>
                <li class="nav-item"><a class="nav-link" href="#results"><i class="fas fa-poll-h me-2"></i>Academic Results</a></li>
                <li class="nav-item"><a class="nav-link" href="#fees"><i class="fas fa-receipt me-2"></i>Fee & Challans</a></li>
                <li class="nav-item mt-4"><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="col-md-10 p-4" style="max-height: 100vh; overflow-y: auto;">
            <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded shadow-sm">
                <div>
                    <h3 class="mb-0 text-dark fw-bold">Welcome, <?php echo htmlspecialchars($student['name'] ?? 'Student'); ?>!</h3>
                    <small class="text-muted">
                        Roll No: <?php echo htmlspecialchars($student['roll_no'] ?? 'N/A'); ?> | 
                        Class: <?php echo htmlspecialchars($class_display); ?> <?php echo !empty($section) ? "(Section ".htmlspecialchars($section).")" : ""; ?>
                    </small>
                </div>
                <span class="badge bg-success p-2 fs-6"><i class="fas fa-circle me-1"></i> Online</span>
            </div>

            <!-- Timetable Section -->
            <div id="timetable" class="card card-custom shadow-sm p-4 mb-4 bg-white">
                <h4 class="text-primary fw-bold mb-3"><i class="fas fa-calendar-alt me-2"></i>Your Class Lecture Timetable</h4>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center">
                        <thead class="table-dark">
                            <tr>
                                <th>Day</th>
                                <th>Subject</th>
                                <th>Timing</th>
                                <th>Instructor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $c_clean = mysqli_real_escape_string($conn, $class_display);
                            $c_num_match = mysqli_real_escape_string($conn, $class_numeric);
                            $c_raw_match = mysqli_real_escape_string($conn, $student['raw_class_id']);
                            $sec_clean = mysqli_real_escape_string($conn, $section);

                            $timetable_query = "
                                SELECT ta.*, u.name as teacher_name 
                                FROM teacher_assignments ta
                                LEFT JOIN users u ON ta.teacher_id = u.id
                                WHERE (
                                    LOWER(TRIM(ta.class_id)) = LOWER(TRIM('$c_clean'))
                                    OR ta.class_id LIKE '%$c_clean%'
                                    OR ta.class_id LIKE '%$c_num_match%'
                                    OR ta.class_id = '$c_raw_match'
                                )
                                AND LOWER(TRIM(ta.section)) = LOWER(TRIM('$sec_clean'))
                                ORDER BY FIELD(ta.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), ta.lecture_time ASC
                            ";
                            
                            $timetable = $conn->query($timetable_query);
                            if($timetable && $timetable->num_rows > 0): 
                                while($row = $timetable->fetch_assoc()):
                            ?>
                                <tr>
                                    <td class="fw-bold text-secondary"><?php echo htmlspecialchars($row['day_of_week']); ?></td>
                                    <td><span class="badge bg-info text-dark px-3 py-2 fw-semibold"><?php echo htmlspecialchars($row['subject_name']); ?></span></td>
                                    <td class="fw-bold text-dark"><i class="far fa-clock me-1 text-muted"></i> <?php echo htmlspecialchars($row['lecture_time']); ?></td>
                                    <td><i class="fas fa-user-tie me-2 text-primary"></i><strong><?php echo htmlspecialchars($row['teacher_name'] ?? 'Assigned Instructor'); ?></strong></td>
                                </tr>
                            <?php 
                                endwhile; 
                            else: 
                            ?>
                                <tr>
                                    <td colspan="4" class="text-muted py-4">
                                        <i class="fas fa-calendar-times me-2 text-warning fs-5"></i>
                                        No classes scheduled for <strong>Class <?php echo htmlspecialchars($class_display); ?> (Section <?php echo htmlspecialchars($section); ?>)</strong> yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Videos Section -->
            <div id="videos" class="card card-custom shadow-sm p-4 mb-4 bg-white">
                <h4 class="text-danger fw-bold mb-3"><i class="fas fa-video me-2"></i>Shared Video Lectures</h4>
                <div class="table-responsive">
                    <table class="table table-striped align-middle text-center">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-start">Lecture Title</th>
                                <th>Instructor</th>
                                <th>Link</th>
                                <th>Date Shared</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $videos_query = "
                                SELECT vl.*, u.name AS teacher_name 
                                FROM video_lectures vl
                                LEFT JOIN users u ON vl.teacher_id = u.id
                                WHERE vl.class_id = '$c_raw_match'
                                ORDER BY vl.id DESC
                            ";
                            $videos_result = $conn->query($videos_query);

                            if ($videos_result && $videos_result->num_rows > 0): 
                                while($v_row = $videos_result->fetch_assoc()): 
                            ?>
                                <tr>
                                    <td class="text-start fw-semibold"><?php echo htmlspecialchars($v_row['video_title']); ?></td>
                                    <td><i class="fas fa-user-tie me-2 text-secondary"></i><?php echo htmlspecialchars($v_row['teacher_name'] ?? 'Teacher'); ?></td>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($v_row['video_url']); ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-play me-1"></i> Watch Video
                                        </a>
                                    </td>
                                    <td class="small text-muted">
                                        <?php echo isset($v_row['created_at']) ? date('d M, Y', strtotime($v_row['created_at'])) : 'N/A'; ?>
                                    </td>
                                </tr>
                            <?php 
                                endwhile; 
                            else: 
                            ?>
                                <tr>
                                    <td colspan="4" class="text-muted py-3">
                                        <i class="fas fa-info-circle me-1 text-warning"></i> No video lectures shared for your class yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quizzes Section -->
            <div id="quizzes" class="card card-custom shadow-sm p-4 mb-4 bg-white">
                <h4 class="text-warning fw-bold mb-3"><i class="fas fa-pen-nib me-2"></i>Active & Pending Online Quizzes</h4>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Subject</th>
                                <th>Quiz Topic</th>
                                <th>Questions</th>
                                <th class="text-center">Action / Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $quizzes = $conn->query("
                                SELECT * FROM quizzes 
                                WHERE (LOWER(TRIM(class_id)) = LOWER(TRIM('$c_clean')) OR class_id LIKE '%$c_clean%' OR class_id = '$c_raw_match') 
                                AND LOWER(TRIM(section)) = LOWER(TRIM('$sec_clean'))
                            ");
                            
                            if($quizzes && $quizzes->num_rows > 0): while($q = $quizzes->fetch_assoc()):
                                $q_id = $q['id'];
                                $attempt_check = $conn->query("SELECT * FROM quiz_results WHERE student_id = $student_id AND quiz_id = $q_id");
                                $is_attempted = ($attempt_check && $attempt_check->num_rows > 0);
                                $attempt_data = $is_attempted ? $attempt_check->fetch_assoc() : null;
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($q['subject_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($q['quiz_title']); ?></td>
                                    <td><?php echo $q['total_questions']; ?> MCQs</td>
                                    <td class="text-center">
                                        <?php if($is_attempted): ?>
                                            <span class="badge bg-success px-3 py-2">Attempted (Score: <?php echo $attempt_data['score']."/".$attempt_data['total_score']; ?>)</span>
                                        <?php else: ?>
                                            <a href="take-quiz.php?quiz_id=<?php echo $q['id']; ?>" class="btn btn-sm btn-warning fw-bold shadow-sm"><i class="fas fa-play me-1"></i> Start Test</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="4" class="text-center text-muted py-3">No active online quizzes running for your section.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Results Section -->
            <div id="results" class="card card-custom shadow-sm p-4 mb-4 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="text-success fw-bold m-0"><i class="fas fa-chart-line me-2"></i>Recent Examination Marks</h4>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle text-center">
                        <thead class="table-success">
                            <tr>
                                <th class="text-start">Subject Name</th>
                                <th>Total Marks</th>
                                <th>Obtained Marks</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $dashboard_marks = null;
                            if ($student_id > 0) {
                                $dashboard_marks = $conn->query("SELECT subject_name, total_marks, obtained_marks FROM marks WHERE student_id = $student_id");
                            }
                            
                            if($dashboard_marks && $dashboard_marks->num_rows > 0): 
                                while($mk = $dashboard_marks->fetch_assoc()):
                                    $percentage = ($mk['total_marks'] > 0) ? ($mk['obtained_marks'] / $mk['total_marks']) * 100 : 0;
                            ?>
                                <tr>
                                    <td class="text-start fw-bold text-dark"><?php echo htmlspecialchars($mk['subject_name']); ?></td>
                                    <td><?php echo $mk['total_marks']; ?></td>
                                    <td class="fw-bold text-success"><?php echo $mk['obtained_marks']; ?></td>
                                    <td class="fw-bold text-primary"><?php echo round($percentage, 1); ?>%</td>
                                </tr>
                            <?php 
                                endwhile; 
                            else: 
                            ?>
                                <tr><td colspan="4" class="text-muted py-3"><i class="fas fa-exclamation-circle me-1"></i> No recent examination marks posted.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Fees Section -->
            <div id="fees" class="card card-custom shadow-sm p-4 mb-4 bg-white">
                <h4 class="text-danger fw-bold mb-3"><i class="fas fa-money-check-alt me-2"></i>Fee Management & Online Challans</h4>
                <div class="table-responsive">
                    <table class="table align-middle text-center table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Challan No</th>
                                <th>Billing Month</th>
                                <th>Amount Payable</th>
                                <th>Due Date</th>
                                <th>Payment Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($student_id > 0) {
                                $challans = $conn->query("SELECT * FROM fee_challans WHERE student_id = $student_id ORDER BY id DESC");
                            }
                            
                            if(isset($challans) && $challans && $challans->num_rows > 0): 
                                while($fc = $challans->fetch_assoc()):
                                    $status_bg = (strtolower($fc['status']) === 'paid') ? 'success' : 'danger';
                            ?>
                                <tr>
                                    <td class="fw-bold"><?php echo htmlspecialchars($fc['challan_no']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($fc[$month_field]); ?></strong></td>
                                    <td class="fw-bold text-danger">Rs. <?php echo number_format($fc['amount']); ?>/-</td>
                                    <td><?php echo !empty($fc['due_date']) ? date('d M, Y', strtotime($fc['due_date'])) : 'N/A'; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $status_bg; ?> px-3 py-2 text-uppercase">
                                            <?php echo htmlspecialchars($fc['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <!-- FIXED ACTION: Added explicit routing to challan template page -->
                                        <a href="print-challan.php?challan_id=<?php echo $fc['id']; ?>" target="_blank" class="btn btn-sm btn-primary fw-bold">
                                            <i class="fas fa-print me-1"></i> Print
                                        </a>
                                    </td>
                                </tr>
                            <?php 
                                endwhile; 
                            else: 
                            ?>
                                <tr><td colspan="6" class="text-muted py-3">No challans found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>