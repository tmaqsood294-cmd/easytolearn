<?php
// Error reporting on kar rahe hain taake koi bhi masla ho toh screen par error dikhe
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Session security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit;
}

$teacher_user_id = (int)$_SESSION['user_id'];
$message = "";

// ------------------------------------
// HANDLE QUIZ DELETE (NEW OPTION)
// ------------------------------------
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    
    // Security check: Yeh confirm karne ke liye ki yeh quiz isi teacher ki hai
    $check_stmt = $conn->prepare("SELECT q.id FROM quizzes q 
                                  LEFT JOIN teacher_assignments ta ON q.class_id = ta.class_id 
                                  WHERE q.id = ? AND ta.teacher_id = ?");
    $check_stmt->bind_param("ii", $delete_id, $teacher_user_id);
    $check_stmt->execute();
    $check_res = $check_stmt->get_result();

    if ($check_res->num_rows > 0) {
        $delete_stmt = $conn->prepare("DELETE FROM quizzes WHERE id = ?");
        $delete_stmt->bind_param("i", $delete_id);
        if ($delete_stmt->execute()) {
            $message = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                            <i class='fas fa-trash-alt me-2'></i>Quiz successfully deleted!
                            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                        </div>";
        }
        $delete_stmt->close();
    } else {
        $message = "<div class='alert alert-warning'>Aapko yeh quiz delete karne ki ijazat nahi hai.</div>";
    }
    $check_stmt->close();
}

// ------------------------------------
// HANDLE QUIZ SUBMISSION
// ------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_quiz'])) {
    $quiz_title = trim($_POST['quiz_title']);
    $class_id = (int)$_POST['class_id'];
    $subject_name = trim($_POST['subject_name']);
    $total_questions = (int)$_POST['total_questions'];

    if (!empty($quiz_title) && !empty($subject_name) && $class_id > 0 && $total_questions > 0) {
        
        $class_stmt = $conn->prepare("SELECT section FROM classes WHERE id = ?");
        $class_stmt->bind_param("i", $class_id);
        $class_stmt->execute();
        $class_res = $class_stmt->get_result();
        
        // Force fallback for section if class_id is 10 or 0 but not in classes table
        $section = 'A'; 
        if ($class_res && $class_row = $class_res->fetch_assoc()) {
            $section = $class_row['section'];
        }

        $insert_stmt = $conn->prepare("INSERT INTO quizzes (class_id, section, subject_name, quiz_title, total_questions) VALUES (?, ?, ?, ?, ?)");
        $insert_stmt->bind_param("isssi", $class_id, $section, $subject_name, $quiz_title, $total_questions);
        
        if ($insert_stmt->execute()) {
            $message = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                            <i class='fas fa-check-circle me-2'></i>Quiz successfully created! Niche se 'Add Questions' par click karein.
                            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                        </div>";
        } else {
            $message = "<div class='alert alert-danger'><strong>Database Error:</strong> " . $conn->error . "</div>";
        }
        $insert_stmt->close();
        $class_stmt->close();
    } else {
        $message = "<div class='alert alert-warning'>Baraye meharbani saaray fields sahi se fill karein.</div>";
    }
}

// ------------------------------------
// FETCH ASSIGNED CLASSES (FOR DROPDOWN)
// ------------------------------------
$classes_query = "SELECT DISTINCT ta.class_id, c.class_name, ta.section 
                  FROM teacher_assignments ta 
                  LEFT JOIN classes c ON ta.class_id = c.id 
                  WHERE ta.teacher_id = $teacher_user_id";
$classes_result = $conn->query($classes_query);

// ------------------------------------
// FETCH EXISTING QUIZZES (FOR TABLE WITH ACTIONS)
// ------------------------------------
$quizzes_query = "SELECT DISTINCT q.*, c.class_name 
                  FROM quizzes q 
                  LEFT JOIN classes c ON q.class_id = c.id 
                  LEFT JOIN teacher_assignments ta ON ta.class_id = q.class_id
                  WHERE ta.teacher_id = $teacher_user_id 
                  ORDER BY q.id DESC";
