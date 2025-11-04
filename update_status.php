<?php
// update_status.php
require_once 'includes/session_check.php';
checkAdminManagerAccess();

include_once 'config/Database.php';
include_once 'models/LeaveApplication.php';

if ($_POST) {
    $database = new Database();
    $db = $database->getConnection();
    
    $leaveApp = new LeaveApplication($db);
    
    $leave_id = $_POST['leave_id'] ?? null;
    $status = $_POST['status'] ?? null;
    $manager_notes = $_POST['manager_notes'] ?? '';
    
    // Debug logging
    error_log("Update Status - Leave ID: " . $leave_id . ", Status: " . $status);
    
    // Validate inputs
    if (empty($leave_id) || !in_array($status, ['approved', 'rejected'])) {
        $_SESSION['error'] = "Invalid request parameters.";
        header("Location: dashboard.php");
        exit();
    }
    
    try {
        // Start transaction
        $db->beginTransaction();
        
        // 1. Update the leave_applications table directly
        $update_query = "UPDATE leave_applications 
                        SET status = :status, 
                            processed = 1,
                            approved_by_user_id = :approved_by,
                            approved_at = NOW(),
                            manager_notes = :manager_notes
                        WHERE id = :id AND processed = 0";
        
        $stmt = $db->prepare($update_query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":approved_by", $_SESSION['user_id']);
        $stmt->bindParam(":manager_notes", $manager_notes);
        $stmt->bindParam(":id", $leave_id);
        
        $update_result = $stmt->execute();
        $rows_affected = $stmt->rowCount();
        
        error_log("Leave application update - Rows affected: " . $rows_affected);
        
        if (!$update_result || $rows_affected === 0) {
            throw new Exception("No leave application found to update or already processed");
        }
        
        // 2. Insert into leave_history for record keeping
        $history_query = "INSERT INTO leave_history 
                         (leave_application_id, user_id, company, store_brand, 
                          start_date, end_date, reason, day_off_count, reliever_name, 
                          status, approved_by, manager_notes, created_at)
                         SELECT id, user_id, company, store_brand, start_date, end_date, 
                                reason, day_off_count, reliever_name, 
                                :status, :approved_by, :manager_notes, NOW()
                         FROM leave_applications 
                         WHERE id = :id";
        
        $history_stmt = $db->prepare($history_query);
        $history_stmt->bindParam(":status", $status);
        $history_stmt->bindParam(":approved_by", $_SESSION['user_id']);
        $history_stmt->bindParam(":manager_notes", $manager_notes);
        $history_stmt->bindParam(":id", $leave_id);
        $history_result = $history_stmt->execute();
        
        error_log("Leave history insert - Success: " . ($history_result ? 'Yes' : 'No'));
        
        if (!$history_result) {
            throw new Exception("Failed to insert into leave history");
        }
        
        // Commit transaction
        $db->commit();
        
        $_SESSION['success'] = "Leave application " . $status . " successfully!";
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        error_log("Leave status update error: " . $e->getMessage());
        $_SESSION['error'] = "Failed to process leave application: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Invalid request method.";
}

header("Location: dashboard.php");
exit();
?>