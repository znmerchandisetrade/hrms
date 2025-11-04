<?php
// print_store_letter.php
require_once 'includes/session_check.php';
checkSession();

include_once 'config/Database.php';
include_once 'models/LeaveApplication.php';

$database = new Database();
$db = $database->getConnection();

$leaveApp = new LeaveApplication($db);

// Get leave ID from URL
$leave_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$leave_id) {
    die("Invalid leave application ID.");
}

// UPDATED SQL QUERY - Get recipient information based on store
$query = "SELECT 
            la.*, 
            u.full_name, 
            u.company_id,
            u.brand_id,
            u.department_id,
            u.store_id,
            c.company_name,
            b.brand_name,
            d.department_name,
            s.store_name,
            s.id as store_id,
            r.name as recipient_name,
            r.position as recipient_position,
            approver.full_name as approved_by_name,
            approver_role.role_name as approver_role,
            la.approved_by_user_id,
            la.approved_at
          FROM leave_applications la
          LEFT JOIN users u ON la.user_id = u.id
          LEFT JOIN companies c ON u.company_id = c.id
          LEFT JOIN brands b ON u.brand_id = b.id
          LEFT JOIN departments d ON u.department_id = d.id
          LEFT JOIN stores s ON u.store_id = s.id
          LEFT JOIN recipients r ON u.store_id = r.store_id
          LEFT JOIN users approver ON la.approved_by_user_id = approver.id
          LEFT JOIN roles approver_role ON approver.role_id = approver_role.id
          WHERE la.id = :id AND la.status = 'approved'";

$stmt = $db->prepare($query);
$stmt->bindParam(":id", $leave_id);
$stmt->execute();
$leave = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$leave) {
    die("Leave application not found or not approved.");
}

// Get company details for header
$company_query = "SELECT * FROM companies WHERE id = :company_id";
$company_stmt = $db->prepare($company_query);
$company_stmt->bindParam(":company_id", $leave['company_id']);
$company_stmt->execute();
$company = $company_stmt->fetch(PDO::FETCH_ASSOC);

// Set the company header image path
$header_image = 'assets/company_headers/';
if ($company) {
    $header_image .= $company['id'] . '_header.jpg';
} else {
    $header_image .= 'default_header.jpg';
}

// Check if header image exists, otherwise use default
if (!file_exists($header_image)) {
    $header_image = 'assets/company_headers/default_header.jpg';
}

// Generate document ID
$document_id = 'LMS-' . date('Ymd') . '-' . str_pad($leave_id, 4, '0', STR_PAD_LEFT);

// Determine approving manager name - with fallbacks
if (!empty($leave['approved_by_name'])) {
    $approving_manager = $leave['approved_by_name'];
    $approver_role = $leave['approver_role'] ?? 'Approving Manager';
} else {
    // Fallback: Use session user if no approver in database
    $approving_manager = $_SESSION['full_name'] ?? 'Authorized Manager';
    $approver_role = $_SESSION['role_name'] ?? 'Manager';
}

// Determine recipient information
if (!empty($leave['recipient_name']) && !empty($leave['recipient_position'])) {
    $recipient_name = $leave['recipient_name'];
    $recipient_position = $leave['recipient_position'];
    $store_name = $leave['store_name'];
} else {
    // Fallback to default recipient if none found for this store
    $recipient_name = 'Store Administrator';
    $recipient_position = 'Store Manager';
    $store_name = $leave['store_name'] ?? 'Store';
}



// Generate document ID
$document_id = 'LMS-' . date('Ymd') . '-' . str_pad($leave_id, 4, '0', STR_PAD_LEFT);

// Generate verification URL and QR code data - FIXED FOR LOCALHOST
$base_url = "http://localhost/leave_management_system";
$verification_url = $base_url . "/verify_leave.php?id=" . $leave_id . "&doc=" . $document_id;
$qr_code_data = urlencode($verification_url);

// QR code generation URLs
$qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=60x60&data=" . $qr_code_data;
$qr_code_alt = "https://chart.googleapis.com/chart?chs=60x60&cht=qr&chl=" . $qr_code_data . "&choe=UTF-8";