$quizzes_result = $conn->query($quizzes_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Quizzes | Teacher Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-success">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="dashboard.php"><i class="fas fa-chalkboard-teacher me-2"></i>Teacher Portal</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link text-white" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link text-white active" href="manage-quizzes.php">Manage Quizzes</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="video-lectures.php">Video Lectures</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container my-5">
    <h2><i class="fas fa-question-circle text-warning me-2"></i>Manage Online Quizzes</h2>
    <p class="text-muted">Create online test structures, add questions, or delete quizzes.</p>
    
    <?php echo $message; ?>

    <div class="row g-4">
        <!-- Add Quiz Form -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4">
                <h5 class="fw-bold text-success mb-3">Create New Quiz</h5>
                <form action="manage-quizzes.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label">Quiz Title / Topic</label>
                        <input type="text" name="quiz_title" class="form-control" placeholder="e.g., Chapter 1 Surprise Test" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject Name</label>
                        <input type="text" name="subject_name" class="form-control" placeholder="e.g., Math" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Select Class</label>
                        <select name="class_id" class="form-select" required>
                            <option value="">-- Choose Class --</option>
                            <?php 
                            if($classes_result && $classes_result->num_rows > 0){
                                while($row = $classes_result->fetch_assoc()){ 
                                    // Dropdown Force Override
                                    if (intval($row['class_id']) === 10 || intval($row['class_id']) === 0) {
                                        $dropdownClassName = '10';
                                    } else {
                                        $dropdownClassName = !empty($row['class_name']) ? $row['class_name'] : $row['class_id'];
                                    }
                                    $dropdownSection = !empty($row['section']) ? $row['section'] : 'A';
                                    
                                    echo "<option value='".$row['class_id']."'>".htmlspecialchars($dropdownClassName . " (" . $dropdownSection . ")")."</option>";
                                } 
                            } else {
                                echo "<option value=''>No classes assigned to you</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Total Questions</label>
                        <input type="number" name="total_questions" class="form-control" min="1" placeholder="e.g., 5" required>
                    </div>
                    <button type="submit" name="add_quiz" class="btn btn-success w-100"><i class="fas fa-plus me-2"></i>Create Quiz Structure</button>
                </form>
            </div>
        </div>

        <!-- Quizzes List Table -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm p-4">
                <h5 class="fw-bold text-dark mb-3">Existing Quizzes</h5>
                <div class="table-responsive">
                    <table class="table table-striped align-middle text-center">
                        <thead class="table-dark">
                            <tr>
                                <th>Quiz Title</th>
                                <th>Subject</th>
                                <th>Class</th>
                                <th>Total Questions</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($quizzes_result && $quizzes_result->num_rows > 0): while($row = $quizzes_result->fetch_assoc()): 
                                // Table Force Override Logic
                                if (intval($row['class_id']) === 10 || intval($row['class_id']) === 0) {
                                    $tableClassName = '10';
                                } else {
                                    $tableClassName = !empty($row['class_name']) ? $row['class_name'] : $row['class_id'];
                                }
                                $tableSectionName = !empty($row['section']) ? htmlspecialchars($row['section']) : 'A';
                            ?>
                                <tr>
                                    <td class="text-start fw-semibold"><?php echo htmlspecialchars($row['quiz_title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($tableClassName . " (" . $tableSectionName . ")"); ?></span></td>
                                    <td><span class="badge bg-info text-dark"><?php echo (int)$row['total_questions']; ?> Qs</span></td>
                                    <td>
                                        <!-- Add Questions Button -->
                                        <a href="add-questions.php?quiz_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary me-1">
                                            <i class="fas fa-plus-circle"></i> Add
                                        </a>
                                        <!-- Delete Button with JavaScript confirmation alert -->
                                        <a href="manage-quizzes.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Kya aap waqai is quiz ko delete karna chahte hain?');">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="5" class="text-muted py-3">No quizzes created yet.</td>
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