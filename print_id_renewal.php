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

// Get ID renewal details
$query = "SELECT idr.*, u.full_name, u.company_id, u.department_id, 
                 c.company_name, d.department_name,
                 approver.full_name as approved_by_name,
                 approver_role.role_name as approved_by_role,
                 collector.full_name as collected_by_name
          FROM id_renewal_applications idr 
          LEFT JOIN users u ON idr.user_id = u.id
          LEFT JOIN companies c ON u.company_id = c.id
          LEFT JOIN departments d ON u.department_id = d.id
          LEFT JOIN users approver ON idr.approved_by = approver.id
          LEFT JOIN roles approver_role ON approver.role_id = approver_role.id
          LEFT JOIN users collector ON idr.collected_by = collector.id
          WHERE idr.id = :id AND idr.status IN ('approved', 'collected')";

$stmt = $db->prepare($query);
$stmt->bindParam(":id", $application_id);
$stmt->execute();
$application = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$application) {
    die('<div style="padding: 20px; text-align: center; font-family: Arial;">Application not found or not approved</div>');
}

// Format dates
$current_valid_from = date('F j, Y', strtotime($application['current_valid_from']));
$current_valid_to = date('F j, Y', strtotime($application['current_valid_to']));
$requested_valid_from = date('F j, Y', strtotime($application['requested_valid_from']));
$requested_valid_to = date('F j, Y', strtotime($application['requested_valid_to']));
$applied_date = date('F j, Y g:i A', strtotime($application['created_at']));
$approved_date = !empty($application['approved_at']) ? date('F j, Y g:i A', strtotime($application['approved_at'])) : 'N/A';
$collection_date = !empty($application['collection_date']) ? date('F j, Y', strtotime($application['collection_date'])) : 'N/A';
$collected_date = !empty($application['collected_at']) ? date('F j, Y g:i A', strtotime($application['collected_at'])) : 'N/A';
$current_date = date('F j, Y g:i A');

// Calculate durations
$current_duration = (strtotime($application['current_valid_to']) - strtotime($application['current_valid_from'])) / (60 * 60 * 24);
$requested_duration = (strtotime($application['requested_valid_to']) - strtotime($application['requested_valid_from'])) / (60 * 60 * 24);
$duration_difference = $requested_duration - $current_duration;

