<?php
include('../config/db.php');

if(isset($_GET['student_id'])) {
    $student_id = intval($_GET['student_id']);
    
    $query = "SELECT * FROM fees WHERE student_id = $student_id ORDER BY due_date DESC";
    $result = $conn->query($query);
    
    echo '<table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse:collapse; text-align:left;">';
    echo '<thead style="background:#eeeded;">';
    echo '<tr><th>Challan No</th><th>Due Date</th><th>Base Fee</th><th>Fine Amount</th><th>Total Paid/Payable</th><th>Status</th></tr>';
    echo '</thead>';
    echo '<tbody>';
    
    if($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $total = $row['amount'] + $row['fine_amount'];
            $status_color = ($row['status'] == 'Paid') ? 'green' : 'red';
            
            echo '<tr>';
            echo '<td><strong>' . $row['challan_number'] . '</strong></td>';
            echo '<td>' . $row['due_date'] . '</td>';
            echo '<td>Rs. ' . number_format($row['amount']) . '</td>';
            echo '<td>Rs. ' . number_format($row['fine_amount']) . '</td>';
            echo '<td><strong>Rs. ' . number_format($total) . '</strong></td>';
            echo '<td style="color:'.$status_color.'; font-weight:bold;">' . $row['status'] . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="6" style="text-align:center; color:#777;">No past billing statements found for this profile.</td></tr>';
    }
    echo '</tbody></table>';
}
?>