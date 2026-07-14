<?php
// Include database connection and templates
include('../config/db.php');
include('../includes/header.php');
include('../includes/sidebar.php');

/**
 * ------------------------------------------------------------------
 * 1. ADMIN ACTIONS: TOGGLE, EDIT & NEW CUSTOM FEE (Paper, Fine, etc.)
 * ------------------------------------------------------------------
 */
// Action A: Toggle Status
if (isset($_POST['toggle_status'])) {
    $challan_id = intval($_POST['fee_id']);
    $current_status = $_POST['current_status'];
    $new_status = (strcasecmp($current_status, 'Unpaid') == 0) ? 'Paid' : 'Unpaid';
    
    $conn->query("UPDATE fee_challans SET status = '$new_status' WHERE id = '$challan_id'");
    $redirect_url = "fee-status.php?" . http_build_query($_GET);
    echo "<script>window.location.href='$redirect_url';</script>";
    exit();
}

// Action B: Update Fee Details
if (isset($_POST['update_fee_details'])) {
    $challan_id = intval($_POST['fee_id']);
    $amount = floatval($_POST['edit_amount']);
    $due_date = mysqli_real_escape_string($conn, $_POST['edit_due_date']);
    $status = mysqli_real_escape_string($conn, $_POST['edit_status']);

    $update_query = "UPDATE fee_challans SET amount='$amount', due_date='$due_date', status='$status' WHERE id='$challan_id'";
    if ($conn->query($update_query)) {
        $redirect_url = "fee-status.php?" . http_build_query($_GET);
        echo "<script>alert('Challan updated successfully!'); window.location.href='$redirect_url';</script>";
        exit();
    }
}

// Action C: Collect Custom Student Fee (Fine, Paper Fee, etc.)
if (isset($_POST['create_custom_fee'])) {
    $student_id = intval($_POST['modal_student_id']);
    $fee_type = mysqli_real_escape_string($conn, $_POST['custom_fee_type']); 
    $custom_month = mysqli_real_escape_string($conn, $_POST['custom_month']);
    $amount = floatval($_POST['custom_amount']);
    $due_date = mysqli_real_escape_string($conn, $_POST['custom_due_date']);
    $status = mysqli_real_escape_string($conn, $_POST['custom_status']);
    
    $challan_no = "CHL-" . date('Ymd') . "-" . rand(100, 999);
    $final_label = $fee_type . " (" . $custom_month . ")";

    $insert_query = "INSERT INTO fee_challans (challan_no, student_id, fee_month, amount, due_date, status) 
                     VALUES ('$challan_no', '$student_id', '$final_label', '$amount', '$due_date', '$status')";
                     
    if ($conn->query($insert_query)) {
        $redirect_url = "fee-status.php?" . http_build_query($_GET);
        echo "<script>alert('$fee_type generated successfully! Challan No: $challan_no'); window.location.href='$redirect_url';</script>";
        exit();
    } else {
        echo "<script>alert('Database Error: Unable to insert invoice.');</script>";
    }
}

// 2. FILTER & ADVANCED SEARCH HANDLING
$where_clauses = [];
if (isset($_GET['class_id']) && $_GET['class_id'] != '') {
    $class_filter = mysqli_real_escape_string($conn, trim($_GET['class_id']));
    if (strpos($class_filter, '|') !== false) {
        list($c_name, $c_sec) = explode('|', $class_filter);
        $where_clauses[] = "LOWER(TRIM(classes.class_name)) = LOWER('$c_name') AND LOWER(TRIM(classes.section)) = LOWER('$c_sec')";
    } else {
        $where_clauses[] = "(students.class_id = '$class_filter' OR classes.id = '$class_filter')";
    }
}
if (isset($_GET['status']) && $_GET['status'] != '') {
    $status_filter = mysqli_real_escape_string($conn, $_GET['status']);
    if ($status_filter === 'Unpaid' || $status_filter === 'Paid') {
        $where_clauses[] = "f1.status = '$status_filter'";
    } else if ($status_filter === 'None') {
        $where_clauses[] = "f1.challan_no IS NULL"; // Filters students with no challans yet
    }
}
if (isset($_GET['search_query']) && trim($_GET['search_query']) != '') {
    $search = mysqli_real_escape_string($conn, trim($_GET['search_query']));
    $where_clauses[] = "(users.name LIKE '%$search%' OR students.roll_no LIKE '%$search%')";
}

$filter_query = "";
if (count($where_clauses) > 0) {
    $filter_query = " WHERE " . implode(" AND ", $where_clauses);
}

