<?php
require_once 'includes/session_check.php';
checkSession();

include_once 'config/Database.php';
include_once 'models/IdRenewalApplication.php';

if ($_POST) {
    $database = new Database();
    $db = $database->getConnection();

    $idRenewal = new IdRenewalApplication($db);

    try {
        // Validate required fields
        $required_fields = [
            'current_valid_from', 'current_valid_to', 
            'requested_valid_from', 'requested_valid_to', 
            'reason', 'renewal_type', 'urgency_level'
        ];
        
        $missing_fields = [];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            throw new Exception("Please fill in all required fields: " . implode(', ', $missing_fields));
        }

        // Set properties
        $idRenewal->user_id = $_SESSION['user_id'];
        $idRenewal->current_valid_from = $_POST['current_valid_from'];
        $idRenewal->current_valid_to = $_POST['current_valid_to'];
        $idRenewal->requested_valid_from = $_POST['requested_valid_from'];
        $idRenewal->requested_valid_to = $_POST['requested_valid_to'];
        $idRenewal->reason = trim($_POST['reason']);
        $idRenewal->renewal_type = $_POST['renewal_type'];
        $idRenewal->urgency_level = $_POST['urgency_level'];

        // Additional validation for specific renewal types
        if ($idRenewal->renewal_type == IdRenewalApplication::RENEWAL_LOST || 
            $idRenewal->renewal_type == IdRenewalApplication::RENEWAL_DAMAGED) {
            
            if (strlen($idRenewal->reason) < 20) {
                throw new Exception("For {$idRenewal->renewal_type}, please provide more details about the circumstances (at least 20 characters).");
            }
        }

        if ($idRenewal->create()) {
            $application_id = str_pad($idRenewal->id, 6, '0', STR_PAD_LEFT);
            $_SESSION['success'] = "ID renewal request submitted successfully! Your application ID is IDR-{$application_id}";
            
            // Clear stored form data
            if (isset($_SESSION['form_data'])) {
                unset($_SESSION['form_data']);
            }
            
        } else {
            throw new Exception("Failed to submit ID renewal request. Please try again.");
        }

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        error_log("ID Renewal Submission Error: " . $e->getMessage());
        
        // Store form data for repopulation
        $_SESSION['form_data'] = $_POST;
    }
} else {
    $_SESSION['error'] = "Invalid request method.";
}

header("Location: dashboard.php");
exit();
?>