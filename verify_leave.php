<?php
// verify_leave.php
require_once 'config/Database.php';
include_once 'models/LeaveApplication.php';

$database = new Database();
$db = $database->getConnection();

// Get parameters from URL
$leave_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$document_id = isset($_GET['doc']) ? $_GET['doc'] : '';

if (!$leave_id || !$document_id) {
    die("Invalid verification link.");
}

// Query to get leave application details
$query = "SELECT 
            la.*, 
            u.full_name, 
            u.company_id,
            u.brand_id,
            u.store_id,
            c.company_name,
            b.brand_name,
            s.store_name,
            approver.full_name as approved_by_name,
            approver_role.role_name as approver_role,
            la.approved_at
          FROM leave_applications la
          LEFT JOIN users u ON la.user_id = u.id
          LEFT JOIN companies c ON u.company_id = c.id
          LEFT JOIN brands b ON u.brand_id = b.id
          LEFT JOIN stores s ON u.store_id = s.id
          LEFT JOIN users approver ON la.approved_by_user_id = approver.id
          LEFT JOIN roles approver_role ON approver.role_id = approver_role.id
          WHERE la.id = :id AND la.status = 'approved'";

$stmt = $db->prepare($query);
$stmt->bindParam(":id", $leave_id);
$stmt->execute();
$leave = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if document exists and is approved
if (!$leave) {
    $status = "❌ INVALID OR REVOKED";
    $status_class = "invalid";
    $message = "This document cannot be found or has been revoked.";
} else {
    // Verify document ID matches
    $expected_doc_id = 'LMS-' . date('Ymd', strtotime($leave['approved_at'] ?? $leave['created_at'])) . '-' . str_pad($leave_id, 4, '0', STR_PAD_LEFT);
    
    if ($document_id === $expected_doc_id) {
        $status = "✅ VALID AND APPROVED";
        $status_class = "valid";
        $message = "This document is authentic and approved.";
    } else {
        $status = "⚠️ DOCUMENT TAMPERED";
        $status_class = "tampered";
        $message = "Document verification failed. This may be a tampered copy.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Verification - <?php echo htmlspecialchars($document_id); ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .verification-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 100%;
        }
        
        .status-banner {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: bold;
            font-size: 18px;
        }
        
        .status-valid {
            background: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }
        
        .status-invalid {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }
        
        .status-tampered {
            background: #fff3cd;
            color: #856404;
            border: 2px solid #ffeaa7;
        }
        
        .document-header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .document-title {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .document-id {
            font-size: 16px;
            color: #6c757d;
            font-family: monospace;
        }
        
        .details-section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #2c5aa0;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detail-item {
            display: flex;
            margin-bottom: 10px;
            padding: 8px 0;
        }
        
        .detail-label {
            font-weight: 600;
            color: #495057;
            min-width: 150px;
        }
        
        .detail-value {
            color: #6c757d;
            flex: 1;
        }
        
        .verification-message {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 20px;
            font-style: italic;
            color: #6c757d;
        }
        
        .company-footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
            color: #6c757d;
            font-size: 14px;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 8px;
        }
        
        .badge-valid {
            background: #28a745;
            color: white;
        }
        
        .badge-invalid {
            background: #dc3545;
            color: white;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .verification-container {
                padding: 20px;
            }
            
            .detail-item {
                flex-direction: column;
            }
            
            .detail-label {
                min-width: auto;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="document-header">
            <div class="document-title">Absence Notification Letter</div>
            <div class="document-id"><?php echo htmlspecialchars($document_id); ?></div>
        </div>
        
        <div class="status-banner <?php echo $status_class; ?>">
            <?php echo $status; ?>
        </div>
        
        <?php if ($leave): ?>
        <div class="details-section">
            <div class="section-title">Employee Details</div>
            <div class="detail-item">
                <span class="detail-label">Employee Name:</span>
                <span class="detail-value"><?php echo htmlspecialchars($leave['full_name']); ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Store:</span>
                <span class="detail-value"><?php echo htmlspecialchars($leave['store_name'] ?? 'N/A'); ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Brand:</span>
                <span class="detail-value"><?php echo htmlspecialchars($leave['brand_name'] ?? 'N/A'); ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Leave Period:</span>
                <span class="detail-value">
                    <?php echo date('F j, Y', strtotime($leave['start_date'])); ?> to 
                    <?php echo date('F j, Y', strtotime($leave['end_date'])); ?>
                </span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Reason:</span>
                <span class="detail-value"><?php echo htmlspecialchars(ucfirst($leave['reason'])); ?></span>
            </div>
        </div>
        
        <div class="details-section">
            <div class="section-title">Approval Information</div>
            <div class="detail-item">
                <span class="detail-label">Approved By:</span>
                <span class="detail-value">
                    <?php echo htmlspecialchars($leave['approved_by_name'] ?? 'System'); ?>
                    <?php if ($leave['approver_role']): ?>
                        <span class="badge badge-valid"><?php echo htmlspecialchars($leave['approver_role']); ?></span>
                    <?php endif; ?>
                </span>
            </div>
            <?php if (!empty($leave['approved_at'])): ?>
            <div class="detail-item">
                <span class="detail-label">Approved On:</span>
                <span class="detail-value"><?php echo date('F j, Y \a\t g:i A', strtotime($leave['approved_at'])); ?></span>
            </div>
            <?php endif; ?>
            <div class="detail-item">
                <span class="detail-label">Document Generated:</span>
                <span class="detail-value"><?php echo date('F j, Y \a\t g:i A'); ?></span>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="verification-message">
            <?php echo $message; ?>
            <br><br>
            <strong>This verification confirms the authenticity of this document.</strong>
            <br>
            For questions, contact HR Department.
        </div>
        
        <div class="company-footer">
            <?php echo htmlspecialchars($leave['company_name'] ?? 'Corporate Apparel Inc.'); ?> &bull; 
            Leave Management System &bull; 
            <?php echo date('Y'); ?>
        </div>
    </div>

    <script>
        // Add some interactive features
        document.addEventListener('DOMContentLoaded', function() {
            // Log verification attempt
            console.log('Document verification attempted:', {
                documentId: '<?php echo $document_id; ?>',
                leaveId: <?php echo $leave_id; ?>,
                status: '<?php echo $status_class; ?>',
                timestamp: new Date().toISOString()
            });
            
            // Auto-refresh for real-time status (optional)
            // setTimeout(() => { location.reload(); }, 30000); // Refresh every 30 seconds
        });
    </script>
</body>
</html>