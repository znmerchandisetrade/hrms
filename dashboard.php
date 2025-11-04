<?php
// Include the configuration autoloader
require_once 'config/autoload.php';

// Initialize session using SessionConfig
SessionConfig::initialize();

// Check if user is logged in
if (!SessionConfig::get('logged_in')) {
    header("Location: login.php");
    exit();
}

include_once 'models/LeaveApplication.php';
include_once 'models/User.php';
include_once 'models/Reference.php';
include_once 'models/LeaveHistory.php';

$database = new Database();
$db = $database->getConnection();

$leaveApp = new LeaveApplication($db);
$user = new User($db);
$reference = new Reference($db);
$leaveHistory = new LeaveHistory($db);

// Get user data from session
$user_id = SessionConfig::get('user_id');
$role_name = SessionConfig::get('role_name') ?? 'Employee';
$full_name = SessionConfig::get('full_name') ?? 'User';
$company_name = SessionConfig::get('company_name') ?? 'N/A';
$brand_name = SessionConfig::get('brand_name') ?? 'N/A';
$department_name = SessionConfig::get('department_name') ?? 'N/A';
$store_name = SessionConfig::get('store_name') ?? 'N/A';
$date_hired = SessionConfig::get('date_hired') ?? date('Y-m-d');
$profile_image = SessionConfig::get('profile_image') ?? null;

// Get user's leave applications
try {
    $userLeavesArray = $leaveApp->getLeaveHistoryForUser($user_id);
    $userLeavesCount = count($userLeavesArray);
} catch (Exception $e) {
    // Fallback to original method
    try {
        $userLeaves = $leaveApp->getByUserId($user_id);
        $userLeavesArray = [];
        while ($row = $userLeaves->fetch(PDO::FETCH_ASSOC)) {
            $userLeavesArray[] = $row;
        }
        $userLeavesCount = count($userLeavesArray);
    } catch (Exception $fallbackException) {
        $userLeavesArray = [];
        $userLeavesCount = 0;
        error_log("Dashboard leave applications error: " . $fallbackException->getMessage());
    }
}

// Get user's other applications (Change of Dayoff, Late Letter, ID Renewal)
try {
    // Change of Dayoff applications
    $changeDayoffStmt = $db->prepare("SELECT * FROM change_dayoff_applications WHERE user_id = ? ORDER BY created_at DESC");
    $changeDayoffStmt->execute([$user_id]);
    $changeDayoffArray = $changeDayoffStmt->fetchAll(PDO::FETCH_ASSOC);
    $changeDayoffCount = count($changeDayoffArray);
    
    // Late Letter applications
    $lateLetterStmt = $db->prepare("SELECT * FROM late_letter_applications WHERE user_id = ? ORDER BY created_at DESC");
    $lateLetterStmt->execute([$user_id]);
    $lateLetterArray = $lateLetterStmt->fetchAll(PDO::FETCH_ASSOC);
    $lateLetterCount = count($lateLetterArray);
    
    // ID Renewal applications
    $idRenewalStmt = $db->prepare("SELECT * FROM id_renewal_applications WHERE user_id = ? ORDER BY created_at DESC");
    $idRenewalStmt->execute([$user_id]);
    $idRenewalArray = $idRenewalStmt->fetchAll(PDO::FETCH_ASSOC);
    $idRenewalCount = count($idRenewalArray);
    
} catch (Exception $e) {
    // Initialize empty arrays if tables don't exist yet
    $changeDayoffArray = [];
    $changeDayoffCount = 0;
    $lateLetterArray = [];
    $lateLetterCount = 0;
    $idRenewalArray = [];
    $idRenewalCount = 0;
    error_log("Dashboard other applications error: " . $e->getMessage());
}

// Get current user data for profile image
try {
    $current_user = $user->getById($user_id);
    if ($current_user && empty($profile_image) && !empty($current_user['profile_image'])) {
        $profile_image = $current_user['profile_image'];
        // Update session with profile image
        SessionConfig::set('profile_image', $profile_image);
    }
} catch (Exception $e) {
    // Continue with session data if getById fails
    error_log("Dashboard user data error: " . $e->getMessage());
}