$selected_class_id = isset($_GET['class_id']) ? $_GET['class_id'] : '';
$search_val = isset($_GET['search_query']) ? htmlspecialchars($_GET['search_query']) : '';
?>

<div class="main-content" style="padding: 20px; font-family: Arial, sans-serif;">
    <h2>School Fee Dashboard & History Tracker</h2>
    <p style="color: #666; font-size: 14px;">Manage school invoices, add fines/paper fees, and audit historical ledgers.</p>
    <hr>

    <div class="card" style="background: #e9ecef; padding: 15px; margin-bottom: 25px; border-radius: 6px; border: 1px solid #ced4da;">
        <form method="GET" action="" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
            
            <div style="flex: 1; min-width: 200px;">
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">Search Student:</label>
                <input type="text" name="search_query" value="<?php echo $search_val; ?>" placeholder="Enter Name or Roll No..." style="padding: 8px; border-radius: 4px; border: 1px solid #ccc; width: 100%; box-sizing: border-box;">
            </div>

            <div>
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">Select Class / Section:</label>
                <select name="class_id" style="padding: 8px; border-radius: 4px; border: 1px solid #ccc; width: 220px;">
                    <option value="">-- View All Classes --</option>
                    <?php 
                    $query_string = "SELECT class_name, section FROM classes 
                                     GROUP BY class_name, section 
                                     ORDER BY 
                                        CASE WHEN class_name REGEXP '[0-9]+' THEN 0 ELSE 1 END,
                                        CAST(REGEXP_SUBSTR(class_name, '[0-9]+') AS UNSIGNED) ASC, 
                                        class_name ASC, 
                                        section ASC";
                                        
                    $class_fetch = $conn->query($query_string);
                    if($class_fetch) {
                        while($c_row = $class_fetch->fetch_assoc()) {
                            $option_value = $c_row['class_name'] . "|" . $c_row['section'];
                            $selected = ($selected_class_id == $option_value) ? 'selected' : '';
                            $display_class_name = $c_row['class_name'] . " (" . $c_row['section'] . ")";
                            echo "<option value='".htmlspecialchars($option_value)."' $selected>".$display_class_name."</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div>
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">Fee Status:</label>
                <select name="status" style="padding: 8px; border-radius: 4px; border: 1px solid #ccc; width: 140px;">
                    <option value="">All Statuses</option>
                    <option value="Unpaid" <?php if(isset($_GET['status']) && $_GET['status'] == 'Unpaid') echo 'selected'; ?>>Unpaid</option>
                    <option value="Paid" <?php if(isset($_GET['status']) && $_GET['status'] == 'Paid') echo 'selected'; ?>>Paid</option>
                    <option value="None" <?php if(isset($_GET['status']) && $_GET['status'] == 'None') echo 'selected'; ?>>No Challan Yet</option>
                </select>
            </div>
            
            <div style="padding-top: 20px;">
                <button type="submit" style="background: #007bff; color: white; padding: 9px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
                    Search & Filter
                </button>
                <?php if(!empty($selected_class_id) || !empty($search_val) || isset($_GET['status'])): ?>
                    <a href="fee-status.php" style="color: #dc3545; text-decoration: none; font-size: 14px; margin-left: 10px; font-weight: bold;">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <h3>Dynamic Student Fee Sheet</h3>
    <table border="1" cellpadding="10" cellspacing="0" style="width: 100%; text-align: left; border-collapse: collapse; margin-top: 10px;">
        <thead style="background: #343a40; color: white;">
            <tr>
                <th>Challan No</th>
                <th>Student & Class Details</th>
                <th>Parent Name</th>
                <th>Fee Head/Type</th>
                <th>Base Amount</th>
                <th>Due Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // FIXED QUERY: Primary driver table is now 'students' with LEFT JOINs to fetch everyone
            $fetch_fees = "SELECT students.id AS student_table_id, students.roll_no, students.parent_name,
                           users.name AS student_name, 
                           classes.class_name, classes.section,
                           f1.id AS challan_table_id, f1.challan_no, f1.fee_month, f1.amount, f1.due_date, f1.status
                           FROM students
                           INNER JOIN users ON students.user_id = users.id 
                           LEFT JOIN classes ON students.class_id = classes.id 
                           LEFT JOIN fee_challans f1 ON students.id = f1.student_id
                           $filter_query 
                           ORDER BY f1.id DESC, students.id DESC";
                           
            $fees_result = $conn->query($fetch_fees);
            
            if ($fees_result && $fees_result->num_rows > 0) {
                while ($fee_row = $fees_result->fetch_assoc()) {
                    $base_amount = $fee_row['amount'];
                    $fid = $fee_row['challan_table_id'];
                    $student_id = $fee_row['student_table_id'];
                    
                    $display_name = !empty($fee_row['student_name']) ? $fee_row['student_name'] : "Student ID: " . $student_id;
                    $display_class = !empty($fee_row['class_name']) ? $fee_row['class_name'] : "N/A";
                    $sec = !empty($fee_row['section']) ? $fee_row['section'] : "-";
                    $roll = !empty($fee_row['roll_no']) ? $fee_row['roll_no'] : "-";
                    $parent = !empty($fee_row['parent_name']) ? $fee_row['parent_name'] : "-";
                    ?>
                    <tr>
                        <td>
                            <?php if(!empty($fee_row['challan_no'])): ?>
                                <strong><?php echo $fee_row['challan_no']; ?></strong>
                            <?php else: ?>
                                <span style="color:#dc3545; font-size:12px; font-style:italic;">No Challan Issued</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong>Name:</strong> <span style="color: #007bff; font-weight:bold;"><?php echo $display_name; ?></span><br>
                            <small><b>Class:</b> <?php echo $display_class; ?>-<?php echo $sec; ?> | <b>Roll No:</b> <span style="background:#ffc107; padding:2px 5px; border-radius:3px; font-weight:bold;"><?php echo $roll; ?></span></small>
                        </td>
                        <td><?php echo $parent; ?></td>
                        <td>
                            <?php if(!empty($fee_row['fee_month'])): ?>
                                <span style="background: #e9ecef; padding:3px 6px; border-radius:4px; font-weight:500;"><?php echo $fee_row['fee_month']; ?></span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: bold; color:#111;">
                            <?php echo !empty($base_amount) ? "Rs. " . number_format($base_amount) : "-"; ?>
                        </td>
                        <td><?php echo !empty($fee_row['due_date']) ? $fee_row['due_date'] : "-"; ?></td>
                        <td>
                            <?php if(!empty($fee_row['status'])): ?>
                                <span style="padding: 4px 8px; border-radius: 4px; color: white; font-size: 13px; font-weight: bold; background: <?php echo (strcasecmp($fee_row['status'], 'Paid') == 0) ? '#28a745' : '#dc3545'; ?>;">
                                    <?php echo $fee_row['status']; ?>
                                </span>
                            <?php else: ?>
                                <span style="padding: 4px 8px; border-radius: 4px; color: #333; font-size: 13px; font-weight: bold; background: #e9ecef; border: 1px dashed #ccc;">
                                    Pending
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if(!empty($fid)): ?>
                                <form method="POST" action="?<?php echo http_build_query($_GET); ?>" style="display:inline;">
                                    <input type="hidden" name="fee_id" value="<?php echo $fid; ?>">
                                    <input type="hidden" name="current_status" value="<?php echo $fee_row['status']; ?>">
                                    <button type="submit" name="toggle_status" style="padding: 4px 7px; font-size: 12px; cursor:pointer; background:#fff; border: 1px solid #6c757d; border-radius:3px;">
                                        Mark <?php echo (strcasecmp($fee_row['status'], 'Unpaid') == 0) ? 'Paid' : 'Unpaid'; ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <div style="margin-top:5px; display:flex; gap:3px;">
                                <?php if(!empty($fid)): ?>
                                    <button onclick="document.getElementById('edit-box-<?php echo $fid; ?>').style.display='block'" style="background:#17a2b8; color:white; border:none; padding:3px 6px; border-radius:3px; cursor:pointer; font-size:11px;">
                                        Edit
                                    </button>
                                <?php endif; ?>
                                
                                <button onclick="openCustomFeeModal('<?php echo $student_id; ?>', '<?php echo addslashes($display_name); ?>')" style="background:#28a745; color:white; border:none; padding:3px 6px; border-radius:3px; cursor:pointer; font-size:11px;">
                                    + Charge
                                </button>

                                <button onclick="openLedgerModal('<?php echo $student_id; ?>', '<?php echo addslashes($display_name); ?>')" style="background:#6f42c1; color:white; border:none; padding:3px 6px; border-radius:3px; cursor:pointer; font-size:11px;">
                                    History
                                </button>
                            </div>
                            
                            <?php if(!empty($fid)): ?>
                                <div id="edit-box-<?php echo $fid; ?>" style="display:none; position:fixed; top:25%; left:35%; background:white; padding:25px; border:2px solid #333; box-shadow:0px 0px 15px rgba(0,0,0,0.5); z-index:100; border-radius:8px; width:320px;">
                                    <h4 style="margin-top:0;">Modify Challan Data</h4>
                                    <form method="POST" action="?<?php echo http_build_query($_GET); ?>">
                                        <input type="hidden" name="fee_id" value="<?php echo $fid; ?>">
                                        <div style="margin-bottom:8px;">
                                            <label>Fee Amount:</label><br>
                                            <input type="number" name="edit_amount" value="<?php echo $base_amount; ?>" style="width:100%; padding:6px;">
                                        </div>
                                        <div style="margin-bottom:8px;">
                                            <label>Due Date:</label><br>
                                            <input type="date" name="edit_due_date" value="<?php echo $fee_row['due_date']; ?>" style="width:100%; padding:6px;">
                                        </div>
                                        <div style="margin-bottom:12px;">
                                            <label>Payment State:</label><br>
                                            <select name="edit_status" style="width:100%; padding:6px;">
                                                <option value="Unpaid" <?php if(strcasecmp($fee_row['status'], 'Unpaid') == 0) echo 'selected'; ?>>Unpaid</option>
                                                <option value="Paid" <?php if(strcasecmp($fee_row['status'], 'Paid') == 0) echo 'selected'; ?>>Paid</option>
                                            </select>
                                        </div>
                                        <button type="submit" name="update_fee_details" style="background:#28a745; color:white; border:none; padding:7px 12px; cursor:pointer; border-radius:4px;">Save Updates</button>
                                        <button type="button" onclick="document.getElementById('edit-box-<?php echo $fid; ?>').style.display='none'" style="background:#6c757d; color:white; border:none; padding:7px 12px; cursor:pointer; border-radius:4px; margin-left:5px;">Cancel</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                echo "<tr><td colspan='8' style='text-align:center; color: #777;'>No student fee logs match your criteria.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<div id="customFeeModal" style="display:none; position:fixed; top:50%; left:50%; transform: translate(-50%, -50%); background:white; padding:20px 25px; border:2px solid #28a745; box-shadow:0px 0px 25px rgba(0,0,0,0.6); z-index:200; border-radius:8px; width:380px; max-height:85vh; overflow-y:auto;">
    <h3 style="margin-top:0; color:#28a745; margin-bottom:5px;">Generate Custom Charge</h3>
    <p style="font-size:14px; margin-bottom:12px; margin-top:0;">Student: <strong id="modal_student_name_label" style="color:#007bff;"></strong></p>
    
    <form method="POST" action="?<?php echo http_build_query($_GET); ?>">
        <input type="hidden" name="modal_student_id" id="modal_student_id">
        
        <div style="margin-bottom:10px;">
            <label style="font-weight:bold; font-size:14px;">Select Fee Type / Charge Head:</label>
            <select name="custom_fee_type" style="width:100%; padding:8px; margin-top:3px; border-radius:4px; border:1px solid #ccc;" required>
                <option value="Paper Fee">Paper Fee</option>
                <option value="Fine">Fine / Penalty</option>
                <option value="Tuition Fee">Monthly Tuition Fee</option>
                <option value="Admission Fee">Admission Fee</option>
                <option value="Sports/Library Fee">Sports & Library Fee</option>
            </select>
        </div>

        <div style="margin-bottom:10px;">
            <label style="font-weight:bold; font-size:14px;">Billing Session/Month:</label>
            <input type="text" name="custom_month" value="<?php echo date('F Y'); ?>" placeholder="e.g. July 2026" style="width:100%; padding:8px; margin-top:3px; border-radius:4px; border:1px solid #ccc;" required>
        </div>

        <div style="margin-bottom:10px;">
            <label style="font-weight:bold; font-size:14px;">Amount (Rs.):</label>
            <input type="number" name="custom_amount" min="1" placeholder="Enter Amount" style="width:100%; padding:8px; margin-top:3px; border-radius:4px; border:1px solid #ccc;" required>
        </div>

        <div style="margin-bottom:10px;">
            <label style="font-weight:bold; font-size:14px;">Due Date:</label>
            <input type="date" name="custom_due_date" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" style="width:100%; padding:8px; margin-top:3px; border-radius:4px; border:1px solid #ccc;" required>
        </div>

        <div style="margin-bottom:15px;">
            <label style="font-weight:bold; font-size:14px;">Initial Status:</label>
            <select name="custom_status" style="width:100%; padding:8px; margin-top:3px; border-radius:4px; border:1px solid #ccc;">
                <option value="Unpaid">Unpaid</option>
                <option value="Paid">Paid</option>
            </select>
        </div>

        <div style="text-align: right;">
            <button type="button" onclick="document.getElementById('customFeeModal').style.display='none'" style="background:#6c757d; color:white; border:none; padding:8px 14px; cursor:pointer; border-radius:4px; margin-right:5px;">Close</button>
            <button type="submit" name="create_custom_fee" style="background:#28a745; color:white; border:none; padding:8px 14px; cursor:pointer; border-radius:4px; font-weight:bold;">Generate Invoice</button>
        </div>
    </form>
</div>

<div id="ledgerModal" style="display:none; position:fixed; top:50%; left:50%; transform: translate(-50%, -50%); background:white; padding:25px; border:2px solid #6f42c1; box-shadow:0px 0px 30px rgba(0,0,0,0.7); z-index:250; border-radius:8px; width:65%; max-height:80vh; overflow-y:auto;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <h3 style="margin:0; color:#6f42c1;">Complete Fee Ledger History</h3>
        <button type="button" onclick="document.getElementById('ledgerModal').style.display='none'" style="background:#dc3545; color:white; border:none; padding:4px 10px; cursor:pointer; border-radius:4px; font-weight:bold;">X</button>
    </div>
    <p style="margin-top:0;">Viewing comprehensive account statements for: <strong id="ledger_student_name" style="color:#007bff; font-size:16px;"></strong></p>
    <hr>
    
    <div id="ledger_content_placeholder"></div>
</div>

<?php
$all_history_query = "SELECT student_id, challan_no, fee_month, amount, due_date, status FROM fee_challans ORDER BY id DESC";
$hist_res = $conn->query($all_history_query);
$ledger_data = [];
if($hist_res && $hist_res->num_rows > 0) {
    while($h_row = $hist_res->fetch_assoc()) {
        $ledger_data[$h_row['student_id']][] = $h_row;
    }
}
?>

<script>
const schoolLedgerMap = <?php echo json_encode($ledger_data); ?>;

function openCustomFeeModal(studentId, studentName) {
    document.getElementById('modal_student_id').value = studentId;
    document.getElementById('modal_student_name_label').innerText = studentName;
    document.getElementById('customFeeModal').style.display = 'block';
}

function openLedgerModal(studentId, studentName) {
    document.getElementById('ledger_student_name').innerText = studentName;
    const container = document.getElementById('ledger_content_placeholder');
    container.innerHTML = ""; 
    
    const logs = schoolLedgerMap[studentId];
    
    if(!logs || logs.length === 0) {
        container.innerHTML = "<p style='color:#777; text-align:center; padding:20px;'>No previous fee entries found for this student.</p>";
    } else {
        let htmlTable = `<table border='1' cellpadding='8' cellspacing='0' style='width:100%; border-collapse:collapse; text-align:left;'>
                            <thead style='background:#6f42c1; color:white;'>
                                <tr>
                                    <th>Challan No</th>
                                    <th>Fee Category / Month</th>
                                    <th>Amount</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>`;
        let totalPaid = 0;
        let totalUnpaid = 0;

        logs.forEach(row => {
            let amt = parseFloat(row.amount);
            let statusColor = (row.status.toLowerCase() === 'paid') ? '#28a745' : '#dc3545';
            
            if(row.status.toLowerCase() === 'paid') totalPaid += amt;
            else totalUnpaid += amt;

            htmlTable += `<tr>
                            <td><strong>${row.challan_no}</strong></td>
                            <td>${row.fee_month}</td>
                            <td>Rs. ${Number(amt).toLocaleString()}</td>
                            <td>${row.due_date}</td>
                            <td><span style='background:${statusColor}; color:white; padding:2px 6px; border-radius:3px; font-weight:bold; font-size:12px;'>${row.status}</span></td>
                          </tr>`;
        });

        htmlTable += `</tbody></table>`;
        
        let summaryHtml = `<div style='display:flex; gap:20px; margin-bottom:15px;'>
                            <div style='background:#d4edda; padding:10px 15px; border-radius:5px; border:1px solid #c3e6cb;'>
                                <span style='color:#155724; font-weight:bold;'>Total Amount Paid:</span> Rs. ${totalPaid.toLocaleString()}
                            </div>
                            <div style='background:#f8d7da; padding:10px 15px; border-radius:5px; border:1px solid #f5c6cb;'>
                                <span style='color:#721c24; font-weight:bold;'>Outstanding Unpaid:</span> Rs. ${totalUnpaid.toLocaleString()}
                            </div>
                          </div>`;
                          
        container.innerHTML = summaryHtml + htmlTable;
    }
    
    document.getElementById('ledgerModal').style.display = 'block';
}
</script>

<?php include('../includes/footer.php'); ?>