<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// BULLETPROOF INFINITYFREE ABSOLUTE PATH REQUIREMENT
$db_path = $_SERVER['DOCUMENT_ROOT'] . '/config/db.php';

if (!file_exists($db_path)) {
    $db_path = '/home/vol1_8/infinityfree.com/if0_42241533/htdocs/config/db.php';
}

require_once $db_path;

// Access Control Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    die("Unauthorized Access!");
}

if (!isset($_GET['challan_id'])) {
    die("Challan ID is missing.");
}

$challan_id = intval($_GET['challan_id']);
$user_id = intval($_SESSION['user_id']);

// STEP 1: Logged-in user ki REAL STUDENT ID confirm karein
$student_find_query = "SELECT id FROM students WHERE user_id = $user_id LIMIT 1";
$student_find_res = $conn->query($student_find_query);
$student_row = $student_find_res ? $student_find_res->fetch_assoc() : null;
$real_student_id = isset($student_row['id']) ? intval($student_row['id']) : 0;

if ($real_student_id === 0) {
    die("Error: Student profile correlation not found.");
}

// STEP 2: Strict record filtering matrix
$query = "SELECT fc.*, u.name as student_name, c.class_name, s.roll_no 
          FROM fee_challans fc
          JOIN students s ON fc.student_id = s.id
          JOIN users u ON s.user_id = u.id
          LEFT JOIN classes c ON s.class_id = c.id
          WHERE fc.id = $challan_id AND fc.student_id = $real_student_id 
          LIMIT 1";

$result = $conn->query($query);
if (!$result || $result->num_rows === 0) {
    die("Challan record not found or access denied.");
}

$challan = $result->fetch_assoc();

// Clean labels parsing
$class_display = !empty($challan['class_name']) ? $challan['class_name'] : 'N/A';
$display_challan_no = !empty($challan['challan_no']) ? $challan['challan_no'] : $challan['id'];
$display_month = !empty($challan['fee_month']) ? $challan['fee_month'] : 'Academic Fee';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Challan_#<?php echo htmlspecialchars($display_challan_no); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #fff; font-family: monospace; }
        .challan-box { border: 2px dashed #000; padding: 20px; max-width: 600px; margin: 30px auto; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>

<div class="container text-center no-print mt-4">
    <button onclick="window.print();" class="btn btn-success shadow-sm"><i class="fas fa-print me-1"></i> Print / Save as PDF</button>
    <a href="dashboard.php" class="btn btn-secondary shadow-sm">Back to Dashboard</a>
</div>

<div class="challan-box shadow-sm">
    <div class="text-center mb-3">
        <h3><strong>SCHOOL MANAGEMENT SYSTEM</strong></h3>
        <h5>FEE CHALLAN (STUDENT COPY)</h5>
    </div>
    <hr>
    <table class="table table-borderless">
        <tr>
            <td><strong>Challan No:</strong> #<?php echo htmlspecialchars($display_challan_no); ?></td>
            <td class="text-end"><strong>Month:</strong> <?php echo htmlspecialchars($display_month); ?></td>
        </tr>
        <tr>
            <td><strong>Roll No:</strong> <?php echo htmlspecialchars($challan['roll_no'] ?? 'N/A'); ?></td>
            <td class="text-end"><strong>Class:</strong> <?php echo htmlspecialchars($class_display); ?></td>
        </tr>
        <tr>
            <td colspan="2"><strong>Student Name:</strong> <?php echo htmlspecialchars($challan['student_name']); ?></td>
        </tr>
        <tr>
            <td colspan="2"><strong>Challan Status:</strong> <span class="badge bg-<?php echo (strtolower($challan['status']) === 'paid') ? 'success' : 'danger'; ?> text-uppercase"><?php echo htmlspecialchars($challan['status']); ?></span></td>
        </tr>
    </table>
    <hr>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-end">Amount (PKR)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Tuition & Academic Evaluation Fee</td>
                <td class="text-end">Rs. <?php echo number_format($challan['amount']); ?>/-</td>
            </tr>
            <tr class="table-light">
                <th>Total Payable Amount:</th>
                <th class="text-end text-danger">Rs. <?php echo number_format($challan['amount']); ?>/-</th>
            </tr>
        </tbody>
    </table>
    <p class="text-muted small mt-2">* Due Date: <strong><?php echo !empty($challan['due_date']) ? date('d-M-Y', strtotime($challan['due_date'])) : 'N/A'; ?></strong>. Processing charges may apply after due date.</p>
    <div class="text-center mt-4">
        <p>______________________<br>Authorized Signature / Stamp</p>
    </div>
</div>

</body>
</html>