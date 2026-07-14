<?php
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit;
}

$msg = "";
$teacher_id = intval($_SESSION['user_id']);

// Retain selected combined string filter (e.g., "10" or "Class 10 (A)")
$selected_class_section = isset($_GET['class_section']) ? mysqli_real_escape_string($conn, trim($_GET['class_section'])) : '';
$selected_subject = isset($_GET['subject_name']) ? mysqli_real_escape_string($conn, trim($_GET['subject_name'])) : '';

// Form Submit handling to save/update marks
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_marks'])) {
    $conn->query("CREATE TABLE IF NOT EXISTS marks (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT, subject_name VARCHAR(100), total_marks INT, obtained_marks INT)");
    
    $total_marks = intval($_POST['total_marks']);
    $subject_name = mysqli_real_escape_string($conn, $_POST['subject_name']);
    
    foreach ($_POST['obtained_marks'] as $student_id => $obtained) {
        $student_id = intval($student_id);
        $obtained = intval($obtained);
        
        // Check if marks already exist for this student and subject
        $check = $conn->query("SELECT id FROM marks WHERE student_id=$student_id AND subject_name='$subject_name'");
        if ($check && $check->num_rows > 0) {
            $conn->query("UPDATE marks SET total_marks=$total_marks, obtained_marks=$obtained WHERE student_id=$student_id AND subject_name='$subject_name'");
        } else {
            $conn->query("INSERT INTO marks (student_id, subject_name, total_marks, obtained_marks) VALUES ($student_id, '$subject_name', $total_marks, $obtained)");
        }
    }
    $msg = "<div class='alert alert-success shadow-sm'><i class='fas fa-check-circle me-2'></i>Marks saved/updated successfully for $subject_name!</div>";
}

// FIX: JOIN classes table to get real Class names and Sections instead of raw IDs like 25
$class_query = "SELECT DISTINCT ta.class_id, c.class_name, c.section 
                FROM teacher_assignments ta
                JOIN classes c ON ta.class_id = c.id 
                WHERE ta.teacher_id = $teacher_id";
$assigned_classes = $conn->query($class_query);

