<?php
require_once 'includes/session_check.php';
checkSession();

include_once 'config/Database.php';
include_once 'models/LeaveApplication.php';

if ($_POST) {
    $database = new Database();
    $db = $database->getConnection();
    
    $leaveApp = new LeaveApplication($db);
    
    $leaveApp->user_id = $_SESSION['user_id'];
    $leaveApp->company = $_POST['company'];
    $leaveApp->store_brand = $_POST['store_brand'];
    $leaveApp->start_date = $_POST['start_date'];
    $leaveApp->end_date = $_POST['end_date'];
    $leaveApp->reason = $_POST['reason'];
    
    // Calculate day off count
    $start = new DateTime($_POST['start_date']);
    $end = new DateTime($_POST['end_date']);
    $interval = $start->diff($end);
    $leaveApp->day_off_count = $interval->days + 1;
    
    $leaveApp->reliever_name = $_POST['reliever_name'];
    
    if ($leaveApp->create()) {
        $_SESSION['success'] = "Leave application submitted successfully!";
    } else {
        $_SESSION['error'] = "Failed to submit leave application.";
    }
} else {
    $_SESSION['error'] = "Invalid request.";
}

header("Location: dashboard.php");
exit();
?>