<?php
// Enable strict error display for debugging on InfinityFree
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database path safety check
$db_path = __DIR__ . '/../config/db.php';
if (!file_exists($db_path)) {
    die("Database Configuration Error: File not found at " . htmlspecialchars($db_path) . ". Please check your admin folder hierarchy.");
}
require_once $db_path;

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$msg = "";

// Form processing with custom error tracking
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_lecture'])) {
    $teacher_id = intval($_POST['teacher_id']);
    $class_id = intval($_POST['class_id']); // Numeric ID conversion for relational accuracy
    $section = mysqli_real_escape_string($conn, $_POST['section']); 
    $subject_name = mysqli_real_escape_string($conn, $_POST['subject_name']);
    $lecture_time = mysqli_real_escape_string($conn, $_POST['lecture_time']);
    $day_of_week = mysqli_real_escape_string($conn, $_POST['day_of_week']);

    if ($teacher_id > 0 && $class_id > 0 && !empty($subject_name)) {
        $query = "INSERT INTO teacher_assignments (teacher_id, class_id, section, subject_name, lecture_time, day_of_week) 
                  VALUES ('$teacher_id', '$class_id', '$section', '$subject_name', '$lecture_time', '$day_of_week')";
                  
        if ($conn->query($query)) {
            $msg = "<div class='alert alert-success shadow-sm'><i class='fas fa-check-circle me-2'></i>Lecture assigned successfully!</div>";
        } else {
            $msg = "<div class='alert alert-danger shadow-sm'>Database Error: " . htmlspecialchars($conn->error) . "</div>";
        }
    } else {
        $msg = "<div class='alert alert-warning shadow-sm'>Please fill all required inputs.</div>";
    }
}

// Delete action
if (isset($_GET['delete_assignment'])) {
    $assignment_id = intval($_GET['delete_assignment']);
    $conn->query("DELETE FROM teacher_assignments WHERE id = $assignment_id");
    $msg = "<div class='alert alert-warning shadow-sm'><i class='fas fa-trash me-2'></i>Lecture schedule removed!</div>";
}

// 1. Safe Teachers fetching logic matching profile requirements
$teachers_list = $conn->query("SELECT id as user_id, name FROM users WHERE role = 'teacher' ORDER BY name ASC");
if (!$teachers_list) {
    die("Database Error while fetching users: " . htmlspecialchars($conn->error));
}

// 2. Dynamic Classes list dropdown fetch (UPDATED: Ordered by your secondary code's natural alphanumeric sorting logic)
$query_classes = "SELECT id, class_name, section FROM classes 
                  ORDER BY 
                    CASE WHEN class_name REGEXP '[0-9]+' THEN 0 ELSE 1 END,
                    CAST(REGEXP_SUBSTR(class_name, '[0-9]+') AS UNSIGNED) ASC, 
                    class_name ASC, 
                    section ASC";

$classes_list = $conn->query($query_classes);
if (!$classes_list) {
    die("Database Error while fetching classes: " . htmlspecialchars($conn->error));
}

// 3. Safe Assignments data fetching matrix structure with explicit Classes description mapping
$assignments = $conn->query("
    SELECT ta.*, ta.id as assignment_id, u.name as teacher_name, c.class_name as real_class_title 
    FROM teacher_assignments ta 
    LEFT JOIN users u ON ta.teacher_id = u.id
    LEFT JOIN classes c ON ta.class_id = c.id
");
if (!$assignments) {
    die("Database Error while fetching teacher_assignments: " . htmlspecialchars($conn->error));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Lectures | School SMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>body { background-color: #f4f6f9; } .main-container { padding: 30px; }</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="dashboard.php"><i class="fas fa-school me-2"></i>School SMS</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link text-white" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="add-student.php">Add/Manage Students</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="manage-teachers.php">Teachers</a></li>
        <li class="nav-item"><a class="nav-link text-white active" href="assign-lecture.php">Assign Lectures</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="fee-status.php">Fees</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container main-container">
    <div class="row">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4">
                <h4 class="text-primary mb-3"><i class="fas fa-calendar-plus me-2"></i>Schedule Lecture</h4>
                <?php echo $msg; ?>
                <form method="POST" action="assign-lecture.php">
                    <input type="hidden" name="assign_lecture" value="1">
                    
                    <div class="mb-2">
                        <label class="form-label">Select Teacher</label>
                        <select name="teacher_id" class="form-select" required>
                            <option value="">-- Choose Instructor --</option>
                            <?php if($teachers_list && $teachers_list->num_rows > 0): while($t = $teachers_list->fetch_assoc()): ?>
                                <option value="<?php echo $t['user_id']; ?>"><?php echo htmlspecialchars($t['name']); ?></option>
                            <?php endwhile; endif; ?>
                        </select>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label">Class Name / Level</label>
                        <select name="class_id" class="form-select" required>
                            <option value="">-- Choose Registered Class --</option>
                            <?php if($classes_list && $classes_list->num_rows > 0): while($cl = $classes_list->fetch_assoc()): ?>
                                <option value="<?php echo $cl['id']; ?>">
                                    <?php echo htmlspecialchars($cl['class_name']) . " (" . htmlspecialchars($cl['section']) . ")"; ?>
                                </option>
                            <?php endwhile; endif; ?>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Section (Verification Only)</label>
                        <select name="section" class="form-select" required>
                            <option value="A">Section A</option>
                            <option value="B">Section B</option>
                            <option value="C">Section C</option>
                        </select>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject_name" class="form-control" placeholder="e.g. Mathematics" required>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label">Lecture Time Slot</label>
                        <input type="text" name="lecture_time" class="form-control" placeholder="e.g. 09:00 AM" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Day of Week</label>
                        <select name="day_of_week" class="form-select" required>
                            <option value="Everyday" selected>Everyday (All Days)</option>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                            <option value="Sunday">Sunday</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold"><i class="fas fa-save me-2"></i>Save Assignment</button>
                </form>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card border-0 shadow-sm p-4">
                <h4 class="mb-3">Active Lecture Timetable Allocation Matrix</h4>
                <div class="table-responsive">
                    <table class="table table-striped align-middle text-center">
                        <thead class="table-dark">
                            <tr>
                                <th>Teacher</th>
                                <th>Class & Section</th>
                                <th>Subject</th>
                                <th>Timing / Day</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($assignments && $assignments->num_rows > 0): while($asg = $assignments->fetch_assoc()): ?>
                                <tr>
                                    <td class="text-start fw-semibold"><?php echo htmlspecialchars($asg['teacher_name'] ?? 'Unknown/Removed'); ?></td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php 
                                            $displayClass = !empty($asg['real_class_title']) ? $asg['real_class_title'] : "Class " . $asg['class_id'];
                                            echo htmlspecialchars($displayClass); 
                                            if (!empty($asg['section'])) {
                                                echo " (" . htmlspecialchars($asg['section']) . ")";
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($asg['subject_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <small class="d-block fw-bold text-dark"><?php echo htmlspecialchars($asg['lecture_time'] ?? 'N/A'); ?></small>
                                        <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($asg['day_of_week'] ?? 'N/A'); ?></span>
                                    </td>
                                    <td>
                                        <a href="assign-lecture.php?delete_assignment=<?php echo $asg['assignment_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove entry?');">
                                            <i class="fas fa-calendar-times"></i> Drop
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="5" class="text-center text-muted py-3">No routine lecture schedules structured yet.</td></tr>
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