// Determine approving manager name - with fallbacks
if (!empty($leave['approved_by_name'])) {
    $approving_manager = $leave['approved_by_name'];
    $approver_role = $leave['approver_role'] ?? 'Approving Manager';
} else {
    // Fallback: Use session user if no approver in database
    $approving_manager = $_SESSION['full_name'] ?? 'Authorized Manager';
    $approver_role = $_SESSION['role_name'] ?? 'Manager';
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absence Notification - <?php echo htmlspecialchars($company['company_name'] ?? 'Company'); ?></title>
    <style>
        /* Print Styles - One Page Fit */
        @media print {
            body { 
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                height: 100% !important;
                overflow: hidden !important;
            }
            .no-print { 
                display: none !important; 
            }
            @page { 
                margin: 0.4in !important;
                size: portrait;
            }
            .letter-container {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                height: 100% !important;
                min-height: auto !important;
                max-height: 100% !important;
            }
            .document-footer {
                position: fixed !important;
                bottom: 0.3in !important;
                left: 0.4in !important;
                right: 0.4in !important;
                margin: 0 !important;
            }
            .qr-verification {
                position: fixed !important;
                bottom: 0.8in !important;
                right: 0.4in !important;
                margin: 0 !important;
            }
        }
        
        /* Screen Styles - Compact One Page */
        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            line-height: 1.2;
            margin: 0;
            padding: 0;
            color: #2c3e50;
            background: #f5f5f5;
            font-size: 11pt;
            width: 100vw;
            height: 100vh;
            overflow: auto;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .button-container {
            width: 100%;
            background: white;
            padding: 10px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            margin-bottom: 0;
        }
        
        .letter-container {
            width: 8.5in;
            height: 11in;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin: 5px auto;
            padding: 0.4in;
            position: relative;
            box-sizing: border-box;
            overflow: hidden;
        }
        
        /* Ultra Compact Layout for One Page */
        .company-header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #2c5aa0;
        }
        
        .company-logo {
            max-width: 120px;
            max-height: 50px;
            margin-bottom: 5px;
        }
        
        .company-name {
            font-size: 14pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
            color: #2c3e50;
        }
        
        .company-tagline {
            font-size: 8pt;
            color: #7f8c8d;
            font-style: italic;
        }
        
        .letter-date {
            margin-bottom: 15px;
            padding-bottom: 5px;
            font-size: 10pt;
        }
        
        .recipient-address {
            margin-bottom: 15px;
            line-height: 1.3;
            padding: 8px;
            background: #f8f9fa;
            border-left: 2px solid #2c5aa0;
            font-size: 10pt;
        }
        
        .subject {
            font-weight: 600;
            margin-bottom: 12px;
            text-transform: uppercase;
            font-size: 10pt;
            color: #2c5aa0;
            padding: 4px 0;
        }
        
        .content {
            margin-bottom: 15px;
            line-height: 1.3;
        }
        
        .salutation {
            margin-bottom: 10px;
            font-size: 10pt;
        }
        
        .paragraph {
            margin-bottom: 8px;
            text-align: left;
            font-size: 10pt;
        }
        
        .employee-info {
            margin: 12px 0;
            padding: 10px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 3px;
            font-size: 10pt;
        }
        
        .info-line {
            margin-bottom: 5px;
            display: flex;
            align-items: center;
        }
        
        .info-label {
            width: 100px;
            font-weight: 600;
            color: #2c3e50;
            flex-shrink: 0;
            font-size: 9pt;
        }
        
        .info-value {
            flex-grow: 1;
            border-bottom: 1px solid #bdc3c7;
            padding-bottom: 1px;
            margin-left: 5px;
            font-size: 9pt;
        }
        
        /* Compact Closing and Signature */
        .closing {
            margin-top: 20px;
        }
        
        .signature-section {
            margin-top: 25px;
        }
        
        .signature-line {
            border-top: 1px solid #2c3e50;
            width: 200px;
            margin-top: 25px;
        }
        
        .signature-name {
            margin-top: 4px;
            font-weight: 600;
            font-size: 10pt;
        }
        
        .signature-title {
            font-size: 8pt;
            color: #7f8c8d;
            font-style: italic;
        }
        
        .digital-signature {
            margin-top: 6px;
            font-size: 7pt;
            color: #95a5a6;
            font-style: italic;
            padding: 4px;
            background: #f8f9fa;
            border-radius: 2px;
        }
        
        /* QR Code Section - Bottom Right above Footer */
        .qr-verification {
            position: absolute;
            bottom: 0.5in; /* Position above footer */
            right: 0.4in;
            text-align: center;
            border: 1px solid #e0e0e0;
            padding: 5px;
            background: white;
            border-radius: 3px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            z-index: 10;
        }
        
        .qr-code {
            width: 100px;
            height: 100px;
            margin-bottom: 2px;
            border: 1px solid #f0f0f0;
        }
        
        .qr-label {
            font-size: 5.5pt;
            color: #666;
            font-weight: 600;
            text-transform: uppercase;
            line-height: 1;
        }
        
        .verification-url {
            font-size: 4pt;
            color: #999;
            word-break: break-all;
            margin-top: 1px;
            display: none; /* Hide long URL in print */
        }
        
        /* Compact Footer */
        .document-footer {
            position: absolute;
            bottom: 0.2in;
            left: 0.4in;
            right: 0.4in;
            font-size: 6pt;
            color: #666;
            border-top: 1px solid #ddd;
            padding: 4px 0;
            background: #f8f9fa;
        }
        
        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: nowrap;
            gap: 5px;
        }
        
        .footer-section {
            display: flex;
            align-items: center;
            gap: 2px;
            white-space: nowrap;
        }
        
        .footer-label {
            font-weight: 600;
            color: #333;
            font-size: 5.5pt;
        }
        
        .footer-value {
            color: #555;
            font-size: 5.5pt;
        }
        
        .document-id-badge {
            background: #2c5aa0;
            color: white;
            padding: 1px 3px;
            border-radius: 1px;
            font-weight: 600;
            font-size: 5.5pt;
            white-space: nowrap;
        }
        
        .footer-separator {
            color: #999;
            margin: 0 1px;
            font-size: 5pt;
        }
        
        /* Verification Info in Footer */
        .verification-info {
            display: flex;
            align-items: center;
            gap: 3px;
            font-size: 5.5pt;
        }
        
        .verification-badge {
            background: #27ae60;
            color: white;
            padding: 1px 3px;
            border-radius: 1px;
            font-weight: 600;
        }
        
        /* Compact Buttons */
        .print-button {
            background: linear-gradient(135deg, #2c5aa0, #1e3d6f);
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            margin: 0 3px;
            font-family: 'Segoe UI', Arial, sans-serif;
            font-weight: 600;
            font-size: 9pt;
        }
        
        /* Content Optimization */
        .content-highlight {
            background: linear-gradient(120deg, #a8e6cf 0%, #a8e6cf 100%);
            background-repeat: no-repeat;
            background-size: 100% 0.15em;
            background-position: 0 88%;
            padding: 0 2px;
        }
        
        .approval-stamp {
            position: absolute;
            right: 30px;
            top: 120px;
            transform: rotate(5deg);
            color: #27ae60;
            font-weight: 700;
            font-size: 12pt;
            opacity: 0.8;
            border: 2px solid #27ae60;
            padding: 5px 10px;
            border-radius: 4px;
            background: rgba(39, 174, 96, 0.1);
        }
        
        /* Ensure everything fits in one page */
        .content-wrapper {
            min-height: 6.5in;
            max-height: 7in;
        }
    </style>
</head>
<body>
    <div class="no-print button-container">
        <button onclick="window.print()" class="print-button">
            🖨️ Print
        </button>
        <button onclick="window.close()" class="print-button" style="background: linear-gradient(135deg, #6c757d, #495057);">
            ❌ Close
        </button>
        <button onclick="testQRCode()" class="print-button" style="background: linear-gradient(135deg, #17a2b8, #138496);">
            🔍 Test QR
        </button>
    </div>

    <div class="letter-container">
        <!-- Approval Stamp -->
        <div class="approval-stamp no-print">
            APPROVED
        </div>

        <!-- Company Header -->
        <div class="company-header">
            <?php if (file_exists($header_image)): ?>
                <img src="<?php echo $header_image; ?>" alt="Company Header" class="company-logo">
            <?php endif; ?>
            <div class="company-name"><?php echo htmlspecialchars($company['company_name'] ?? 'CORPORATE APPAREL INC.'); ?></div>
            <div class="company-tagline">Human Resources Department</div>
        </div>

        <!-- Letter Date -->
        <div class="letter-date">
            <strong>Date:</strong> <?php echo date('F j, Y'); ?>
        </div>

        <!-- DYNAMIC Recipient Address -->
        <div class="recipient-address">
            <strong>To:</strong><br>
            <?php echo htmlspecialchars($recipient_name); ?><br>
            <?php echo htmlspecialchars($recipient_position); ?><br>
            <?php echo htmlspecialchars($store_name); ?>
        </div>

        <!-- Subject -->
        <div class="subject">
            Subject: Absence Notification
        </div>

        <!-- Letter Content -->
        <div class="content content-wrapper">
            <div class="salutation">
                Dear   <?php echo htmlspecialchars($recipient_name); ?>:
            </div>
            
            <div class="paragraph">
                This is to notify you of the approved leave of absence of 
                <span class="content-highlight"><?php echo htmlspecialchars($leave['full_name']); ?></span>,
                Promo Clerk/Sales Demo for the 
                <span class="content-highlight"><?php echo htmlspecialchars($leave['brand_name'] ?? 'N/A'); ?></span> brand.
            </div>

            <!-- Employee Details -->
            <div class="employee-info">
                <div class="info-line">
                    <span class="info-label">Date Covered:</span>
                    <span class="info-value">
                        <?php echo date('F j, Y', strtotime($leave['start_date'])); ?> to <?php echo date('F j, Y', strtotime($leave['end_date'])); ?>
                    </span>
                </div>
                <div class="info-line">
                    <span class="info-label">Reason:</span>
                    <span class="info-value">
                        <?php echo htmlspecialchars($leave['reason']); ?>
                    </span>
                </div>
            </div>

            <div class="paragraph">
                We appreciate your kind understanding and cooperation.
            </div>

            <!-- Closing and Signatures - Moved right after the content -->
            <div class="closing">
                <div class="paragraph">Sincerely,</div>
                
                <div class="signature-section">
                    <!-- AVP Signature -->
                    <div class="signature-section">
                        <div class="signature-line"></div>
                        <div class="signature-name">Ms. Chat B. Del Remedio</div>
                        <div class="signature-title">Assistant Vice President for Human Resources</div>
                        <div class="digital-signature">
                            Digitally signed by Leave Management System<br>
                            Document ID: <?php echo $document_id; ?><br>
                            Date: <?php echo date('Y-m-d H:i:s'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- QR Code Verification Section - Bottom Right above Footer -->
        <div class="qr-verification">
            <img src="<?php echo $qr_code_url; ?>" alt="QR Code for Document Verification" class="qr-code" 
                 onerror="this.src='<?php echo $qr_code_alt; ?>'">
            <div class="qr-label">Scan to Verify</div>
            <div class="verification-url"><?php echo $verification_url; ?></div>
        </div>

        <!-- Document Footer - Bottom of Page -->
        <div class="document-footer">
            <div class="footer-content">
                <div class="footer-section">
                    <span class="document-id-badge"><?php echo $document_id; ?></span>
                </div>
                <div class="footer-section">
                    <span class="footer-label">Generated:</span>
                    <span class="footer-value"><?php echo date('M j, Y g:i A'); ?></span>
                    <span class="footer-separator">|</span>
                </div>
                <div class="footer-section">
                    <span class="footer-label">Leave ID:</span>
                    <span class="footer-value">HRMS-<?php echo $leave_id; ?></span>
                    <span class="footer-separator">|</span>
                </div>
                <div class="footer-section">
                    <span class="verification-info">
                        <span class="verification-badge">VERIFIABLE</span>
                        <span class="footer-label">Scan QR Code</span>
                    </span>
                    <span class="footer-separator">|</span>
                </div>
                <div class="footer-section">
                    <span class="footer-label">Store:</span>
                    <span class="footer-value"><?php echo htmlspecialchars($store_name); ?></span>
                    <span class="footer-separator">|</span>
                </div>
                <div class="footer-section">
                    <span class="footer-label">Approved By:</span>
                    <span class="footer-value"><?php echo htmlspecialchars($approving_manager); ?></span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Force portrait orientation
        function setPortraitOrientation() {
            if (screen.orientation && screen.orientation.lock) {
                screen.orientation.lock('portrait').catch(function(error) {
                    console.log('Orientation lock failed: ', error);
                });
            }
        }

        // Test QR Code functionality
        function testQRCode() {
            const qrCode = document.querySelector('.qr-code');
            const verificationUrl = '<?php echo $verification_url; ?>';
            
            if (qrCode.complete && qrCode.naturalHeight !== 0) {
                alert('QR Code loaded successfully!\nVerification URL: ' + verificationUrl);
            } else {
                alert('QR Code failed to load. Trying alternative...');
                qrCode.src = '<?php echo $qr_code_alt; ?>';
            }
        }

        // Auto-print option (optional)
        window.onload = function() {
            setPortraitOrientation();
            
            // Check if QR code loaded successfully
            const qrCode = document.querySelector('.qr-code');
            qrCode.onerror = function() {
                console.log('Primary QR code failed, switching to alternative...');
                this.src = '<?php echo $qr_code_alt; ?>';
            };

            // Test QR Code functionality
function testQRCode() {
    const qrCode = document.querySelector('.qr-code');
    const verificationUrl = '<?php echo $verification_url; ?>';
    
    if (qrCode.complete && qrCode.naturalHeight !== 0) {
        alert('QR Code loaded successfully!\nVerification URL: ' + verificationUrl +
              '\n\nThis will direct to: localhost/leave_management_system/verify_leave.php');
    } else {
        alert('QR Code failed to load. Trying alternative...');
        qrCode.src = '<?php echo $qr_code_alt; ?>';
    }
}
            
            // Uncomment the line below if you want to auto-print when the page loads
            // window.print();
        };
        
        // Close window after print
        window.onafterprint = function() {
            // Optional: close window after printing
            // window.close();
        };

        // Scroll to top when page loads
        window.scrollTo(0, 0);
    </script>
</body>
</html>