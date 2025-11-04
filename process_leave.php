<?php
require_once 'includes/session_check.php';
checkAdminManagerAccess();

include_once 'config/Database.php';
include_once 'models/LeaveApplication.php';
include_once 'models/LeaveHistory.php';

if ($_POST) {
    $database = new Database();
    $db = $database->getConnection();
    
    $leaveApp = new LeaveApplication($db);
    $leaveHistory = new LeaveHistory($db);
    
    $leave_id = $_POST['leave_id'];
    $status = $_POST['status'];
    $manager_notes = $_POST['manager_notes'] ?? '';
    
    // Get the leave application details
    $query = "SELECT la.*, u.full_name, u.company_id 
              FROM leave_applications la 
              LEFT JOIN users u ON la.user_id = u.id 
              WHERE la.id = :id AND la.processed = 0";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $leave_id);
    $stmt->execute();
    $leave_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($leave_data) {
        // Prepare data for history
        $history_data = [
            'leave_application_id' => $leave_id,
            'user_id' => $leave_data['user_id'],
            'company' => $leave_data['company'],
            'store_brand' => $leave_data['store_brand'],
            'start_date' => $leave_data['start_date'],
            'end_date' => $leave_data['end_date'],
            'reason' => $leave_data['reason'],
            'day_off_count' => $leave_data['day_off_count'],
            'reliever_name' => $leave_data['reliever_name'],
            'status' => $status,
            'approved_by' => $_SESSION['user_id'],
            'manager_notes' => $manager_notes
        ];
        
        // Move to history
        if ($leaveApp->moveToHistory($history_data)) {
            $_SESSION['success'] = "Leave application " . $status . " successfully!";
        } else {
            $_SESSION['error'] = "Failed to process leave application.";
        }
    } else {
        $_SESSION['error'] = "Leave application not found or already processed.";
    }
} else {
    $_SESSION['error'] = "Invalid request.";
}

header("Location: dashboard.php");
exit();
?>