// 2. Load students matching the exact or partial string/ID configuration (Same as Attendance Logic)
$students = null;
if (!empty($selected_class_section)) {
    $search_term = strtolower($selected_class_section);
    
    $students_query = "
        SELECT s.id as student_id, u.name, s.roll_no, 
               m.obtained_marks, m.total_marks
        FROM students s 
        JOIN users u ON s.user_id = u.id
        LEFT JOIN classes c ON s.class_id = c.id
        LEFT JOIN marks m ON s.id = m.student_id AND m.subject_name = '$selected_subject'
        WHERE LOWER(TRIM(s.class_id)) = '$search_term'
        OR LOWER(TRIM(c.class_name)) = '$search_term'
        OR LOWER(TRIM(s.class_id)) LIKE '%$search_term%'
        OR LOWER(TRIM(c.class_name)) LIKE '%$search_term%'
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
    <title>Enter Marks | School SMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>body { background-color: #f4f6f9; } .main-container { padding: 30px; }</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-success">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="dashboard.php"><i class="fas fa-chalkboard-teacher me-2"></i>Teacher Portal</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link text-white" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="mark-attendance.php">Mark Attendance</a></li>
        <li class="nav-item"><a class="nav-link text-white active" href="enter-marks.php">Enter Marks</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container main-container">
    <div class="card border-0 shadow-sm p-4 col-md-10 mx-auto">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="text-success mb-0"><i class="fas fa-edit me-2"></i>Examination Marks Entry</h3>
        </div>
        
        <?php echo $msg; ?>

        <form method="GET" class="row g-3 mb-4 bg-light p-3 rounded border">
            <div class="col-md-4">
                <label class="form-label fw-bold text-secondary">Select Assigned Class</label>
                <select name="class_section" class="form-select" required>
                    <option value="">-- Choose Assigned Class --</option>
                    <?php 
                    if ($assigned_classes && $assigned_classes->num_rows > 0): 
                        while($ac = $assigned_classes->fetch_assoc()): 
                            $class_val = $ac['class_id'];
                            // Display format like: Class 9 (A)
                            $class_display = $ac['class_name'] . ' (' . $ac['section'] . ')';
                            $is_selected = ($selected_class_section == $class_val) ? 'selected' : '';
                    ?>
                        <option value="<?php echo htmlspecialchars($class_val); ?>" <?php echo $is_selected; ?>>
                            <?php echo htmlspecialchars($class_display); ?>
                        </option>
                    <?php 
                        endwhile; 
                    else: 
                    ?>
                        <option value="" disabled>No classes assigned to you yet.</option>
                    <?php endif; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold text-secondary">Subject Name</label>
                <input type="text" name="subject_name" class="form-select" placeholder="e.g. Mathematics, English" value="<?php echo htmlspecialchars($selected_subject); ?>" required list="subjects-list">
                <datalist id="subjects-list">
                    <option value="Mathematics">
                    <option value="English">
                    <option value="Science">
                    <option value="Urdu">
                    <option value="Islamiat">
                </datalist>
            </div>
            
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="fas fa-users me-1"></i> Load Student List</button>
            </div>
        </form>

        <?php if (!empty($selected_class_section) && !empty($selected_subject)): ?>
            <?php
            // Extract existing Total Marks if already saved before
            $existing_total = 100;
            if($students && $students->num_rows > 0) {
                $students->data_seek(0);
                $first_row = $students->fetch_assoc();
                if(!empty($first_row['total_marks'])) {
                    $existing_total = intval($first_row['total_marks']);
                }
                $students->data_seek(0); // reset pointer
            }
            ?>
            <form method="POST">
                <input type="hidden" name="subject_name" value="<?php echo htmlspecialchars($selected_subject); ?>">
                
                <div class="row mb-3 bg-white p-3 rounded border align-items-center">
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-dark">Subject: <span class="text-success"><?php echo htmlspecialchars($selected_subject); ?></span></label>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-secondary text-white fw-bold">Maximum/Total Marks</span>
                            <input type="number" name="total_marks" class="form-control text-center fw-bold" value="<?php echo $existing_total; ?>" min="1" required>
                        </div>
                    </div>
                </div>

                <table class="table table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 15%;">Roll No</th>
                            <th style="width: 45%;">Student Name</th>
                            <th style="width: 40%;">Obtained Marks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($students && $students->num_rows > 0): while($r = $students->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($r['roll_no']); ?></strong></td>
                                <td><?php echo htmlspecialchars($r['name']); ?></td>
                                <td>
                                    <div class="input-group" style="max-width: 200px;">
                                        <input type="number" 
                                               name="obtained_marks[<?php echo $r['student_id']; ?>]" 
                                               class="form-control fw-bold text-success" 
                                               value="<?php echo isset($r['obtained_marks']) ? $r['obtained_marks'] : ''; ?>" 
                                               min="0" 
                                               placeholder="Enter Marks" 
                                               required>
                                        <span class="input-group-text">/ <?php echo $existing_total; ?></span>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="3" class="text-center text-muted py-4"><i class="fas fa-exclamation-triangle text-warning me-1"></i> No students found enrolled in this class.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php if($students && $students->num_rows > 0): ?>
                    <button type="submit" name="save_marks" class="btn btn-success w-100 py-2 fw-bold mt-3"><i class="fas fa-save me-2"></i>Save & Publish Marks</button>
                <?php endif; ?>
            </form>
        <?php else: ?>
            <div class="text-center py-5 border rounded bg-white text-muted shadow-sm">
                <i class="fas fa-file-invoice fs-1 text-success mb-3"></i>
                <h5>Please select a Class and Subject from the filters above and click "Load Student List" to manage exam scores.</h5>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>