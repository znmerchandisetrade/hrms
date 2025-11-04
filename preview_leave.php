<?php
session_start();
require_once 'config/Database.php';
require_once 'models/LeaveApplication.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Get leave ID from query parameter
$leave_id = $_GET['id'] ?? 0;

if (!$leave_id) {
    die('<div style="padding: 20px; text-align: center; font-family: Arial;">Invalid leave application ID</div>');
}

$database = new Database();
$db = $database->getConnection();

$leaveApp = new LeaveApplication($db);

// Get leave details with user information
$query = "SELECT la.*, u.full_name, u.company_id, u.department_id, u.profile_image,
                 c.company_name, d.department_name,
                 approver.full_name as approved_by_name,
                 approver_role.role_name as approved_by_role,
                 la.created_at as applied_date,
                 lh.created_at as processed_date,
                 lh.manager_notes
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
    die('<div style="padding: 20px; text-align: center; font-family: Arial;">Leave application not found</div>');
}

// Format dates with time
$start_date = date('F j, Y', strtotime($leave['start_date']));
$end_date = date('F j, Y', strtotime($leave['end_date']));
$applied_date = date('F j, Y g:i A', strtotime($leave['applied_date']));
$processed_date = date('F j, Y g:i A', strtotime($leave['processed_date'] ?? $leave['applied_date']));
$current_date = date('F j, Y g:i A');

// Determine status color and text
$status_color = $leave['status'] == 'approved' ? '#28a745' : '#dc3545';
$status_text = $leave['status'] == 'approved' ? 'APPROVED' : 'REJECTED';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Application - <?php echo htmlspecialchars($leave['full_name']); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @media print {
            @page {
                size: 8.5in 5.5in landscape;
                margin: 0.15in;
            }
            body {
                font-size: 10pt;
                margin: 0;
                padding: 0;
                width: 8.5in;
                height: 5.5in;
            }
            .no-print {
                display: none !important;
            }
            .container {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                height: 5.2in !important;
            }
            .watermark {
                display: block !important;
            }
        }

        @media screen {
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                padding: 20px;
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            .container {
                background: white;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                border-radius: 8px;
                width: 8.5in;
                height: 5.5in;
            }
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 9pt;
            line-height: 1.3;
            color: #000;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 8.5in;
            height: 5.5in;
            padding: 0.2in;
            background: white;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .header {
            text-align: center;
            border-bottom: 1.5pt solid #2c5aa0;
            padding-bottom: 6pt;
            margin-bottom: 8pt;
        }

        .company-name {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 2pt;
            color: #2c5aa0;
            text-transform: uppercase;
        }

        .form-title {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 3pt;
            color: #333;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12pt;
            flex: 1;
        }

        .section {
            margin-bottom: 8pt;
        }

        .section-title {
            font-weight: bold;
            background: #2c5aa0;
            color: white;
            padding: 4pt 6pt;
            margin-bottom: 6pt;
            font-size: 9pt;
            text-transform: uppercase;
            border-radius: 3pt;
        }

        .info-grid {
            display: table;
            width: 100%;
        }

        .info-row {
            display: table-row;
        }

        .info-label {
            display: table-cell;
            font-weight: bold;
            color: #555;
            padding: 2pt 0;
            width: 40%;
            vertical-align: top;
            border-bottom: 0.5pt dotted #eee;
            font-size: 8pt;
        }

        .info-value {
            display: table-cell;
            padding: 2pt 0;
            vertical-align: top;
            border-bottom: 0.5pt dotted #eee;
            font-size: 8pt;
        }

        .leave-details {
            border: 1pt solid #e9ecef;
            padding: 6pt;
            border-radius: 3pt;
            background: #f8f9fa;
        }

        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15pt;
            margin-top: 12pt;
        }

        .signature-line {
            border-top: 1pt solid #000;
            margin-top: 20pt;
            width: 85%;
        }

        .signature-label {
            text-align: center;
            font-size: 7pt;
            margin-top: 2pt;
            color: #666;
        }

        .status-badge {
            padding: 2pt 6pt;
            border-radius: 2pt;
            font-weight: bold;
            font-size: 7pt;
            display: inline-block;
            text-transform: uppercase;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
            border: 0.5pt solid #28a745;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
            border: 0.5pt solid #dc3545;
        }

        .notes-box {
            border: 0.5pt solid #dee2e6;
            padding: 4pt;
            background: white;
            border-radius: 2pt;
            min-height: 25pt;
            font-size: 8pt;
        }

        .footer {
            text-align: center;
            margin-top: 8pt;
            font-size: 6pt;
            color: #666;
            border-top: 0.5pt solid #dee2e6;
            padding-top: 4pt;
        }

        .print-date {
            text-align: right;
            font-size: 6pt;
            color: #666;
            margin-bottom: 4pt;
        }

        .reason-box {
            border: 0.5pt dashed #adb5bd;
            padding: 4pt;
            margin-top: 3pt;
            background: white;
            border-radius: 2pt;
            font-size: 8pt;
            line-height: 1.3;
            min-height: 35pt;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            opacity: 0.03;
            font-size: 80pt;
            font-weight: bold;
            pointer-events: none;
            color: #2c5aa0;
            display: none;
            z-index: -1;
        }

        .compact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6pt;
            margin-bottom: 6pt;
        }

        .control-panel {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 12px;
            border-radius: 6px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 1000;
            border: 2px solid #2c5aa0;
        }

        .control-panel h4 {
            margin: 0 0 8px 0;
            color: #2c5aa0;
            font-size: 12px;
            text-align: center;
        }

        .btn {
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 10px;
            font-weight: bold;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 1px;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        }

        .btn i {
            margin-right: 4px;
            font-size: 9px;
        }

        .document-id {
            background: #f8f9fa;
            padding: 3px 6px;
            border-radius: 2px;
            font-family: monospace;
            font-size: 8px;
            border: 0.5pt solid #dee2e6;
        }

        .alert-info {
            background: #d1ecf1;
            border: 0.5pt solid #bee5eb;
            color: #0c5460;
            padding: 4pt 6pt;
            border-radius: 2pt;
            font-size: 7pt;
            margin: 4pt 0;
        }

        .vertical-divider {
            border-left: 0.5pt solid #dee2e6;
            height: 100%;
        }

        .horizontal-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8pt;
        }
    </style>
