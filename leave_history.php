<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role_name'] == 'Employee')) {
    header("Location: index.php");
    exit();
}

include_once 'config/Database.php';
include_once 'models/LeaveHistory.php';
include_once 'models/User.php';

$database = new Database();
$db = $database->getConnection();

$leaveHistory = new LeaveHistory($db);
$user = new User($db);

// Get filter parameters
$filters = [];
if (isset($_GET['employee_name'])) {
    $filters['employee_name'] = $_GET['employee_name'];
}
if (isset($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}
if (isset($_GET['start_date'])) {
    $filters['start_date'] = $_GET['start_date'];
}
if (isset($_GET['end_date'])) {
    $filters['end_date'] = $_GET['end_date'];
}

// Get leave history with filters
$history = $leaveHistory->getAll($filters);
$stats = $leaveHistory->getStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave History - Leave Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Leave History</h1>
                    <p class="text-gray-600">View all processed leave applications</p>
                </div>
                <a href="dashboard.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-300 text-sm font-medium">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
            </div>

            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-blue-100 rounded-lg">
                            <i class="fas fa-history text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">Total Processed</h3>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $stats['total']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-100 rounded-lg">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">Approved</h3>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $stats['approved']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-red-100 rounded-lg">
                            <i class="fas fa-times-circle text-red-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">Rejected</h3>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $stats['rejected']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Filters</h3>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Employee Name</label>
                    <input type="text" name="employee_name" value="<?php echo $_GET['employee_name'] ?? ''; ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Search by name">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">All Status</option>
                        <option value="approved" <?php echo (isset($_GET['status']) && $_GET['status'] == 'approved') ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo (isset($_GET['status']) && $_GET['status'] == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Date From</label>
                    <input type="date" name="start_date" value="<?php echo $_GET['start_date'] ?? ''; ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Date To</label>
                    <input type="date" name="end_date" value="<?php echo $_GET['end_date'] ?? ''; ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div class="md:col-span-4 flex space-x-3">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition duration-300 font-medium">
                        <i class="fas fa-filter mr-2"></i>Apply Filters
                    </button>
                    <a href="leave_history.php" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition duration-300 font-medium">
                        <i class="fas fa-redo mr-2"></i>Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Leave History Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-lg font-semibold text-gray-800">Processed Leave Applications</h2>
                <span class="text-sm text-gray-500"><?php echo $history->rowCount(); ?> records found</span>
            </div>
            
            <?php if ($history->rowCount() > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approved By</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Processed</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php while ($row = $history->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr class="hover:bg-gray-50 transition duration-150">
                            <td class="px-4 py-3">
                                <button onclick="viewEmployeeDetails(<?php echo $row['user_id']; ?>)" 
                                        class="flex items-center space-x-3 text-left hover:text-blue-600 transition duration-150">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center overflow-hidden">
                                        <?php if ($row['profile_image'] && file_exists('uploads/profiles/' . $row['profile_image'])): ?>
                                            <img src="uploads/profiles/<?php echo $row['profile_image']; ?>" alt="Profile" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <i class="fas fa-user text-blue-600 text-sm"></i>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-sm font-medium text-gray-900"><?php echo $row['full_name']; ?></span>
                                </button>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                <?php echo date('M j, Y', strtotime($row['start_date'])); ?> - 
                                <?php echo date('M j, Y', strtotime($row['end_date'])); ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                <div>
                                    <p class="font-medium"><?php echo $row['reason']; ?></p>
                                    <?php if (!empty($row['manager_notes'])): ?>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <strong>Manager's Note:</strong> <?php echo $row['manager_notes']; ?>
                                    </p>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900"><?php echo $row['day_off_count']; ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium 
                                    <?php echo $row['status'] == 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900"><?php echo $row['approved_by_name']; ?></td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                <?php echo date('M j, Y g:i A', strtotime($row['created_at'])); ?>
                            </td>
                           <!-- In the actions column of leave_history.php -->
<td class="px-4 py-3">
    <div class="flex space-x-2">
        <!-- Print Button -->
        <button onclick="printLeaveForm(<?php echo $row['id']; ?>)" 
                class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 transition duration-300 flex items-center">
            <i class="fas fa-print mr-1"></i></button>

         <!-- Letter Button -->
         <?php if ($row['status'] == 'approved'): ?>
                <button onclick="printStoreLetter(<?php echo $row['id']; ?>)" 
                        class="bg-purple-600 text-white px-3 py-1 rounded text-xs hover:bg-purple-700 transition duration-300 flex items-center">
                    <i class="fas fa-envelope mr-1"></i>
                </button>
            <?php endif; ?>
       
        
        <!-- Edit Button - Only for managers -->
        <?php if (in_array($_SESSION['role_name'], ['HR Manager', 'Operations Manager'])): ?>
        <a href="edit_leave.php?id=<?php echo $row['id']; ?>" 
           class="bg-yellow-600 text-white px-3 py-1 rounded text-xs hover:bg-yellow-700 transition duration-300 flex items-center">
            <i class="fas fa-edit mr-1"></i>
        </a>
        <?php endif; ?>
        
        <!-- Cancel Button - Only for HR Manager -->
        <?php if ($_SESSION['role_name'] == 'HR Manager'): ?>
        <a href="cancel_leave.php?id=<?php echo $row['id']; ?>" 
           class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700 transition duration-300 flex items-center">
            <i class="fas fa-times mr-1"></i>
        </a>
        <?php endif; ?>
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
                <p class="text-gray-500">No leave history found.</p>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Employee Details Modal -->
    <div id="employeeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
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

    <script>
        // Print Leave Form Function
        function printLeaveForm(leaveId) {
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Loading...';
            button.disabled = true;
            
            const previewWindow = window.open(`preview_leave.php?id=${leaveId}`, '_blank', 'width=600,height=800');
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            }, 2000);
            
            if (previewWindow) {
                previewWindow.focus();
            } else {
                alert('Please allow pop-ups to view the print preview.');
                button.innerHTML = originalText;
                button.disabled = false;
            }
        }

        // Edit Leave Form Function
        function editLeaveForm(leaveId) {
            if (confirm('Are you sure you want to edit this leave application?')) {
                // Redirect to edit page or open edit modal
                window.location.href = `edit_leave.php?id=${leaveId}`;
            }
        }

        // Cancel Leave Form Function
        function cancelLeaveForm(leaveId) {
            if (confirm('Are you sure you want to cancel this leave application? This action cannot be undone.')) {
                // Perform cancel action via AJAX or redirect
                window.location.href = `cancel_leave.php?id=${leaveId}`;
            }
        }

        
        // Print Store Letter Function
function printStoreLetter(leaveId) {
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Loading...';
    button.disabled = true;
    
    const letterWindow = window.open(`print_store_letter.php?id=${leaveId}`, '_blank', 'width=900,height=1000');
    
    setTimeout(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    }, 2000);
    
    if (letterWindow) {
        letterWindow.focus();
    } else {
        alert('Please allow pop-ups for this site to view the store letter.');
        button.innerHTML = originalText;
        button.disabled = false;
    }
}
        // Employee Details Modal Functions
        function viewEmployeeDetails(userId) {
            fetch('get_employee_details.php?id=' + userId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('employeeDetails').innerHTML = data;
                    document.getElementById('employeeModal').classList.remove('hidden');
                    document.getElementById('employeeModal').classList.add('flex');
                });
        }

        function closeEmployeeModal() {
            document.getElementById('employeeModal').classList.remove('flex');
            document.getElementById('employeeModal').classList.add('hidden');
        }
    </script>
</body>
</html>