// Calculate collection status
$collection_status = 'Pending';
$collection_class = 'collection-pending';
if (!empty($application['collected_at'])) {
    $collection_status = 'Collected';
    $collection_class = 'collection-completed';
} elseif (strtotime($application['collection_date']) < time()) {
    $collection_status = 'Overdue';
    $collection_class = 'collection-overdue';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Renewal - <?php echo htmlspecialchars($application['full_name']); ?></title>
    <style>
        @media print {
            @page { margin: 0.5in; }
            body { font-size: 11pt; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
        }
        
        body { 
            font-family: Arial, sans-serif; 
            line-height: 1.4;
            max-width: 8.5in;
            margin: 0 auto;
            padding: 0.5in;
            background: white;
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
        
        .validity-box {
            border: 1px solid #ddd;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        
        .current-validity {
            background: #fff3cd;
            border-color: #ffeaa7;
        }
        
        .requested-validity {
            background: #d1edff;
            border-color: #b3d9ff;
        }
        
        .duration-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 9pt;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .duration-increase {
            background: #d4edda;
            color: #155724;
        }
        
        .duration-decrease {
            background: #f8d7da;
            color: #721c24;
        }
        
        .duration-same {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .urgency-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 10pt;
        }
        .urgency-critical { background: #dc3545; color: white; }
        .urgency-high { background: #fd7e14; color: white; }
        .urgency-medium { background: #ffc107; color: black; }
        .urgency-low { background: #28a745; color: white; }
        
        .collection-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 10pt;
        }
        .collection-completed { background: #28a745; color: white; }
        .collection-pending { background: #ffc107; color: black; }
        .collection-overdue { background: #dc3545; color: white; }
        
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
        
        .important-note {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 10px;
            margin: 15px 0;
            border-radius: 5px;
            font-size: 10pt;
        }
        
        .hr-notes {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            padding: 10px;
            margin: 15px 0;
            border-radius: 5px;
            font-size: 10pt;
        }
        
        .timeline {
            border-left: 3px solid #007bff;
            margin: 20px 0;
            padding-left: 20px;
        }
        
        .timeline-item {
            margin-bottom: 15px;
            position: relative;
        }
        
        .timeline-item:before {
            content: '';
            position: absolute;
            left: -26px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #007bff;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name"><?php echo htmlspecialchars($application['company_name']); ?></div>
        <div class="document-title">EMPLOYEE ID RENEWAL APPROVAL</div>
    </div>

    <div class="section">
        <div class="section-title">Application Summary</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Application ID:</div>
                <div class="info-value">
                    <strong>IDR-<?php echo str_pad($application_id, 6, '0', STR_PAD_LEFT); ?></strong>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Current Status:</div>
                <div class="info-value">
                    <span class="collection-badge <?php echo $collection_class; ?>">
                        <?php echo strtoupper($application['status']); ?> - <?php echo $collection_status; ?>
                    </span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Renewal Type:</div>
                <div class="info-value">
                    <strong><?php echo htmlspecialchars($application['renewal_type']); ?></strong>
                    <?php if ($application['renewal_type'] == 'Lost Card' || $application['renewal_type'] == 'Damaged Card'): ?>
                        <span style="color: #dc3545; font-weight: bold; font-size: 9pt;"> (Additional Verification Required)</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Urgency Level:</div>
                <div class="info-value">
                    <?php 
                    $urgency_class = '';
                    switch ($application['urgency_level']) {
                        case 'critical': $urgency_class = 'urgency-critical'; break;
                        case 'high': $urgency_class = 'urgency-high'; break;
                        case 'medium': $urgency_class = 'urgency-medium'; break;
                        default: $urgency_class = 'urgency-low';
                    }
                    ?>
                    <span class="urgency-badge <?php echo $urgency_class; ?>">
                        <?php echo ucfirst($application['urgency_level']); ?> Priority
                    </span>
                </div>
            </div>
        </div>
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
                <div class="info-label">Employee ID:</div>
                <div class="info-value"><?php echo htmlspecialchars($application['user_id']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Date Applied:</div>
                <div class="info-value"><?php echo $applied_date; ?></div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Current ID Validity</div>
        <div class="validity-box current-validity">
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Valid From:</div>
                    <div class="info-value"><strong><?php echo $current_valid_from; ?></strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Valid To:</div>
                    <div class="info-value"><strong><?php echo $current_valid_to; ?></strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Duration:</div>
                    <div class="info-value">
                        <strong><?php echo $current_duration; ?> days</strong>
                        (<?php echo round($current_duration / 30, 1); ?> months)
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Requested Renewal</div>
        <div class="validity-box requested-validity">
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">New Valid From:</div>
                    <div class="info-value"><strong><?php echo $requested_valid_from; ?></strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">New Valid To:</div>
                    <div class="info-value"><strong><?php echo $requested_valid_to; ?></strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">New Duration:</div>
                    <div class="info-value">
                        <strong><?php echo $requested_duration; ?> days</strong>
                        (<?php echo round($requested_duration / 30, 1); ?> months)
                        <?php if ($duration_difference > 0): ?>
                            <span class="duration-badge duration-increase">+<?php echo $duration_difference; ?> days</span>
                        <?php elseif ($duration_difference < 0): ?>
                            <span class="duration-badge duration-decrease"><?php echo $duration_difference; ?> days</span>
                        <?php else: ?>
                            <span class="duration-badge duration-same">No change</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Renewal Details</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Reason for Renewal:</div>
                <div class="info-value">
                    <div style="border: 1px solid #ddd; padding: 8px; border-radius: 3px; background: #f8f9fa;">
                        <?php echo nl2br(htmlspecialchars($application['reason'])); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($application['manager_notes'])): ?>
    <div class="section">
        <div class="section-title">Manager's Approval Notes</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-value">
                    <div style="border: 1px solid #28a745; padding: 8px; border-radius: 3px; background: #d4edda;">
                        <?php echo nl2br(htmlspecialchars($application['manager_notes'])); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($application['hr_notes'])): ?>
    <div class="section">
        <div class="section-title">HR Department Notes</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-value">
                    <div class="hr-notes">
                        <?php echo nl2br(htmlspecialchars($application['hr_notes'])); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="section">
        <div class="section-title">Approval Details</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Approved By:</div>
                <div class="info-value"><?php echo htmlspecialchars($application['approved_by_name'] ?? 'N/A'); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Position:</div>
                <div class="info-value"><?php echo htmlspecialchars($application['approved_by_role'] ?? 'N/A'); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Date Approved:</div>
                <div class="info-value"><?php echo $approved_date; ?></div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Collection Information</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Suggested Collection Date:</div>
                <div class="info-value">
                    <strong><?php echo $collection_date; ?></strong>
                    <?php 
                    if ($application['status'] == 'approved' && empty($application['collected_at'])) {
                        $collection_days = (strtotime($application['collection_date']) - time()) / (60 * 60 * 24);
                        if ($collection_days <= 0) {
                            echo '<span style="color: #dc3545; font-weight: bold;"> (OVERDUE)</span>';
                        } elseif ($collection_days <= 2) {
                            echo '<span style="color: #fd7e14; font-weight: bold;"> (URGENT - Collect within ' . ceil($collection_days) . ' days)</span>';
                        }
                    }
                    ?>
                </div>
            </div>
            <?php if (!empty($application['collected_at'])): ?>
            <div class="info-row">
                <div class="info-label">Actual Collection Date:</div>
                <div class="info-value">
                    <strong><?php echo $collected_date; ?></strong>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Collected By:</div>
                <div class="info-value">
                    <strong><?php echo htmlspecialchars($application['collected_by_name']); ?></strong>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="important-note">
        <strong>Important Instructions:</strong><br>
        1. Employee must visit HR department to collect new ID card within 3 working days of approval.<br>
        2. Old ID card must be surrendered when collecting the new one.<br>
        3. For lost cards, a police report may be required.<br>
        4. Damaged cards must be returned for replacement.<br>
        5. Photo identification is required for collection.
    </div>

    <div class="signature-area">
        <div style="display: flex; justify-content: space-between;">
            <div style="text-align: center; width: 45%;">
                <div class="signature-line"></div>
                <div style="font-size: 10pt;">Employee's Signature</div>
            </div>
            <div style="text-align: center; width: 45%;">
                <div class="signature-line"></div>
                <div style="font-size: 10pt;">HR Department Signature</div>
            </div>
        </div>
    </div>

    <div class="footer">
        Document ID: IDR-<?php echo str_pad($application_id, 6, '0', STR_PAD_LEFT); ?> | 
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