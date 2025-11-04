<?php
session_start();
require_once 'config/Database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Get application ID from query parameter
$application_id = $_GET['id'] ?? 0;

if (!$application_id) {
    die('<div style="padding: 20px; text-align: center; font-family: Arial;">Invalid application ID</div>');
}

$database = new Database();
$db = $database->getConnection();

// Get late letter details
$query = "SELECT ll.*, u.full_name, u.company_id, u.department_id, 
                 c.company_name, d.department_name,
                 approver.full_name as approved_by_name,
                 approver_role.role_name as approved_by_role
          FROM late_letter_applications ll 
          LEFT JOIN users u ON ll.user_id = u.id
          LEFT JOIN companies c ON u.company_id = c.id
          LEFT JOIN departments d ON u.department_id = d.id
          LEFT JOIN users approver ON ll.approved_by = approver.id
          LEFT JOIN roles approver_role ON approver.role_id = approver_role.id
          WHERE ll.id = :id AND ll.status = 'approved'";

$stmt = $db->prepare($query);
$stmt->bindParam(":id", $application_id);
$stmt->execute();
$application = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$application) {
    die('<div style="padding: 20px; text-align: center; font-family: Arial;">Application not found or not approved</div>');
}

// Format dates and times
$late_date = date('F j, Y', strtotime($application['late_date']));
$arrival_time = date('g:i A', strtotime($application['arrival_time']));
$applied_date = date('F j, Y g:i A', strtotime($application['created_at']));
$approved_date = date('F j, Y g:i A', strtotime($application['approved_at']));
$current_date = date('F j, Y g:i A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Late Letter - <?php echo htmlspecialchars($application['full_name']); ?></title>
    <style>
        @media print {
            @page { margin: 0.5in; }
            body { font-size: 11pt; }
            .no-print { display: none !important; }
        }
        
        body { 
            font-family: Arial, sans-serif; 
            line-height: 1.4;
            max-width: 8.5in;
            margin: 0 auto;
            padding: 0.5in;
        }
        
        .header { 
            text-align: center; 
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .company-name { 
            font-size: 18pt; 
            font-weight: bold; 
            margin-bottom: 5px;
        }
        
        .document-title { 
            font-size: 14pt; 
            font-weight: bold;
        }
        
        .section { 
            margin-bottom: 15px; 
        }
        
        .section-title { 
            font-weight: bold; 
            background: #f0f0f0;
            padding: 5px 10px;
            margin-bottom: 8px;
            border-left: 3px solid #333;
        }
        
        .info-grid { 
            display: table; 
            width: 100%; 
        }
        
        .info-row { 
            display: table-row; 
        }
        
        .info-label, .info-value { 
            display: table-cell; 
            padding: 3px 0;
            border-bottom: 1px dotted #ddd;
        }
        
        .info-label { 
            font-weight: bold; 
            width: 40%; 
        }
        
        .reason-box {
            border: 1px solid #ddd;
            padding: 10px;
            margin: 10px 0;
            background: #f9f9f9;
            border-radius: 5px;
        }
        
        .signature-area { 
            margin-top: 40px; 
        }
        
        .signature-line { 
            border-top: 1px solid #000; 
            width: 60%; 
            margin: 30px 0 5px 0;
        }
        
        .footer { 
            text-align: center; 
            margin-top: 30px;
            font-size: 9pt;
            color: #666;
        }
        
        .official-stamp {
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            border: 2px dashed #ccc;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name"><?php echo htmlspecialchars($application['company_name']); ?></div>
        <div class="document-title">OFFICIAL LATE ARRIVAL LETTER</div>
    </div>

    <div class="section">
        <div class="section-title">Employee Information</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Employee Name:</div>
                <div class="info-value"><?php echo htmlspecialchars($application['full_name']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Department:</div>
                <div class="info-value"><?php echo htmlspecialchars($application['department_name']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Date Applied:</div>
                <div class="info-value"><?php echo $applied_date; ?></div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Late Arrival Details</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Date of Late Arrival:</div>
                <div class="info-value"><strong><?php echo $late_date; ?></strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Actual Arrival Time:</div>
                <div class="info-value"><strong><?php echo $arrival_time; ?></strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Reason for Late Arrival:</div>
                <div class="info-value">
                    <div class="reason-box">
                        <?php echo nl2br(htmlspecialchars($application['reason'])); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Approval Details</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Approved By:</div>
                <div class="info-value"><?php echo htmlspecialchars($application['approved_by_name']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Position:</div>
                <div class="info-value"><?php echo htmlspecialchars($application['approved_by_role']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Date Approved:</div>
                <div class="info-value"><?php echo $approved_date; ?></div>
            </div>
            <?php if (!empty($application['manager_notes'])): ?>
            <div class="info-row">
                <div class="info-label">Manager's Notes:</div>
                <div class="info-value"><?php echo nl2br(htmlspecialchars($application['manager_notes'])); ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div style="text-align: center; margin: 20px 0;">
        <div class="official-stamp">
            <strong>OFFICIALLY APPROVED</strong><br>
            <span style="font-size: 10pt;">Late Arrival Acknowledged</span>
        </div>
    </div>

    <div class="signature-area">
        <div style="display: flex; justify-content: space-between;">
            <div style="text-align: center; width: 45%;">
                <div class="signature-line"></div>
                <div style="font-size: 10pt;">Employee's Signature</div>
            </div>
            <div style="text-align: center; width: 45%;">
                <div class="signature-line"></div>
                <div style="font-size: 10pt;">Manager's Signature</div>
            </div>
        </div>
    </div>

    <div class="footer">
        Document ID: LL-<?php echo str_pad($application_id, 6, '0', STR_PAD_LEFT); ?> | 
        Generated: <?php echo $current_date; ?> | 
        <?php echo htmlspecialchars($application['company_name']); ?>
    </div>

    <script>
        window.addEventListener('load', function() {
            window.print();
            setTimeout(function() {
                window.close();
            }, 500);
        });
    </script>
</body>
</html>