</head>
<body>
    <div class="watermark"><?php echo $status_text; ?></div>
    
    <!-- Control Panel -->
    <div class="control-panel no-print">
        <h4><i class="fas fa-print"></i> 8.5"×5.5" Landscape</h4>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3px;">
            <button class="btn btn-success" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
            <button class="btn btn-primary" onclick="downloadPDF()">
                <i class="fas fa-download"></i> PDF
            </button>
            <button class="btn btn-secondary" onclick="window.close()">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
        <div style="margin-top: 6px; text-align: center;">
            <div class="document-id">LA-<?php echo str_pad($leave_id, 6, '0', STR_PAD_LEFT); ?></div>
            <div style="font-size: 8px; color: #666; margin-top: 3px;">
                8.5" × 5.5" Landscape
            </div>
        </div>
    </div>

    <div class="container">
        <div class="print-date">
            Generated: <?php echo $current_date; ?>
        </div>

        <div class="header">
            <div class="company-name"><?php echo htmlspecialchars($leave['company_name'] ?? 'LEAVE MANAGEMENT SYSTEM'); ?></div>
            <div class="form-title">OFFICIAL LEAVE APPLICATION FORM</div>
        </div>

        <div class="content-grid">
            <!-- Left Column -->
            <div>
                <div class="section">
                    <div class="section-title">Employee Information</div>
                    <div class="info-grid">
                        <div class="info-row">
                            <div class="info-label">Full Name:</div>
                            <div class="info-value"><strong><?php echo htmlspecialchars($leave['full_name']); ?></strong></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Department:</div>
                            <div class="info-value"><?php echo htmlspecialchars($leave['department_name'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Store/Brand:</div>
                            <div class="info-value"><?php echo htmlspecialchars($leave['store_brand']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Date Applied:</div>
                            <div class="info-value"><?php echo $applied_date; ?></div>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <div class="section-title">Leave Details</div>
                    <div class="leave-details">
                        <div class="compact-grid">
                            <div>
                                <div class="info-label">Start Date:</div>
                                <div class="info-value"><strong><?php echo $start_date; ?></strong></div>
                            </div>
                            <div>
                                <div class="info-label">End Date:</div>
                                <div class="info-value"><strong><?php echo $end_date; ?></strong></div>
                            </div>
                            <div>
                                <div class="info-label">Total Days:</div>
                                <div class="info-value"><strong><?php echo $leave['day_off_count']; ?> day(s)</strong></div>
                            </div>
                            <div>
                                <div class="info-label">Status:</div>
                                <div class="info-value">
                                    <span class="status-badge <?php echo $leave['status'] == 'approved' ? 'status-approved' : 'status-rejected'; ?>">
                                        <?php echo strtoupper($leave['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div style="margin-top: 6pt;">
                            <div class="info-label">Reliever:</div>
                            <div class="info-value"><strong><?php echo htmlspecialchars($leave['reliever_name']); ?></strong></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div>
                <div class="section">
                    <div class="section-title">Reason for Leave</div>
                    <div class="reason-box"><?php echo nl2br(htmlspecialchars($leave['reason'])); ?></div>
                </div>

                <div class="section">
                    <div class="section-title">Approval Details</div>
                    <div class="compact-grid">
                        <div>
                            <div class="info-label">Approved By:</div>
                            <div class="info-value"><strong><?php echo htmlspecialchars($leave['approved_by_name'] ?? 'System'); ?></strong></div>
                        </div>
                        <div>
                            <div class="info-label">Position:</div>
                            <div class="info-value"><?php echo htmlspecialchars($leave['approved_by_role'] ?? 'Manager'); ?></div>
                        </div>
                        <div>
                            <div class="info-label">Date Processed:</div>
                            <div class="info-value"><?php echo $processed_date; ?></div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 6pt;">
                        <div class="info-label">Manager's Comments:</div>
                        <div class="notes-box">
                            <?php echo !empty($leave['manager_notes']) ? nl2br(htmlspecialchars($leave['manager_notes'])) : 'No additional comments provided.'; ?>
                        </div>
                    </div>
                </div>

                <div class="section signature-section">
                    <div style="text-align: center;">
                        <div class="signature-line"></div>
                        <div class="signature-label">
                            Employee's Signature<br>
                            <em>Date: _______________</em>
                        </div>
                    </div>
                    <div style="text-align: center;">
                        <div class="signature-line"></div>
                        <div class="signature-label">
                            Manager's Signature<br>
                            <em>Date: _______________</em>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <strong>OFFICIAL DOCUMENT</strong> - This leave application has been <?php echo strtoupper($leave['status']); ?>. &nbsp; | &nbsp;
            Document ID: LA-<?php echo str_pad($leave_id, 6, '0', STR_PAD_LEFT); ?> &nbsp; | &nbsp;
            <?php echo htmlspecialchars($leave['company_name'] ?? 'Leave Management System'); ?><br>
            <em>Computer-generated document. No physical signature required for digital records.</em>
        </div>
    </div>

    <script>
        // PDF Download function
        function downloadPDF() {
            alert('PDF download would generate 8.5"×5.5" landscape format. Currently showing print preview.');
            window.print();
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
            if (e.key === 'Escape') {
                window.close();
            }
        });

        // Auto-print if specified in URL
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('print') === 'true') {
            setTimeout(() => {
                window.print();
            }, 500);
        }

        // Show watermark on print
        window.addEventListener('beforeprint', () => {
            document.querySelector('.watermark').style.display = 'block';
        });

        window.addEventListener('afterprint', () => {
            document.querySelector('.watermark').style.display = 'none';
        });

        document.addEventListener('DOMContentLoaded', function() {
            console.log('Half Letter Landscape Preview Loaded:', {
                leaveId: <?php echo $leave_id; ?>,
                status: '<?php echo $leave['status']; ?>',
                format: '8.5"×5.5" Landscape'
            });
        });
    </script>
</body>
</html>