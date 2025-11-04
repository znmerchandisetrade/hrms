<?php
require_once 'includes/session_check.php';
checkSession();

include_once 'config/Database.php';
include_once 'models/IdRenewalApplication.php';

if ($_POST) {
    $database = new Database();
    $db = $database->getConnection();

    $application_id = $_POST['application_id'] ?? 0;
    $status = $_POST['status'] ?? '';
    $manager_notes = $_POST['manager_notes'] ?? '';
    $hr_notes = $_POST['hr_notes'] ?? '';

    if (!$application_id || !in_array($status, ['approved', 'rejected'])) {
        $_SESSION['error'] = "Invalid request parameters.";
        header("Location: dashboard.php");
        exit();
    }

    try {
        $idRenewal = new IdRenewalApplication($db);
        
        if ($idRenewal->updateStatus($application_id, $status, $_SESSION['user_id'], $manager_notes, $hr_notes)) {
            $_SESSION['success'] = "ID renewal application " . $status . " successfully!";
        } else {
            $_SESSION['error'] = "Failed to update application status. It may have already been processed.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error processing request: " . $e->getMessage();
        error_log("ID Renewal Status Update Error: " . $e->getMessage());
    }
} else {
    $_SESSION['error'] = "Invalid request method.";
}

header("Location: dashboard.php");
exit();
?>