// Get pending applications for admin/manager
if ($role_name != 'Employee') {
    try {
        $pendingLeaves = $leaveApp->getPendingApplications();
        $allUsers = $user->getAll();
        
        // Get pending applications for other types
        $pendingChangeDayoff = $db->query("SELECT cdo.*, u.full_name, u.profile_image 
                                         FROM change_dayoff_applications cdo 
                                         JOIN users u ON cdo.user_id = u.id 
                                         WHERE cdo.status = 'pending' 
                                         ORDER BY cdo.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        
        $pendingLateLetter = $db->query("SELECT ll.*, u.full_name, u.profile_image 
                                       FROM late_letter_applications ll 
                                       JOIN users u ON ll.user_id = u.id 
                                       WHERE ll.status = 'pending' 
                                       ORDER BY ll.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        
        $pendingIdRenewal = $db->query("SELECT idr.*, u.full_name, u.profile_image 
                                      FROM id_renewal_applications idr 
                                      JOIN users u ON idr.user_id = u.id 
                                      WHERE idr.status = 'pending' 
                                      ORDER BY idr.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        $pendingLeaves = [];
        $allUsers = [];
        $pendingChangeDayoff = [];
        $pendingLateLetter = [];
        $pendingIdRenewal = [];
        error_log("Dashboard admin data error: " . $e->getMessage());
    }
    
    // Get reference data for user creation
    try {
        $companies = $reference->getCompanies();
        $departments = $reference->getDepartments();
        $roles = $reference->getRoles();
        $brands = $reference->getBrands();
        $stores = $reference->getStores();
    } catch (Exception $e) {
        $companies = [];
        $departments = [];
        $roles = [];
        $brands = [];
        $stores = [];
        error_log("Dashboard reference data error: " . $e->getMessage());
    }
}

// Display success/error messages from session
$success_message = SessionConfig::get('success_message');
$error_message = SessionConfig::get('error_message');

// Clear messages after displaying
if ($success_message) {
    SessionConfig::remove('success_message');
}

if ($error_message) {
    SessionConfig::remove('error_message');
}

// Alternative: Check for old-style session messages (for backward compatibility)
if (!$success_message && isset($_SESSION['success'])) {
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']);
}

if (!$error_message && isset($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Leave Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Additional styles for fixed header compatibility */
        body {
            padding-top: 80px; /* Match the header height */
        }
        
        .table-container {
            transition: all 0.3s ease-in-out;
        }
        
        .table-container.collapsed {
            max-height: 0;
            overflow: hidden;
            opacity: 0;
        }
        
        .table-container.expanded {
            max-height: 2000px;
            opacity: 1;
        }
        
        .toggle-icon {
            transition: transform 0.3s ease;
        }
        
        .toggle-icon.rotated {
            transform: rotate(180deg);
        }
        
        .toggle-header {
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        .toggle-header:hover {
            background-color: #f8fafc;
        }
        
        /* Ensure modals appear above fixed header */
        .modal-overlay {
            z-index: 1001;
        }
        
        .modal-content {
            z-index: 1002;
        }
        
        /* Status badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-pending { background-color: #fef3cd; color: #856404; }
        .status-approved { background-color: #d1edff; color: #004085; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
        .status-completed { background-color: #d4edda; color: #155724; }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <!-- Display Messages -->
    <?php if (isset($success_message)): ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($success_message); ?></span>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
        </div>
    </div>
    <?php endif; ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if ($role_name == 'Employee'): ?>
        
        <!-- Employee Details Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <h2 class="text-lg font-semibold text-gray-800 mb-6">Employee Information</h2>
            
            <!-- Profile Header with Image -->
            <div class="flex items-center space-x-6 mb-6 pb-6 border-b border-gray-200">
                <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center overflow-hidden border-2 border-blue-200">
                    <?php 
                    $image_path = 'uploads/profiles/';
                    if (!empty($profile_image) && $profile_image != 'default-avatar.png' && file_exists($image_path . $profile_image)): 
                    ?>
                        <img src="<?php echo $image_path . $profile_image; ?>" 
                             alt="Profile" 
                             class="w-full h-full object-cover"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="w-full h-full bg-blue-100 rounded-full flex items-center justify-center hidden">
                            <i class="fas fa-user text-blue-600 text-2xl"></i>
                        </div>
                    <?php else: ?>
                        <div class="w-full h-full bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-blue-600 text-2xl"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($full_name); ?></h2>
                    <p class="text-gray-600"><?php echo htmlspecialchars($role_name); ?> • <?php echo htmlspecialchars($department_name); ?></p>
                    <p class="text-sm text-gray-500">Employee ID: <?php echo $_SESSION['user_id']; ?></p>
                </div>
            </div>

            <!-- Employee Details Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-500">Full Name</label>
                    <p class="text-gray-800 font-semibold"><?php echo htmlspecialchars($full_name); ?></p>
                </div>
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-500">Company Name</label>
                    <p class="text-gray-800"><?php echo htmlspecialchars($company_name); ?></p>
                </div>
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-500">Brand Name</label>
                    <p class="text-gray-800"><?php echo htmlspecialchars($brand_name); ?></p>
                </div>
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-500">Date Hired</label>
                    <p class="text-gray-800"><?php echo date('F j, Y', strtotime($date_hired)); ?></p>
                </div>
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-500">Role</label>
                    <p class="text-gray-800"><?php echo htmlspecialchars($role_name); ?></p>
                </div>
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-500">Store Name</label>
                    <p class="text-gray-800"><?php echo htmlspecialchars($store_name); ?></p>
                </div>
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-500">Department</label>
                    <p class="text-gray-800"><?php echo htmlspecialchars($department_name); ?></p>
                </div>
            </div>
        </div>

        <!-- Application Action Buttons -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <button onclick="openLeaveModal()" class="bg-blue-600 text-white p-4 rounded-lg hover:bg-blue-700 transition duration-300 flex items-center justify-center space-x-2">
                <i class="fas fa-calendar-plus text-xl"></i>
                <span>New Leave</span>
            </button>
            <button onclick="openChangeDayoffModal()" class="bg-green-600 text-white p-4 rounded-lg hover:bg-green-700 transition duration-300 flex items-center justify-center space-x-2">
                <i class="fas fa-exchange-alt text-xl"></i>
                <span>Change Dayoff</span>
            </button>
            <button onclick="openLateLetterModal()" class="bg-yellow-600 text-white p-4 rounded-lg hover:bg-yellow-700 transition duration-300 flex items-center justify-center space-x-2">
                <i class="fas fa-clock text-xl"></i>
                <span>Late Letter</span>
            </button>
            <button onclick="openIdRenewalModal()" class="bg-purple-600 text-white p-4 rounded-lg hover:bg-purple-700 transition duration-300 flex items-center justify-center space-x-2">
                <i class="fas fa-id-card text-xl"></i>
                <span>ID Renewal</span>
            </button>
        </div>

        <!-- Leave History -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
            <!-- Table Header with Toggle -->
            <div class="toggle-header p-6 border-b border-gray-200" onclick="toggleTable('leaveTable')">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-3">
                        <i id="leaveTableToggleIcon" class="fas fa-chevron-down toggle-icon text-gray-400"></i>
                        <h2 class="text-lg font-semibold text-gray-800">Leave Applications</h2>
                    </div>
                    <div class="flex space-x-3">
                        <span class="text-sm text-gray-500"><?php echo $userLeavesCount; ?> applications</span>
                    </div>
                </div>
            </div>
            
            <!-- Table Container -->
            <div id="leaveTableContainer" class="table-container expanded">
                <?php if ($userLeavesCount > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reliever</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approved By</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Processed</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Manager's Notes</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($userLeavesArray as $row): ?>
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <?php echo date('M j, Y', strtotime($row['start_date'])); ?> - 
                                    <?php echo date('M j, Y', strtotime($row['end_date'])); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <div>
                                        <p class="font-medium"><?php echo htmlspecialchars($row['reason']); ?></p>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo $row['day_off_count']; ?></td>
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($row['reliever_name']); ?></td>
                                <td class="px-4 py-3">
                                    <span class="status-badge status-<?php echo $row['status']; ?>">
                                        <?php if ($row['status'] == 'pending'): ?>
                                            <i class="fas fa-clock mr-1"></i>
                                        <?php elseif ($row['status'] == 'approved'): ?>
                                            <i class="fas fa-check mr-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-times mr-1"></i>
                                        <?php endif; ?>
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <?php if ($row['status'] != 'pending'): ?>
                                        <?php if (!empty($row['approved_by_full_name'])): ?>
                                            <div class="text-left">
                                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($row['approved_by_full_name']); ?></p>
                                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($row['approved_by_role_name'] ?? 'Manager'); ?></p>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-gray-500">System</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-gray-400">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    <?php if ($row['status'] != 'pending'): ?>
                                        <?php 
                                        $date_display = '';
                                        if (!empty($row['processed_date'])) {
                                            $date_display = date('M j, Y g:i A', strtotime($row['processed_date']));
                                        } elseif (!empty($row['approved_at'])) {
                                            $date_display = date('M j, Y g:i A', strtotime($row['approved_at']));
                                        } else {
                                            $date_display = date('M j, Y g:i A', strtotime($row['created_at']));
                                        }
                                        echo $date_display;
                                        ?>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 max-w-xs">
                                    <?php if (!empty($row['manager_notes'])): ?>
                                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                            <p class="text-sm text-blue-800"><?php echo htmlspecialchars($row['manager_notes']); ?></p>
                                        </div>
                                    <?php elseif ($row['status'] != 'pending'): ?>
                                        <span class="text-gray-400 text-sm">No notes provided</span>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex space-x-2">
                                        <?php if ($row['status'] != 'pending'): ?>
                                            <!-- Print Button -->
                                            <button onclick="printLeaveForm(<?php echo $row['id']; ?>)" 
                                                    class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 transition duration-300 flex items-center">
                                                <i class="fas fa-print mr-1"></i>
                                            </button>
                                            
                                            <!-- Print Letter Button for Approved Leaves -->
                                            <?php if ($row['status'] == 'approved'): ?>
                                                <button onclick="printStoreLetter(<?php echo $row['id']; ?>)" 
                                                        class="bg-purple-600 text-white px-3 py-1 rounded text-xs hover:bg-purple-700 transition duration-300 flex items-center">
                                                    <i class="fas fa-envelope mr-1"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <button onclick="editLeave(<?php echo $row['id']; ?>)" 
                                                    class="bg-yellow-600 text-white px-3 py-1 rounded text-xs hover:bg-yellow-700 transition duration-300 flex items-center">
                                                <i class="fas fa-edit mr-1"></i>
                                            </button>
                                            <button onclick="cancelLeave(<?php echo $row['id']; ?>)" 
                                                    class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700 transition duration-300 flex items-center">
                                                <i class="fas fa-times mr-1"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-8">
                    <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                    <p class="text-gray-500">No leave applications found.</p>
                    <p class="text-sm text-gray-400 mt-2">Click "New Leave Application" to submit your first leave request.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Change of Dayoff Applications -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
            <div class="toggle-header p-6 border-b border-gray-200" onclick="toggleTable('changeDayoffTable')">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-3">
                        <i id="changeDayoffTableToggleIcon" class="fas fa-chevron-down toggle-icon text-gray-400"></i>
                        <h2 class="text-lg font-semibold text-gray-800">Change of Dayoff Applications</h2>
                    </div>
                    <div class="flex space-x-3">
                        <span class="text-sm text-gray-500"><?php echo $changeDayoffCount; ?> applications</span>
                    </div>
                </div>
            </div>
            
            <div id="changeDayoffTableContainer" class="table-container expanded">
                <?php if ($changeDayoffCount > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Dayoff</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested Dayoff</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Effective Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approved By</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Applied</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($changeDayoffArray as $row): ?>
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <div class="flex items-center">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <?php echo htmlspecialchars($row['current_dayoff']); ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <div class="flex items-center">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <?php echo htmlspecialchars($row['requested_dayoff']); ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <?php echo date('M j, Y', strtotime($row['effective_date'])); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <div class="max-w-xs">
                                        <p class="truncate" title="<?php echo htmlspecialchars($row['reason']); ?>">
                                            <?php echo htmlspecialchars($row['reason']); ?>
                                        </p>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="status-badge status-<?php echo $row['status']; ?>">
                                        <?php if ($row['status'] == 'pending'): ?>
                                            <i class="fas fa-clock mr-1"></i>
                                        <?php elseif ($row['status'] == 'approved'): ?>
                                            <i class="fas fa-check mr-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-times mr-1"></i>
                                        <?php endif; ?>
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <?php if ($row['status'] != 'pending' && !empty($row['approved_by'])): ?>
                                        <?php 
                                        // Get approver name from users table
                                        try {
                                            $approverStmt = $db->prepare("SELECT full_name FROM users WHERE id = ?");
                                            $approverStmt->execute([$row['approved_by']]);
                                            $approver = $approverStmt->fetch(PDO::FETCH_ASSOC);
                                            echo htmlspecialchars($approver['full_name'] ?? 'Manager');
                                        } catch (Exception $e) {
                                            echo 'Manager';
                                        }
                                        ?>
                                    <?php elseif ($row['status'] != 'pending'): ?>
                                        <span class="text-gray-500">System</span>
                                    <?php else: ?>
                                        <span class="text-gray-400">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    <?php echo date('M j, Y g:i A', strtotime($row['created_at'])); ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex space-x-2">
                                        <?php if ($row['status'] == 'approved'): ?>
                                            <button onclick="printChangeDayoff(<?php echo $row['id']; ?>)" 
                                                    class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 transition duration-300 flex items-center">
                                                <i class="fas fa-print mr-1"></i> Print
                                            </button>
                                        <?php elseif ($row['status'] == 'pending'): ?>
                                            <button onclick="editChangeDayoff(<?php echo $row['id']; ?>)" 
                                                    class="bg-yellow-600 text-white px-3 py-1 rounded text-xs hover:bg-yellow-700 transition duration-300 flex items-center">
                                                <i class="fas fa-edit mr-1"></i> Edit
                                            </button>
                                            <button onclick="cancelChangeDayoff(<?php echo $row['id']; ?>)" 
                                                    class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700 transition duration-300 flex items-center">
                                                <i class="fas fa-times mr-1"></i> Cancel
                                            </button>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-xs">No actions</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-8">
                    <i class="fas fa-exchange-alt text-gray-300 text-4xl mb-3"></i>
                    <p class="text-gray-500">No change of dayoff applications found.</p>
                    <p class="text-sm text-gray-400 mt-2">Click "Change Dayoff" button to submit your first request.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Late Letter Applications -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
            <div class="toggle-header p-6 border-b border-gray-200" onclick="toggleTable('lateLetterTable')">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-3">
                        <i id="lateLetterTableToggleIcon" class="fas fa-chevron-down toggle-icon text-gray-400"></i>
                        <h2 class="text-lg font-semibold text-gray-800">Late Letter Applications</h2>
                    </div>
                    <div class="flex space-x-3">
                        <span class="text-sm text-gray-500"><?php echo $lateLetterCount; ?> applications</span>
                    </div>
                </div>
            </div>
            
            <div id="lateLetterTableContainer" class="table-container expanded">
                <?php if ($lateLetterCount > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Late Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Arrival Time</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approved By</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Applied</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($lateLetterArray as $row): ?>
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo date('M j, Y', strtotime($row['late_date'])); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo date('g:i A', strtotime($row['arrival_time'])); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($row['reason']); ?></td>
                                <td class="px-4 py-3">
                                    <span class="status-badge status-<?php echo $row['status']; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <?php if ($row['status'] != 'pending' && !empty($row['approved_by'])): ?>
                                        <?php 
                                        try {
                                            $approverStmt = $db->prepare("SELECT full_name FROM users WHERE id = ?");
                                            $approverStmt->execute([$row['approved_by']]);
                                            $approver = $approverStmt->fetch(PDO::FETCH_ASSOC);
                                            echo htmlspecialchars($approver['full_name'] ?? 'Manager');
                                        } catch (Exception $e) {
                                            echo 'Manager';
                                        }
                                        ?>
                                    <?php elseif ($row['status'] != 'pending'): ?>
                                        <span class="text-gray-500">System</span>
                                    <?php else: ?>
                                        <span class="text-gray-400">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    <?php echo date('M j, Y g:i A', strtotime($row['created_at'])); ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex space-x-2">
                                        <?php if ($row['status'] == 'approved'): ?>
                                            <button onclick="printLateLetter(<?php echo $row['id']; ?>)" 
                                                    class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 transition duration-300 flex items-center">
                                                <i class="fas fa-print mr-1"></i> Print
                                            </button>
                                        <?php elseif ($row['status'] == 'pending'): ?>
                                            <button onclick="editLateLetter(<?php echo $row['id']; ?>)" 
                                                    class="bg-yellow-600 text-white px-3 py-1 rounded text-xs hover:bg-yellow-700 transition duration-300 flex items-center">
                                                <i class="fas fa-edit mr-1"></i> Edit
                                            </button>
                                            <button onclick="cancelLateLetter(<?php echo $row['id']; ?>)" 
                                                    class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700 transition duration-300 flex items-center">
                                                <i class="fas fa-times mr-1"></i> Cancel
                                            </button>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-xs">No actions</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-8">
                    <i class="fas fa-clock text-gray-300 text-4xl mb-3"></i>
                    <p class="text-gray-500">No late letter applications found.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ID Renewal Applications -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="toggle-header p-6 border-b border-gray-200" onclick="toggleTable('idRenewalTable')">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-3">
                        <i id="idRenewalTableToggleIcon" class="fas fa-chevron-down toggle-icon text-gray-400"></i>
                        <h2 class="text-lg font-semibold text-gray-800">ID Renewal Applications</h2>
                    </div>
                    <div class="flex space-x-3">
                        <span class="text-sm text-gray-500"><?php echo $idRenewalCount; ?> applications</span>
                    </div>
                </div>
            </div>
            
            <div id="idRenewalTableContainer" class="table-container expanded">
                <?php if ($idRenewalCount > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Validity</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested Validity</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approved By</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Applied</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($idRenewalArray as $row): ?>
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <?php echo date('M j, Y', strtotime($row['current_valid_from'])); ?> - 
                                    <?php echo date('M j, Y', strtotime($row['current_valid_to'])); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <?php echo date('M j, Y', strtotime($row['requested_valid_from'])); ?> - 
                                    <?php echo date('M j, Y', strtotime($row['requested_valid_to'])); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($row['reason']); ?></td>
                                <td class="px-4 py-3">
                                    <span class="status-badge status-<?php echo $row['status']; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <?php if ($row['status'] != 'pending' && !empty($row['approved_by'])): ?>
                                        <?php 
                                        try {
                                            $approverStmt = $db->prepare("SELECT full_name FROM users WHERE id = ?");
                                            $approverStmt->execute([$row['approved_by']]);
                                            $approver = $approverStmt->fetch(PDO::FETCH_ASSOC);
                                            echo htmlspecialchars($approver['full_name'] ?? 'Manager');
                                        } catch (Exception $e) {
                                            echo 'Manager';
                                        }
                                        ?>
                                    <?php elseif ($row['status'] != 'pending'): ?>
                                        <span class="text-gray-500">System</span>
                                    <?php else: ?>
                                        <span class="text-gray-400">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    <?php echo date('M j, Y g:i A', strtotime($row['created_at'])); ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex space-x-2">
                                        <?php if ($row['status'] == 'approved'): ?>
                                            <button onclick="printIdRenewal(<?php echo $row['id']; ?>)" 
                                                    class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 transition duration-300 flex items-center">
                                                <i class="fas fa-print mr-1"></i> Print
                                            </button>
                                        <?php elseif ($row['status'] == 'pending'): ?>
                                            <button onclick="editIdRenewal(<?php echo $row['id']; ?>)" 
                                                    class="bg-yellow-600 text-white px-3 py-1 rounded text-xs hover:bg-yellow-700 transition duration-300 flex items-center">
                                                <i class="fas fa-edit mr-1"></i> Edit
                                            </button>
                                            <button onclick="cancelIdRenewal(<?php echo $row['id']; ?>)" 
                                                    class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700 transition duration-300 flex items-center">
                                                <i class="fas fa-times mr-1"></i> Cancel
                                            </button>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-xs">No actions</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-8">
                    <i class="fas fa-id-card text-gray-300 text-4xl mb-3"></i>
                    <p class="text-gray-500">No ID renewal applications found.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php else: ?>
        
        <!-- Admin/Manager Dashboard -->
        <div class="grid grid-cols-1 gap-8">
            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <?php 
                $stats = $leaveApp->getStats();
                $historyStats = $leaveHistory->getStats();
                $statItems = [
                    ['count' => $stats['pending'] ?? 0, 'label' => 'Pending Leaves', 'icon' => 'calendar-times', 'color' => 'yellow'],
                    ['count' => count($pendingChangeDayoff), 'label' => 'Pending Dayoff Changes', 'icon' => 'exchange-alt', 'color' => 'blue'],
                    ['count' => count($pendingLateLetter), 'label' => 'Pending Late Letters', 'icon' => 'clock', 'color' => 'orange'],
                    ['count' => count($pendingIdRenewal), 'label' => 'Pending ID Renewals', 'icon' => 'id-card', 'color' => 'purple']
                ];
                ?>
                <?php foreach ($statItems as $item): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-<?php echo $item['color']; ?>-100 rounded-lg">
                            <i class="fas fa-<?php echo $item['icon']; ?> text-<?php echo $item['color']; ?>-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500"><?php echo $item['label']; ?></h3>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $item['count']; ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pending Leave Applications Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-semibold text-gray-800">Pending Leave Applications</h2>
                    <div class="flex space-x-3">
                        <a href="leave_history.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-300 text-sm font-medium">
                            <i class="fas fa-history mr-2"></i>View History
                        </a>
                    </div>
                </div>
                
                <?php if ($pendingLeaves && $pendingLeaves->rowCount() > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reliever</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Applied</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php while ($row = $pendingLeaves->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-4 py-3">
                                    <button onclick="viewEmployeeDetails(<?php echo $row['user_id']; ?>)" 
                                            class="flex items-center space-x-3 text-left hover:text-blue-600 transition duration-150">
                                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center overflow-hidden border border-blue-200">
                                            <?php 
                                            $image_path = 'uploads/profiles/';
                                            if (!empty($row['profile_image']) && $row['profile_image'] != 'default-avatar.png' && file_exists($image_path . $row['profile_image'])): 
                                            ?>
                                                <img src="<?php echo $image_path . $row['profile_image']; ?>" alt="Profile" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <i class="fas fa-user text-blue-600 text-sm"></i>
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['full_name']); ?></span>
                                    </button>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <?php echo date('M j, Y', strtotime($row['start_date'])); ?> - 
                                    <?php echo date('M j, Y', strtotime($row['end_date'])); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($row['reason']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo $row['day_off_count']; ?></td>
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($row['reliever_name']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    <?php echo date('M j, Y g:i A', strtotime($row['created_at'])); ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex space-x-2">
                                        <button onclick="openApprovalModal(<?php echo $row['id']; ?>, 'approved')" 
                                                class="bg-green-600 text-white px-3 py-1 rounded text-xs hover:bg-green-700 transition duration-300 flex items-center">
                                            <i class="fas fa-check mr-1"></i>Approve
                                        </button>
                                        <button onclick="openApprovalModal(<?php echo $row['id']; ?>, 'rejected')" 
                                                class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700 transition duration-300 flex items-center">
                                            <i class="fas fa-times mr-1"></i>Reject
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-8">
                    <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                    <p class="text-gray-500">No pending leave applications.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Pending Change of Dayoff Applications -->
            <?php if (count($pendingChangeDayoff) > 0): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-6">Pending Change of Dayoff Applications</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Dayoff</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested Dayoff</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Effective Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Applied</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($pendingChangeDayoff as $row): ?>
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-4 py-3">
                                    <button onclick="viewEmployeeDetails(<?php echo $row['user_id']; ?>)" 
                                            class="flex items-center space-x-3 text-left hover:text-blue-600 transition duration-150">
                                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center overflow-hidden border border-blue-200">
                                            <?php if (!empty($row['profile_image']) && $row['profile_image'] != 'default-avatar.png' && file_exists('uploads/profiles/' . $row['profile_image'])): ?>
                                                <img src="uploads/profiles/<?php echo $row['profile_image']; ?>" alt="Profile" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <i class="fas fa-user text-blue-600 text-sm"></i>
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['full_name']); ?></span>
                                    </button>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($row['current_dayoff']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($row['requested_dayoff']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($row['reason']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo date('M j, Y', strtotime($row['effective_date'])); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    <?php echo date('M j, Y g:i A', strtotime($row['created_at'])); ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex space-x-2">
                                        <button onclick="openChangeDayoffApprovalModal(<?php echo $row['id']; ?>, 'approved')" 
                                                class="bg-green-600 text-white px-3 py-1 rounded text-xs hover:bg-green-700 transition duration-300 flex items-center">
                                            <i class="fas fa-check mr-1"></i>Approve
                                        </button>
                                        <button onclick="openChangeDayoffApprovalModal(<?php echo $row['id']; ?>, 'rejected')" 
                                                class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700 transition duration-300 flex items-center">
                                            <i class="fas fa-times mr-1"></i>Reject
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Pending Late Letter Applications -->
            <?php if (count($pendingLateLetter) > 0): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-6">Pending Late Letter Applications</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Late Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Arrival Time</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Applied</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($pendingLateLetter as $row): ?>
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-4 py-3">
                                    <button onclick="viewEmployeeDetails(<?php echo $row['user_id']; ?>)" 
                                            class="flex items-center space-x-3 text-left hover:text-blue-600 transition duration-150">
                                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center overflow-hidden border border-blue-200">
                                            <?php if (!empty($row['profile_image']) && $row['profile_image'] != 'default-avatar.png' && file_exists('uploads/profiles/' . $row['profile_image'])): ?>
                                                <img src="uploads/profiles/<?php echo $row['profile_image']; ?>" alt="Profile" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <i class="fas fa-user text-blue-600 text-sm"></i>
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['full_name']); ?></span>
                                    </button>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo date('M j, Y', strtotime($row['late_date'])); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo date('g:i A', strtotime($row['arrival_time'])); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($row['reason']); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    <?php echo date('M j, Y g:i A', strtotime($row['created_at'])); ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex space-x-2">
                                        <button onclick="openLateLetterApprovalModal(<?php echo $row['id']; ?>, 'approved')" 
                                                class="bg-green-600 text-white px-3 py-1 rounded text-xs hover:bg-green-700 transition duration-300 flex items-center">
                                            <i class="fas fa-check mr-1"></i>Approve
                                        </button>
                                        <button onclick="openLateLetterApprovalModal(<?php echo $row['id']; ?>, 'rejected')" 
                                                class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700 transition duration-300 flex items-center">
                                            <i class="fas fa-times mr-1"></i>Reject
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Pending ID Renewal Applications -->
            <?php if (count($pendingIdRenewal) > 0): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-semibold text-gray-800">Pending ID Renewal Applications</h2>
                    <span class="bg-purple-100 text-purple-800 text-sm font-medium px-3 py-1 rounded-full">
                        <?php echo count($pendingIdRenewal); ?> pending
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Validity</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested Validity</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days Remaining</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Applied</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($pendingIdRenewal as $row): 
                                $currentTo = new DateTime($row['current_valid_to']);
                                $today = new DateTime();
                                $daysRemaining = $currentTo->diff($today)->days;
                                $isExpiringSoon = $daysRemaining <= 30;
                                $isExpired = $currentTo < $today;
                            ?>
                            <tr class="hover:bg-gray-50 transition duration-150 <?php echo $isExpired ? 'bg-red-50' : ($isExpiringSoon ? 'bg-yellow-50' : ''); ?>">
                                <td class="px-4 py-3">
                                    <button onclick="viewEmployeeDetails(<?php echo $row['user_id']; ?>)" 
                                            class="flex items-center space-x-3 text-left hover:text-blue-600 transition duration-150">
                                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center overflow-hidden border border-blue-200">
                                            <?php if (!empty($row['profile_image']) && $row['profile_image'] != 'default-avatar.png' && file_exists('uploads/profiles/' . $row['profile_image'])): ?>
                                                <img src="uploads/profiles/<?php echo $row['profile_image']; ?>" alt="Profile" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <i class="fas fa-user text-blue-600 text-sm"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-left">
                                            <span class="text-sm font-medium text-gray-900 block"><?php echo htmlspecialchars($row['full_name']); ?></span>
                                            <span class="text-xs text-gray-500">ID: <?php echo $row['user_id']; ?></span>
                                        </div>
                                    </button>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <div class="text-center">
                                        <div class="font-medium">
                                            <?php echo date('M j, Y', strtotime($row['current_valid_from'])); ?> - 
                                            <?php echo date('M j, Y', strtotime($row['current_valid_to'])); ?>
                                        </div>
                                        <?php if ($isExpired): ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 mt-1">
                                                <i class="fas fa-exclamation-triangle mr-1"></i>Expired
                                            </span>
                                        <?php elseif ($isExpiringSoon): ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 mt-1">
                                                <i class="fas fa-clock mr-1"></i>Expiring Soon
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <div class="text-center bg-green-50 border border-green-200 rounded-lg p-2">
                                        <div class="font-medium text-green-800">
                                            <?php echo date('M j, Y', strtotime($row['requested_valid_from'])); ?> - 
                                            <?php echo date('M j, Y', strtotime($row['requested_valid_to'])); ?>
                                        </div>
                                        <?php
                                        $currentDuration = (strtotime($row['current_valid_to']) - strtotime($row['current_valid_from'])) / (60 * 60 * 24);
                                        $requestedDuration = (strtotime($row['requested_valid_to']) - strtotime($row['requested_valid_from'])) / (60 * 60 * 24);
                                        $durationDifference = $requestedDuration - $currentDuration;
                                        ?>
                                        <span class="text-xs <?php echo $durationDifference > 0 ? 'text-green-600' : ($durationDifference < 0 ? 'text-red-600' : 'text-gray-600'); ?>">
                                            <?php echo $durationDifference > 0 ? "+{$durationDifference} days" : ($durationDifference < 0 ? "{$durationDifference} days" : "No change"); ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <div class="max-w-xs">
                                        <p class="truncate" title="<?php echo htmlspecialchars($row['reason']); ?>">
                                            <?php echo htmlspecialchars($row['reason']); ?>
                                        </p>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-center">
                                    <?php if ($isExpired): ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <i class="fas fa-ban mr-1"></i>Expired
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium 
                                            <?php echo $daysRemaining <= 7 ? 'bg-red-100 text-red-800' : 
                                                   ($daysRemaining <= 30 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'); ?>">
                                            <i class="fas fa-calendar-day mr-1"></i>
                                            <?php echo $daysRemaining; ?> days
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    <?php echo date('M j, Y g:i A', strtotime($row['created_at'])); ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex space-x-2">
                                        <button onclick="openIdRenewalApprovalModal(<?php echo $row['id']; ?>, 'approved')" 
                                                class="bg-green-600 text-white px-3 py-2 rounded text-sm hover:bg-green-700 transition duration-300 flex items-center">
                                            <i class="fas fa-check mr-2"></i>Approve
                                        </button>
                                        <button onclick="openIdRenewalApprovalModal(<?php echo $row['id']; ?>, 'rejected')" 
                                                class="bg-red-600 text-white px-3 py-2 rounded text-sm hover:bg-red-700 transition duration-300 flex items-center">
                                            <i class="fas fa-times mr-2"></i>Reject
                                        </button>
                                        <button onclick="viewIdRenewalDetails(<?php echo $row['id']; ?>)" 
                                                class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700 transition duration-300 flex items-center">
                                            <i class="fas fa-eye mr-2"></i>View
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
            <!-- Show empty state if no pending ID renewals -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 text-center">
                <i class="fas fa-id-card text-gray-300 text-4xl mb-3"></i>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">No Pending ID Renewals</h3>
                <p class="text-gray-500">There are no pending ID renewal applications at the moment.</p>
            </div>
            <?php endif; ?>

        </div>

        <?php endif; ?>
    </main>

    <!-- Leave Application Modal -->
    <div id="leaveModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center p-4 modal-overlay">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto modal-content">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800">New Leave Application</h3>
                    <button onclick="closeLeaveModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <form id="leaveForm" action="submit_leave.php" method="POST" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Company</label>
                    <input type="text" name="company" required value="<?php echo htmlspecialchars($company_name); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" readonly>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Store/Brand</label>
                    <input type="text" name="store_brand" required value="<?php echo htmlspecialchars($store_name . ' - ' . $brand_name); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" readonly>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <input type="date" name="start_date" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                        <input type="date" name="end_date" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                    <textarea name="reason" rows="3" required 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                              placeholder="Please specify the reason for your leave..."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reliever Name</label>
                    <input type="text" name="reliever_name" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                           placeholder="Person who will cover your duties">
                </div>

                <input type="hidden" name="day_off_count" id="dayOffCount">

                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeLeaveModal()" 
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-300">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-300 font-medium">
                        Submit Application
                    </button>
                </div>
            </form>
        </div>
    </div>

   <!-- Change of Dayoff Modal -->
<div id="changeDayoffModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center p-4 modal-overlay">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto modal-content">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">Change of Dayoff Request</h3>
                <button onclick="closeChangeDayoffModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <form id="changeDayoffForm" action="submit_change_dayoff.php" method="POST" class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Dayoff</label>
                    <select name="current_dayoff" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select current dayoff</option>
                        <option value="Sunday">Sunday</option>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Requested Dayoff</label>
                    <select name="requested_dayoff" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="">Select requested dayoff</option>
                        <option value="Sunday">Sunday</option>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Effective Date</label>
                <input type="date" name="effective_date" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                <p class="text-xs text-gray-500 mt-1">Select when you want the change to take effect</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Change</label>
                <textarea name="reason" rows="3" required 
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                          placeholder="Please explain why you need to change your dayoff..."></textarea>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                <h4 class="text-sm font-semibold text-blue-800 mb-2">Important Notes</h4>
                <ul class="text-xs text-blue-700 space-y-1">
                    <li>• Changes require manager approval</li>
                    <li>• Effective date must be at least 1 day from today</li>
                    <li>• Changes may affect your schedule and responsibilities</li>
                </ul>
            </div>

            <div class="flex space-x-3 pt-4">
                <button type="button" onclick="closeChangeDayoffModal()" 
                        class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-300">
                    Cancel
                </button>
                <button type="submit" 
                        class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-300 font-medium">
                    Submit Request
                </button>
            </div>
        </form>
    </div>
</div>

    <!-- Late Letter Modal -->
    <div id="lateLetterModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center p-4 modal-overlay">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto modal-content">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800">Late Letter Request</h3>
                    <button onclick="closeLateLetterModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <form id="lateLetterForm" action="submit_late_letter.php" method="POST" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Late Date</label>
                    <input type="date" name="late_date" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           max="<?php echo date('Y-m-d'); ?>">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Arrival Time</label>
                    <input type="time" name="arrival_time" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Being Late</label>
                    <textarea name="reason" rows="3" required 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                              placeholder="Please explain why you were late..."></textarea>
                </div>

                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeLateLetterModal()" 
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-300">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-1 bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition duration-300 font-medium">
                        Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ID Renewal Modal -->
    <div id="idRenewalModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center p-4 modal-overlay">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto modal-content">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800">ID Renewal Request</h3>
                    <button onclick="closeIdRenewalModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <form id="idRenewalForm" action="submit_id_renewal.php" method="POST" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current ID Expiry Date</label>
                    <input type="date" name="current_id_expiry" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Renewal Type</label>
                    <select name="renewal_type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select renewal type</option>
                        <option value="Annual Renewal">Annual Renewal</option>
                        <option value="Replacement">Replacement</option>
                        <option value="Update Information">Update Information</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Renewal</label>
                    <textarea name="reason" rows="3" required 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                              placeholder="Please specify the reason for ID renewal..."></textarea>
                </div>

                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeIdRenewalModal()" 
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-300">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-1 bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition duration-300 font-medium">
                        Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Approval Modal -->
    <?php if ($role_name != 'Employee'): ?>
    <div id="approvalModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center p-4 modal-overlay">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto modal-content">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800" id="approvalTitle">Approve Leave Application</h3>
                    <button onclick="closeApprovalModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <form id="approvalForm" action="update_status.php" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="leave_id" id="leaveId">
                <input type="hidden" name="status" id="leaveStatus">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Manager's Comments</label>
                    <textarea name="manager_notes" rows="4" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                              placeholder="Add any comments or notes for the employee..."></textarea>
                </div>

                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeApprovalModal()" 
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-300">
                        Cancel
                    </button>
                    <button type="submit" id="approvalSubmitBtn"
                            class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-300 font-medium">
                        <i class="fas fa-save mr-2"></i>Save Approval
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Change Dayoff Approval Modal -->
    <?php if ($role_name != 'Employee'): ?>
    <div id="changeDayoffApprovalModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center p-4 modal-overlay">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto modal-content">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800" id="changeDayoffApprovalTitle">Approve Change of Dayoff</h3>
                    <button onclick="closeChangeDayoffApprovalModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <form id="changeDayoffApprovalForm" action="update_change_dayoff_status.php" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="application_id" id="changeDayoffId">
                <input type="hidden" name="status" id="changeDayoffStatus">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Manager's Comments</label>
                    <textarea name="manager_notes" rows="4" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                              placeholder="Add any comments or notes for the employee..."></textarea>
                </div>

                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeChangeDayoffApprovalModal()" 
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-300">
                        Cancel
                    </button>
                    <button type="submit" id="changeDayoffSubmitBtn"
                            class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-300 font-medium">
                        <i class="fas fa-save mr-2"></i>Save Approval
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Late Letter Approval Modal -->
    <?php if ($role_name != 'Employee'): ?>
    <div id="lateLetterApprovalModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center p-4 modal-overlay">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto modal-content">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800" id="lateLetterApprovalTitle">Approve Late Letter</h3>
                    <button onclick="closeLateLetterApprovalModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <form id="lateLetterApprovalForm" action="update_late_letter_status.php" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="application_id" id="lateLetterId">
                <input type="hidden" name="status" id="lateLetterStatus">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Manager's Comments</label>
                    <textarea name="manager_notes" rows="4" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                              placeholder="Add any comments regarding the late arrival..."></textarea>
                </div>

                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeLateLetterApprovalModal()" 
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-300">
                        Cancel
                    </button>
                    <button type="submit" id="lateLetterSubmitBtn"
                            class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-300 font-medium">
                        <i class="fas fa-save mr-2"></i>Save Approval
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- ID Renewal Approval Modal -->
    <?php if ($role_name != 'Employee'): ?>
    <div id="idRenewalApprovalModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center p-4 modal-overlay">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto modal-content">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800" id="idRenewalApprovalTitle">Approve ID Renewal</h3>
                    <button onclick="closeIdRenewalApprovalModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <form id="idRenewalApprovalForm" action="update_id_renewal_status.php" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="application_id" id="idRenewalId">
                <input type="hidden" name="status" id="idRenewalStatus">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Manager's Comments</label>
                    <textarea name="manager_notes" rows="4" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                              placeholder="Add any comments regarding the ID renewal..."></textarea>
                </div>

                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeIdRenewalApprovalModal()" 
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-300">
                        Cancel
                    </button>
                    <button type="submit" id="idRenewalSubmitBtn"
                            class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-300 font-medium">
                        <i class="fas fa-save mr-2"></i>Save Approval
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Employee Details Modal -->
    <?php if ($role_name != 'Employee'): ?>
    <div id="employeeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center p-4 modal-overlay">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto modal-content">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800">Employee Details</h3>
                    <button onclick="closeEmployeeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div id="employeeDetails" class="p-6">
                <!-- Employee details will be loaded here via AJAX -->
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Create User Modal (Admin/Manager) -->
    <?php if ($role_name != 'Employee'): ?>
    <div id="userModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center p-4 modal-overlay">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto modal-content">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800">Create New User</h3>
                    <button onclick="closeUserModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <form id="userForm" action="create_user.php" method="POST" class="p-6 space-y-4" enctype="multipart/form-data">
                <?php include 'includes/user_form_fields.php'; ?>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
    // Table toggle functionality
    function toggleTable(tableType) {
        const tableContainer = document.getElementById(tableType + 'Container');
        const toggleIcon = document.getElementById(tableType + 'ToggleIcon');
        
        const isExpanded = tableContainer.classList.contains('expanded');
        
        if (isExpanded) {
            tableContainer.classList.remove('expanded');
            tableContainer.classList.add('collapsed');
            toggleIcon.classList.add('rotated');
        } else {
            tableContainer.classList.remove('collapsed');
            tableContainer.classList.add('expanded');
            toggleIcon.classList.remove('rotated');
        }
    }

    // Leave Modal Functions
    function openLeaveModal() {
        document.getElementById('leaveModal').classList.remove('hidden');
        document.getElementById('leaveModal').classList.add('flex');
    }

    function closeLeaveModal() {
        document.getElementById('leaveModal').classList.remove('flex');
        document.getElementById('leaveModal').classList.add('hidden');
    }

    // Change of Dayoff Modal Functions
    function openChangeDayoffModal() {
        document.getElementById('changeDayoffModal').classList.remove('hidden');
        document.getElementById('changeDayoffModal').classList.add('flex');
    }

    function closeChangeDayoffModal() {
        document.getElementById('changeDayoffModal').classList.remove('flex');
        document.getElementById('changeDayoffModal').classList.add('hidden');
    }

    // Late Letter Modal Functions
    function openLateLetterModal() {
        document.getElementById('lateLetterModal').classList.remove('hidden');
        document.getElementById('lateLetterModal').classList.add('flex');
    }

    function closeLateLetterModal() {
        document.getElementById('lateLetterModal').classList.remove('flex');
        document.getElementById('lateLetterModal').classList.add('hidden');
    }

    // ID Renewal Modal Functions with Enhanced Features
    function openIdRenewalModal() {
        document.getElementById('idRenewalModal').classList.remove('hidden');
        document.getElementById('idRenewalModal').classList.add('flex');
        
        // Set default dates
        const today = new Date();
        const oneYearFromNow = new Date(today);
        oneYearFromNow.setFullYear(today.getFullYear() + 1);
        
        // Set current validity to today - 1 year ago
        const oneYearAgo = new Date(today);
        oneYearAgo.setFullYear(today.getFullYear() - 1);
        
        document.getElementById('currentValidFrom').value = formatDate(oneYearAgo);
        document.getElementById('currentValidTo').value = formatDate(today);
        document.getElementById('requestedValidFrom').value = formatDate(today);
        document.getElementById('requestedValidTo').value = formatDate(oneYearFromNow);
        
        // Calculate initial durations
        calculateDurations();
        updateReasonCharCount();
    }

    function closeIdRenewalModal() {
        document.getElementById('idRenewalModal').classList.remove('flex');
        document.getElementById('idRenewalModal').classList.add('hidden');
        resetIdRenewalForm();
    }

    function resetIdRenewalForm() {
        document.getElementById('idRenewalForm').reset();
        document.getElementById('renewalSummary').classList.add('hidden');
        document.getElementById('submitRenewalBtn').disabled = false;
    }

    function formatDate(date) {
        return date.toISOString().split('T')[0];
    }

    function calculateDurations() {
        const currentFrom = new Date(document.getElementById('currentValidFrom').value);
        const currentTo = new Date(document.getElementById('currentValidTo').value);
        const requestedFrom = new Date(document.getElementById('requestedValidFrom').value);
        const requestedTo = new Date(document.getElementById('requestedValidTo').value);
        
        // Calculate durations in days
        const currentDuration = Math.ceil((currentTo - currentFrom) / (1000 * 60 * 60 * 24));
        const requestedDuration = Math.ceil((requestedTo - requestedFrom) / (1000 * 60 * 60 * 24));
        
        // Update duration displays
        document.getElementById('currentDuration').textContent = `${currentDuration} days`;
        document.getElementById('requestedDuration').textContent = `${requestedDuration} days`;
        
        // Calculate difference
        const durationDifference = requestedDuration - currentDuration;
        const differenceElement = document.getElementById('renewalDifference');
        
        if (durationDifference > 0) {
            differenceElement.textContent = `(+${durationDifference} days)`;
            differenceElement.className = 'text-xs ml-2 text-green-600 font-medium';
        } else if (durationDifference < 0) {
            differenceElement.textContent = `(${durationDifference} days)`;
            differenceElement.className = 'text-xs ml-2 text-red-600 font-medium';
        } else {
            differenceElement.textContent = '(same duration)';
            differenceElement.className = 'text-xs ml-2 text-gray-600 font-medium';
        }
        
        // Update summary
        updateRenewalSummary(currentDuration, requestedDuration, durationDifference);
    }

    function updateRenewalSummary(currentDays, requestedDays, difference) {
        const summary = document.getElementById('renewalSummary');
        const currentMonths = (currentDays / 30).toFixed(1);
        const requestedMonths = (requestedDays / 30).toFixed(1);
        
        document.getElementById('summaryCurrentDuration').textContent = `${currentDays} days (${currentMonths} months)`;
        document.getElementById('summaryRequestedDuration').textContent = `${requestedDays} days (${requestedMonths} months)`;
        
        if (difference > 0) {
            document.getElementById('summaryExtension').textContent = `+${difference} days extension`;
            document.getElementById('summaryExtension').className = 'font-medium text-green-600';
        } else if (difference < 0) {
            document.getElementById('summaryExtension').textContent = `${difference} days reduction`;
            document.getElementById('summaryExtension').className = 'font-medium text-red-600';
        } else {
            document.getElementById('summaryExtension').textContent = 'No change in duration';
            document.getElementById('summaryExtension').className = 'font-medium text-gray-600';
        }
        
        summary.classList.remove('hidden');
    }

    function setRenewalPeriod(days) {
        const requestedFrom = document.getElementById('requestedValidFrom');
        const requestedTo = document.getElementById('requestedValidTo');
        
        if (!requestedFrom.value) {
            alert('Please set the "New Valid From" date first.');
            return;
        }
        
        const fromDate = new Date(requestedFrom.value);
        const toDate = new Date(fromDate);
        toDate.setDate(fromDate.getDate() + days);
        
        requestedTo.value = formatDate(toDate);
        calculateDurations();
    }

    function updateReasonCharCount() {
        const reasonTextarea = document.getElementById('renewalReason');
        const charCount = document.getElementById('reasonCharCount');
        const submitBtn = document.getElementById('submitRenewalBtn');
        
        const count = reasonTextarea.value.length;
        charCount.textContent = `${count} characters`;
        
        // Update character count color
        if (count < 10) {
            charCount.className = 'text-xs text-red-500 font-medium';
            submitBtn.disabled = true;
        } else if (count < 20) {
            charCount.className = 'text-xs text-yellow-500 font-medium';
            submitBtn.disabled = false;
        } else {
            charCount.className = 'text-xs text-green-500 font-medium';
            submitBtn.disabled = false;
        }
    }

    // User Modal Functions
    function openUserModal() {
        document.getElementById('userModal').classList.remove('hidden');
        document.getElementById('userModal').classList.add('flex');
    }

    function closeUserModal() {
        document.getElementById('userModal').classList.remove('flex');
        document.getElementById('userModal').classList.add('hidden');
    }

    // Approval Modal Functions
    function openApprovalModal(leaveId, status) {
        console.log("Opening approval modal for leave ID:", leaveId, "Status:", status);
        
        document.getElementById('leaveId').value = leaveId;
        document.getElementById('leaveStatus').value = status;
        
        const title = status === 'approved' ? 'Approve Leave Application' : 'Reject Leave Application';
        const buttonColor = status === 'approved' ? 'green' : 'red';
        
        document.getElementById('approvalTitle').textContent = title;
        document.getElementById('approvalSubmitBtn').className = `flex-1 bg-${buttonColor}-600 text-white px-4 py-2 rounded-lg hover:bg-${buttonColor}-700 transition duration-300 font-medium`;
        document.getElementById('approvalSubmitBtn').innerHTML = status === 'approved' 
            ? '<i class="fas fa-check mr-2"></i>Approve Application' 
            : '<i class="fas fa-times mr-2"></i>Reject Application';
        
        // Clear previous notes
        document.querySelector('textarea[name="manager_notes"]').value = '';
        
        document.getElementById('approvalModal').classList.remove('hidden');
        document.getElementById('approvalModal').classList.add('flex');
    }

    function closeApprovalModal() {
        document.getElementById('approvalModal').classList.remove('flex');
        document.getElementById('approvalModal').classList.add('hidden');
    }

    // Change Dayoff Approval Modal Functions
    function openChangeDayoffApprovalModal(applicationId, status) {
        console.log("Opening change dayoff approval modal for application ID:", applicationId, "Status:", status);
        
        document.getElementById('changeDayoffId').value = applicationId;
        document.getElementById('changeDayoffStatus').value = status;
        
        const title = status === 'approved' ? 'Approve Change of Dayoff' : 'Reject Change of Dayoff';
        const buttonColor = status === 'approved' ? 'green' : 'red';
        const buttonIcon = status === 'approved' ? 'check' : 'times';
        const buttonText = status === 'approved' ? 'Approve Request' : 'Reject Request';
        
        document.getElementById('changeDayoffApprovalTitle').textContent = title;
        document.getElementById('changeDayoffSubmitBtn').className = `flex-1 bg-${buttonColor}-600 text-white px-4 py-2 rounded-lg hover:bg-${buttonColor}-700 transition duration-300 font-medium`;
        document.getElementById('changeDayoffSubmitBtn').innerHTML = `<i class="fas fa-${buttonIcon} mr-2"></i>${buttonText}`;
        
        // Clear previous notes
        document.querySelector('#changeDayoffApprovalForm textarea[name="manager_notes"]').value = '';
        
        document.getElementById('changeDayoffApprovalModal').classList.remove('hidden');
        document.getElementById('changeDayoffApprovalModal').classList.add('flex');
    }

    function closeChangeDayoffApprovalModal() {
        document.getElementById('changeDayoffApprovalModal').classList.remove('flex');
        document.getElementById('changeDayoffApprovalModal').classList.add('hidden');
    }

    // Late Letter Approval Modal Functions
    function openLateLetterApprovalModal(applicationId, status) {
        console.log("Opening late letter approval modal for application ID:", applicationId, "Status:", status);
        
        document.getElementById('lateLetterId').value = applicationId;
        document.getElementById('lateLetterStatus').value = status;
        
        const title = status === 'approved' ? 'Approve Late Letter' : 'Reject Late Letter';
        const buttonColor = status === 'approved' ? 'green' : 'red';
        const buttonIcon = status === 'approved' ? 'check' : 'times';
        const buttonText = status === 'approved' ? 'Approve Letter' : 'Reject Letter';
        
        document.getElementById('lateLetterApprovalTitle').textContent = title;
        document.getElementById('lateLetterSubmitBtn').className = `flex-1 bg-${buttonColor}-600 text-white px-4 py-2 rounded-lg hover:bg-${buttonColor}-700 transition duration-300 font-medium`;
        document.getElementById('lateLetterSubmitBtn').innerHTML = `<i class="fas fa-${buttonIcon} mr-2"></i>${buttonText}`;
        
        document.querySelector('#lateLetterApprovalForm textarea[name="manager_notes"]').value = '';
        
        document.getElementById('lateLetterApprovalModal').classList.remove('hidden');
        document.getElementById('lateLetterApprovalModal').classList.add('flex');
    }

    function closeLateLetterApprovalModal() {
        document.getElementById('lateLetterApprovalModal').classList.remove('flex');
        document.getElementById('lateLetterApprovalModal').classList.add('hidden');
    }

    // ID Renewal Approval Modal Functions
    function openIdRenewalApprovalModal(applicationId, status) {
        console.log("Opening ID renewal approval modal for application ID:", applicationId, "Status:", status);
        
        document.getElementById('idRenewalId').value = applicationId;
        document.getElementById('idRenewalStatus').value = status;
        
        const title = status === 'approved' ? 'Approve ID Renewal' : 'Reject ID Renewal';
        const buttonColor = status === 'approved' ? 'green' : 'red';
        const buttonIcon = status === 'approved' ? 'check' : 'times';
        const buttonText = status === 'approved' ? 'Approve Renewal' : 'Reject Renewal';
        
        document.getElementById('idRenewalApprovalTitle').textContent = title;
        document.getElementById('idRenewalSubmitBtn').className = `flex-1 bg-${buttonColor}-600 text-white px-4 py-2 rounded-lg hover:bg-${buttonColor}-700 transition duration-300 font-medium`;
        document.getElementById('idRenewalSubmitBtn').innerHTML = `<i class="fas fa-${buttonIcon} mr-2"></i>${buttonText}`;
        
        // Clear previous notes
        document.querySelector('#idRenewalApprovalForm textarea[name="manager_notes"]').value = '';
        
        document.getElementById('idRenewalApprovalModal').classList.remove('hidden');
        document.getElementById('idRenewalApprovalModal').classList.add('flex');
    }

    function closeIdRenewalApprovalModal() {
        document.getElementById('idRenewalApprovalModal').classList.remove('flex');
        document.getElementById('idRenewalApprovalModal').classList.add('hidden');
    }

    function viewIdRenewalDetails(applicationId) {
        alert(`Viewing details for ID renewal application #${applicationId}. Detailed view coming soon!`);
    }

    // Employee Details Modal Functions
    function viewEmployeeDetails(userId) {
        document.getElementById('employeeDetails').innerHTML = `
            <div class="flex justify-center items-center py-8">
                <i class="fas fa-spinner fa-spin text-blue-600 text-2xl"></i>
                <span class="ml-2 text-gray-600">Loading employee details...</span>
            </div>
        `;
        
        document.getElementById('employeeModal').classList.remove('hidden');
        document.getElementById('employeeModal').classList.add('flex');
        
        fetch('get_employee_details.php?id=' + userId)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(data => {
                document.getElementById('employeeDetails').innerHTML = data;
            })
            .catch(error => {
                console.error('Error loading employee details:', error);
                document.getElementById('employeeDetails').innerHTML = `
                    <div class="text-center py-8 text-red-500">
                        <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                        <p>Failed to load employee details.</p>
                        <p class="text-sm text-gray-500 mt-2">Please try again later.</p>
                    </div>
                `;
            });
    }

    function closeEmployeeModal() {
        document.getElementById('employeeModal').classList.remove('flex');
        document.getElementById('employeeModal').classList.add('hidden');
    }

    // Print functions for different application types
    function printLeaveForm(leaveId) {
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Loading...';
        button.disabled = true;
        
        const printWindow = window.open(`preview_leave.php?id=${leaveId}`, '_blank', 'width=800,height=1000');
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        }, 2000);
    }

    function printStoreLetter(leaveId) {
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Loading...';
        button.disabled = true;
        
        const letterWindow = window.open(`print_store_letter.php?id=${leaveId}`, '_blank', 'width=900,height=1000');
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        }, 2000);
    }

    function printChangeDayoff(applicationId) {
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Loading...';
        button.disabled = true;
        
        const printWindow = window.open(`print_change_dayoff.php?id=${applicationId}`, '_blank', 'width=800,height=1000');
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        }, 2000);
    }

    function printLateLetter(applicationId) {
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Loading...';
        button.disabled = true;
        
        const printWindow = window.open(`print_late_letter.php?id=${applicationId}`, '_blank', 'width=800,height=1000');
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        }, 2000);
    }

    function printIdRenewal(applicationId) {
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Loading...';
        button.disabled = true;
        
        const printWindow = window.open(`print_id_renewal.php?id=${applicationId}`, '_blank', 'width=800,height=1000');
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        }, 2000);
    }

    // Edit and Cancel functions (placeholder implementations)
    function editLeave(leaveId) {
        console.log('Edit leave application:', leaveId);
        alert('Edit functionality coming soon!');
    }

    function cancelLeave(leaveId) {
        if (confirm('Are you sure you want to cancel this leave application?')) {
            fetch('cancel_leave.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `leave_id=${leaveId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Leave application cancelled successfully!');
                    location.reload();
                } else {
                    alert('Error cancelling leave: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error cancelling leave. Please try again.');
            });
        }
    }

    function editChangeDayoff(applicationId) {
        console.log('Edit change dayoff application:', applicationId);
        alert('Edit functionality coming soon!');
    }

    function cancelChangeDayoff(applicationId) {
        if (confirm('Are you sure you want to cancel this change of dayoff request?')) {
            fetch('cancel_change_dayoff.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `application_id=${applicationId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Change of dayoff request cancelled successfully!');
                    location.reload();
                } else {
                    alert('Error cancelling request: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error cancelling request. Please try again.');
            });
        }
    }

    function editLateLetter(applicationId) {
        console.log('Edit late letter application:', applicationId);
        alert('Edit functionality coming soon!');
    }

    function cancelLateLetter(applicationId) {
        if (confirm('Are you sure you want to cancel this late letter request?')) {
            fetch('cancel_late_letter.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `application_id=${applicationId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Late letter request cancelled successfully!');
                    location.reload();
                } else {
                    alert('Error cancelling request: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error cancelling request. Please try again.');
            });
        }
    }

    function editIdRenewal(applicationId) {
        console.log('Edit ID renewal application:', applicationId);
        alert('Edit functionality coming soon!');
    }

    function cancelIdRenewal(applicationId) {
        if (confirm('Are you sure you want to cancel this ID renewal request?')) {
            fetch('cancel_id_renewal.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `application_id=${applicationId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('ID renewal request cancelled successfully!');
                    location.reload();
                } else {
                    alert('Error cancelling request: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error cancelling request. Please try again.');
            });
        }
    }

    // Form validation and calculations
    function calculateLeaveDays() {
        const startDate = document.querySelector('input[name="start_date"]');
        const endDate = document.querySelector('input[name="end_date"]');
        const dayOffCount = document.getElementById('dayOffCount');
        
        if (startDate && endDate && startDate.value && endDate.value) {
            const start = new Date(startDate.value);
            const end = new Date(endDate.value);
            
            // Validate date range
            if (start > end) {
                alert('End date cannot be before start date.');
                endDate.value = '';
                dayOffCount.value = '';
                return;
            }
            
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            dayOffCount.value = diffDays;
        }
    }

    function validateChangeDayoffForm() {
        const currentDayoff = document.querySelector('select[name="current_dayoff"]');
        const requestedDayoff = document.querySelector('select[name="requested_dayoff"]');
        const effectiveDate = document.querySelector('input[name="effective_date"]');
        
        if (currentDayoff.value === requestedDayoff.value) {
            alert('Requested dayoff must be different from current dayoff.');
            return false;
        }
        
        const effective = new Date(effectiveDate.value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (effective <= today) {
            alert('Effective date must be at least 1 day from today.');
            return false;
        }
        
        return true;
    }

    function validateLateLetterForm() {
        const lateDate = document.querySelector('input[name="late_date"]');
        const late = new Date(lateDate.value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (late > today) {
            alert('Late date cannot be in the future.');
            return false;
        }
        
        // Check if date is within last 7 days
        const sevenDaysAgo = new Date();
        sevenDaysAgo.setDate(today.getDate() - 7);
        
        if (late < sevenDaysAgo) {
            alert('Late date cannot be more than 7 days in the past.');
            return false;
        }
        
        return true;
    }

    // Initialize all event listeners when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Dashboard initialized');
        
        // Leave form date calculations
        const startDate = document.querySelector('input[name="start_date"]');
        const endDate = document.querySelector('input[name="end_date"]');
        if (startDate && endDate) {
            startDate.addEventListener('change', calculateLeaveDays);
            endDate.addEventListener('change', calculateLeaveDays);
        }
        
        // ID Renewal form functionality
        const dateInputs = [
            'currentValidFrom', 'currentValidTo', 
            'requestedValidFrom', 'requestedValidTo'
        ];
        
        dateInputs.forEach(inputId => {
            const input = document.getElementById(inputId);
            if (input) {
                input.addEventListener('change', calculateDurations);
            }
        });
        
        const reasonTextarea = document.getElementById('renewalReason');
        if (reasonTextarea) {
            reasonTextarea.addEventListener('input', updateReasonCharCount);
        }
        
        // Form validation for all forms
        const forms = {
            'leaveForm': null,
            'changeDayoffForm': validateChangeDayoffForm,
            'lateLetterForm': validateLateLetterForm,
            'idRenewalForm': function() {
                const currentFrom = new Date(document.getElementById('currentValidFrom').value);
                const currentTo = new Date(document.getElementById('currentValidTo').value);
                const requestedFrom = new Date(document.getElementById('requestedValidFrom').value);
                const requestedTo = new Date(document.getElementById('requestedValidTo').value);
                const reason = document.getElementById('renewalReason').value;
                
                if (currentFrom >= currentTo) {
                    alert('Current "Valid From" date must be before "Valid To" date.');
                    return false;
                }
                
                if (requestedFrom >= requestedTo) {
                    alert('Requested "Valid From" date must be before "Valid To" date.');
                    return false;
                }
                
                if (reason.trim().length < 10) {
                    alert('Please provide a more detailed reason (at least 10 characters).');
                    return false;
                }
                
                return true;
            }
        };
        
        Object.keys(forms).forEach(formId => {
            const form = document.getElementById(formId);
            if (form) {
                form.addEventListener('submit', function(e) {
                    // Basic required field validation
                    const requiredFields = form.querySelectorAll('[required]');
                    let isValid = true;
                    
                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            isValid = false;
                            field.classList.add('border-red-500');
                        } else {
                            field.classList.remove('border-red-500');
                        }
                    });
                    
                    if (!isValid) {
                        e.preventDefault();
                        alert('Please fill in all required fields.');
                        return;
                    }
                    
                    // Custom form validation
                    const customValidator = forms[formId];
                    if (customValidator && !customValidator()) {
                        e.preventDefault();
                        return;
                    }
                    
                    // Show loading state for submit buttons
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        const originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Submitting...';
                        submitBtn.disabled = true;
                        
                        // Re-enable button after 5 seconds in case of error
                        setTimeout(() => {
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                        }, 5000);
                    }
                });
            }
        });
        
        // Auto-close success/error messages after 5 seconds
        setTimeout(() => {
            const messages = document.querySelectorAll('.bg-green-100, .bg-red-100');
            messages.forEach(message => {
                message.style.transition = 'opacity 0.5s ease';
                message.style.opacity = '0';
                setTimeout(() => message.remove(), 500);
            });
        }, 5000);
        
        // Add hover effects to table rows
        const tableRows = document.querySelectorAll('tbody tr');
        tableRows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1)';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+P or Cmd+P for print (when focused on print buttons)
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                const printButtons = document.querySelectorAll('button[onclick*="print"]');
                if (printButtons.length > 0) {
                    printButtons[0].click();
                }
            }
            
            // Escape to close modals
            if (e.key === 'Escape') {
                closeLeaveModal();
                closeChangeDayoffModal();
                closeLateLetterModal();
                closeIdRenewalModal();
                closeApprovalModal();
                closeEmployeeModal();
                closeUserModal();
                closeChangeDayoffApprovalModal();
                closeLateLetterApprovalModal();
                closeIdRenewalApprovalModal();
            }
            
            // Number shortcuts for quick actions (1-4 for application types)
            if (e.altKey && e.key >= '1' && e.key <= '4') {
                e.preventDefault();
                const actions = [
                    openLeaveModal,
                    openChangeDayoffModal,
                    openLateLetterModal,
                    openIdRenewalModal
                ];
                const index = parseInt(e.key) - 1;
                if (actions[index]) {
                    actions[index]();
                }
            }
        });
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Initialize tooltips for action buttons
        const initTooltips = () => {
            const buttons = document.querySelectorAll('button');
            buttons.forEach(button => {
                const originalTitle = button.getAttribute('title');
                if (originalTitle && !button.hasAttribute('data-tooltip-initialized')) {
                    button.setAttribute('data-tooltip-initialized', 'true');
                    button.addEventListener('mouseenter', function(e) {
                        const tooltip = document.createElement('div');
                        tooltip.className = 'fixed z-50 px-2 py-1 text-xs text-white bg-gray-900 rounded shadow-lg';
                        tooltip.textContent = originalTitle;
                        document.body.appendChild(tooltip);
                        
                        const rect = button.getBoundingClientRect();
                        tooltip.style.left = rect.left + 'px';
                        tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
                        
                        button.setAttribute('data-current-tooltip', tooltip);
                    });
                    
                    button.addEventListener('mouseleave', function() {
                        const tooltip = button.getAttribute('data-current-tooltip');
                        if (tooltip) {
                            document.body.removeChild(tooltip);
                            button.removeAttribute('data-current-tooltip');
                        }
                    });
                }
            });
        };
        
        // Initialize tooltips after a short delay
        setTimeout(initTooltips, 1000);
    });

    // Close modals when clicking outside
    document.addEventListener('click', function(e) {
        const modals = [
            'leaveModal', 'changeDayoffModal', 'lateLetterModal', 'idRenewalModal', 
            'approvalModal', 'employeeModal', 'userModal', 
            'changeDayoffApprovalModal', 'lateLetterApprovalModal', 'idRenewalApprovalModal'
        ];
        
        modals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (modal && e.target === modal) {
                const closeFunction = 'close' + modalId.charAt(0).toUpperCase() + modalId.slice(1);
                if (typeof window[closeFunction] === 'function') {
                    window[closeFunction]();
                }
            }
        });
    });

    // Performance optimization: Debounce resize events
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            // Recalculate any layout-dependent elements here
            console.log('Window resized - recalculating layouts');
        }, 250);
    });

    // Export functions for global access (if needed)
    window.toggleTable = toggleTable;
    window.openLeaveModal = openLeaveModal;
    window.closeLeaveModal = closeLeaveModal;
    window.openChangeDayoffModal = openChangeDayoffModal;
    window.closeChangeDayoffModal = closeChangeDayoffModal;
    window.openLateLetterModal = openLateLetterModal;
    window.closeLateLetterModal = closeLateLetterModal;
    window.openIdRenewalModal = openIdRenewalModal;
    window.closeIdRenewalModal = closeIdRenewalModal;
    window.printLeaveForm = printLeaveForm;
    window.printStoreLetter = printStoreLetter;
    window.printChangeDayoff = printChangeDayoff;
    window.printLateLetter = printLateLetter;
    window.printIdRenewal = printIdRenewal;
</script>
</body>
</html>