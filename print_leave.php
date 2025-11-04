<?php
require_once 'config/Database.php';
require_once 'models/LeaveApplication.php';
require_once 'models/User.php';

// Get leave ID from query parameter
$leave_id = $_GET['id'] ?? 0;

if (!$leave_id) {
    die('Invalid leave application ID');
}

$database = new Database();
$db = $database->getConnection();

$leaveApp = new LeaveApplication($db);
$user = new User($db);

// Get leave details with user information
$query = "SELECT la.*, u.full_name, u.company_id, u.department_id, 
                 c.company_name, d.department_name,
                 approver.full_name as approved_by_name,
                 approver_role.role_name as approved_by_role
          FROM leave_applications la 
          LEFT JOIN users u ON la.user_id = u.id
          LEFT JOIN companies c ON u.company_id = c.id
          LEFT JOIN departments d ON u.department_id = d.id
          LEFT JOIN leave_history lh ON la.id = lh.leave_application_id
          LEFT JOIN users approver ON lh.approved_by = approver.id
          LEFT JOIN roles approver_role ON approver.role_id = approver_role.id
          WHERE la.id = :id 
          LIMIT 1";

$stmt = $db->prepare($query);
$stmt->bindParam(":id", $leave_id);
$stmt->execute();
$leave = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$leave) {
    die('Leave application not found');
}

// Only allow printing for approved/rejected leaves
if ($leave['status'] === 'pending') {
    die('Cannot print pending leave applications');
}

// Format dates
$start_date = date('F j, Y', strtotime($leave['start_date']));
$end_date = date('F j, Y', strtotime($leave['end_date']));
$applied_date = date('F j, Y', strtotime($leave['created_at']));
$processed_date = date('F j, Y g:i A', strtotime($leave['approved_at'] ?? $leave['created_at']));

// Generate HTML for PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Leave Application Form</title>
    <style>
        @page {
            size: A5;
            margin: 15mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .form-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .section {
            margin-bottom: 15px;
        }
        .section-title {
            font-weight: bold;
            background-color: #f8f9fa;
            padding: 5px 10px;
            border-left: 4px solid #007bff;
            margin-bottom: 8px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 10px;
        }
        .info-item {
            margin-bottom: 5px;
        }
        .info-label {
            font-weight: bold;
            color: #555;
        }
        .leave-details {
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
        }
        .signature-section {
            margin-top: 30px;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 40px;
            padding-top: 5px;
        }
        .status-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: bold;
            display: inline-block;
        }
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        .notes-box {
            border: 1px solid #ddd;
            padding: 8px;
            background-color: #f8f9fa;
            border-radius: 3px;
            min-height: 60px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 10px;
            color: #666;
        }
        .print-date {
            text-align: right;
            font-size: 10px;
            color: #666;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="print-date">
        Printed on: ' . date('F j, Y g:i A') . '
    </div>

    <div class="header">
        <div class="company-name">' . htmlspecialchars($leave['company_name'] ?? 'Company Name') . '</div>
        <div class="form-title">LEAVE APPLICATION FORM</div>
    </div>

    <div class="section">
        <div class="section-title">EMPLOYEE INFORMATION</div>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Full Name:</span> ' . htmlspecialchars($leave['full_name']) . '
            </div>
            <div class="info-item">
                <span class="info-label">Department:</span> ' . htmlspecialchars($leave['department_name'] ?? 'N/A') . '
            </div>
            <div class="info-item">
                <span class="info-label">Store/Brand:</span> ' . htmlspecialchars($leave['store_brand']) . '
            </div>
            <div class="info-item">
                <span class="info-label">Date Applied:</span> ' . $applied_date . '
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">LEAVE DETAILS</div>
        <div class="leave-details">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Start Date:</span> ' . $start_date . '
                </div>
                <div class="info-item">
                    <span class="info-label">End Date:</span> ' . $end_date . '
                </div>
                <div class="info-item">
                    <span class="info-label">Number of Days:</span> ' . $leave['day_off_count'] . ' day(s)
                </div>
                <div class="info-item">
                    <span class="info-label">Status:</span> 
                    <span class="status-badge ' . ($leave['status'] == 'approved' ? 'status-approved' : 'status-rejected') . '">
                        ' . strtoupper($leave['status']) . '
                    </span>
                </div>
            </div>
            
            <div class="info-item">
                <span class="info-label">Reason for Leave:</span><br>
                <div style="margin-top: 5px; padding: 5px; border: 1px dashed #ccc; border-radius: 3px;">
                    ' . nl2br(htmlspecialchars($leave['reason'])) . '
                </div>
            </div>
            
            <div class="info-item" style="margin-top: 10px;">
                <span class="info-label">Reliever:</span> ' . htmlspecialchars($leave['reliever_name']) . '
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">MANAGER\'S APPROVAL</div>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Approved By:</span> ' . htmlspecialchars($leave['approved_by_name'] ?? 'N/A') . '
            </div>
            <div class="info-item">
                <span class="info-label">Position:</span> ' . htmlspecialchars($leave['approved_by_role'] ?? 'N/A') . '
            </div>
            <div class="info-item">
                <span class="info-label">Date Processed:</span> ' . $processed_date . '
            </div>
        </div>
        
        <div class="info-item" style="margin-top: 10px;">
            <span class="info-label">Manager\'s Notes:</span><br>
            <div class="notes-box">
                ' . (!empty($leave['manager_notes']) ? nl2br(htmlspecialchars($leave['manager_notes'])) : 'No notes provided.') . '
            </div>
        </div>
    </div>

    <div class="signature-section">
        <div class="info-grid">
            <div>
                <div class="signature-line"></div>
                <div style="text-align: center; font-size: 11px;">
                    Employee\'s Signature
                </div>
            </div>
            <div>
                <div class="signature-line"></div>
                <div style="text-align: center; font-size: 11px;">
                    Manager\'s Signature
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        This is a computer-generated document. No signature is required.<br>
        ' . htmlspecialchars($leave['company_name'] ?? 'Company Name') . ' - Leave Management System
    </div>
</body>
</html>';

// Output as PDF
require_once 'vendor/autoload.php'; // Make sure you have dompdf installed

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultPaperSize', 'A5');
$options->set('dpi', 150);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->render();

// Output the generated PDF
$dompdf->stream("leave_application_{$leave_id}.pdf", [
    'Attachment' => true
]);
?>