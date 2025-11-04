<?php
// manage_fields.php
require_once 'includes/session_check.php';
checkAdminAccess(); // Only admin can access this

include_once 'config/Database.php';
include_once 'models/Reference.php';

$database = new Database();
$db = $database->getConnection();
$reference = new Reference($db);

// Get all data for management
$companies = $reference->getCompanies();
$brands = $reference->getBrands();
$departments = $reference->getDepartments();
$stores = $reference->getStores();
$roles = $reference->getRoles();

// Get recipients with store names
$recipients_query = "SELECT r.*, s.store_name 
                     FROM recipients r 
                     LEFT JOIN stores s ON r.store_id = s.id 
                     ORDER BY s.store_name";
$recipients_stmt = $db->prepare($recipients_query);
$recipients_stmt->execute();
$recipients = $recipients_stmt->fetchAll(PDO::FETCH_ASSOC);

// Process form submissions
if ($_POST) {
    $success = false;
    
    try {
        // Companies
        if (isset($_POST['add_company'])) {
            $company_name = trim($_POST['company_name']);
            $insert_query = "INSERT INTO companies (company_name) VALUES (:name)";
            $stmt = $db->prepare($insert_query);
            $stmt->bindParam(":name", $company_name);
            $success = $stmt->execute();
            $_SESSION['success'] = $success ? "Company added successfully!" : "Failed to add company.";
        }
        
        if (isset($_POST['update_company'])) {
            $company_id = $_POST['company_id'];
            $company_name = trim($_POST['company_name']);
            $update_query = "UPDATE companies SET company_name = :name WHERE id = :id";
            $stmt = $db->prepare($update_query);
            $stmt->bindParam(":name", $company_name);
            $stmt->bindParam(":id", $company_id);
            $success = $stmt->execute();
            $_SESSION['success'] = $success ? "Company updated successfully!" : "Failed to update company.";
        }
        
        if (isset($_POST['delete_company'])) {
            $company_id = $_POST['company_id'];
            $delete_query = "DELETE FROM companies WHERE id = :id";
            $stmt = $db->prepare($delete_query);
            $stmt->bindParam(":id", $company_id);
            $success = $stmt->execute();
            $_SESSION['success'] = $success ? "Company deleted successfully!" : "Failed to delete company.";
        }

        // Brands
        if (isset($_POST['add_brand'])) {
            $brand_name = trim($_POST['brand_name']);
            $company_id = $_POST['company_id'];
            $insert_query = "INSERT INTO brands (brand_name, company_id) VALUES (:name, :company_id)";
            $stmt = $db->prepare($insert_query);
            $stmt->bindParam(":name", $brand_name);
            $stmt->bindParam(":company_id", $company_id);
            $success = $stmt->execute();
            $_SESSION['success'] = $success ? "Brand added successfully!" : "Failed to add brand.";
        }
        
        if (isset($_POST['update_brand'])) {
            $brand_id = $_POST['brand_id'];
            $brand_name = trim($_POST['brand_name']);
            $company_id = $_POST['company_id'];
            $update_query = "UPDATE brands SET brand_name = :name, company_id = :company_id WHERE id = :id";
            $stmt = $db->prepare($update_query);
            $stmt->bindParam(":name", $brand_name);
            $stmt->bindParam(":company_id", $company_id);
            $stmt->bindParam(":id", $brand_id);
            $success = $stmt->execute();
            $_SESSION['success'] = $success ? "Brand updated successfully!" : "Failed to update brand.";
        }
        
        if (isset($_POST['delete_brand'])) {
            $brand_id = $_POST['brand_id'];
            $delete_query = "DELETE FROM brands WHERE id = :id";
            $stmt = $db->prepare($delete_query);
            $stmt->bindParam(":id", $brand_id);
            $success = $stmt->execute();
            $_SESSION['success'] = $success ? "Brand deleted successfully!" : "Failed to delete brand.";
        }

        // Departments
        if (isset($_POST['add_department'])) {
            $department_name = trim($_POST['department_name']);
            $insert_query = "INSERT INTO departments (department_name) VALUES (:name)";
            $stmt = $db->prepare($insert_query);
            $stmt->bindParam(":name", $department_name);
            $success = $stmt->execute();
            $_SESSION['success'] = $success ? "Department added successfully!" : "Failed to add department.";
        }
        
        if (isset($_POST['update_department'])) {
            $department_id = $_POST['department_id'];
            $department_name = trim($_POST['department_name']);
            $update_query = "UPDATE departments SET department_name = :name WHERE id = :id";
            $stmt = $db->prepare($update_query);
            $stmt->bindParam(":name", $department_name);
            $stmt->bindParam(":id", $department_id);
            $success = $stmt->execute();
            $_SESSION['success'] = $success ? "Department updated successfully!" : "Failed to update department.";
        }
        
        if (isset($_POST['delete_department'])) {
            $department_id = $_POST['department_id'];
            $delete_query = "DELETE FROM departments WHERE id = :id";
            $stmt = $db->prepare($delete_query);
            $stmt->bindParam(":id", $department_id);
            $success = $stmt->execute();
            $_SESSION['success'] = $success ? "Department deleted successfully!" : "Failed to delete department.";
        }

        // Stores
        if (isset($_POST['add_store'])) {
            $store_name = trim($_POST['store_name']);
            $brand_id = $_POST['brand_id'];
            $insert_query = "INSERT INTO stores (store_name, brand_id) VALUES (:name, :brand_id)";
            $stmt = $db->prepare($insert_query);
            $stmt->bindParam(":name", $store_name);
            $stmt->bindParam(":brand_id", $brand_id);
            $success = $stmt->execute();
            $_SESSION['success'] = $success ? "Store added successfully!" : "Failed to add store.";
        }
        
        if (isset($_POST['update_store'])) {
            $store_id = $_POST['store_id'];
            $store_name = trim($_POST['store_name']);
            $brand_id = $_POST['brand_id'];
            $update_query = "UPDATE stores SET store_name = :name, brand_id = :brand_id WHERE id = :id";
            $stmt = $db->prepare($update_query);
            $stmt->bindParam(":name", $store_name);
            $stmt->bindParam(":brand_id", $brand_id);
            $stmt->bindParam(":id", $store_id);
            $success = $stmt->execute();
            $_SESSION['success'] = $success ? "Store updated successfully!" : "Failed to update store.";
        }
        
        if (isset($_POST['delete_store'])) {
            $store_id = $_POST['store_id'];
            $delete_query = "DELETE FROM stores WHERE id = :id";
            $stmt = $db->prepare($delete_query);
            $stmt->bindParam(":id", $store_id);
            $success = $stmt->execute();
            $_SESSION['success'] = $success ? "Store deleted successfully!" : "Failed to delete store.";
        }

        // Roles
        if (isset($_POST['add_role'])) {
            $role_name = trim($_POST['role_name']);
            $insert_query = "INSERT INTO roles (role_name) VALUES (:name)";
            $stmt = $db->prepare($insert_query);
            $stmt->bindParam(":name", $role_name);
            $success = $stmt->execute();
            $_SESSION['success'] = $success ? "Role added successfully!" : "Failed to add role.";
        }
        
        if (isset($_POST['update_role'])) {
            $role_id = $_POST['role_id'];
            $role_name = trim($_POST['role_name']);
            $update_query = "UPDATE roles SET role_name = :name WHERE id = :id";
            $stmt = $db->prepare($update_query);
            $stmt->bindParam(":name", $role_name);
            $stmt->bindParam(":id", $role_id);
            $success = $stmt->execute();
            $_SESSION['success'] = $success ? "Role updated successfully!" : "Failed to update role.";
        }
        
        if (isset($_POST['delete_role'])) {
            $role_id = $_POST['role_id'];
            $delete_query = "DELETE FROM roles WHERE id = :id";
            $stmt = $db->prepare($delete_query);
            $stmt->bindParam(":id", $role_id);
            $success = $stmt->execute();
            $_SESSION['success'] = $success ? "Role deleted successfully!" : "Failed to delete role.";
        }

        // Recipients
        if (isset($_POST['add_recipient'])) {
            $store_id = $_POST['store_id'];
            $name = trim($_POST['name']);
            $position = trim($_POST['position']);
            $insert_query = "INSERT INTO recipients (store_id, name, position) VALUES (:store_id, :name, :position)";
            $stmt = $db->prepare($insert_query);
            $stmt->bindParam(":store_id", $store_id);
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":position", $position);
            $success = $stmt->execute();
            $_SESSION['success'] = $success ? "Recipient added successfully!" : "Failed to add recipient.";
        }
        
        if (isset($_POST['update_recipient'])) {
            $recipient_id = $_POST['recipient_id'];
            $name = trim($_POST['name']);
            $position = trim($_POST['position']);
            $update_query = "UPDATE recipients SET name = :name, position = :position WHERE id = :id";
            $stmt = $db->prepare($update_query);
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":position", $position);
            $stmt->bindParam(":id", $recipient_id);
            $success = $stmt->execute();
            $_SESSION['success'] = $success ? "Recipient updated successfully!" : "Failed to update recipient.";
        }
        
        if (isset($_POST['delete_recipient'])) {
            $recipient_id = $_POST['recipient_id'];
            $delete_query = "DELETE FROM recipients WHERE id = :id";
            $stmt = $db->prepare($delete_query);
            $stmt->bindParam(":id", $recipient_id);
            $success = $stmt->execute();
            $_SESSION['success'] = $success ? "Recipient deleted successfully!" : "Failed to delete recipient.";
        }

        if ($success) {
            header("Location: manage_fields.php");
            exit();
        }

    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
}

// Refresh data after operations
$companies = $reference->getCompanies();
$brands = $reference->getBrands();
$departments = $reference->getDepartments();
$stores = $reference->getStores();
$roles = $reference->getRoles();
$recipients_stmt->execute();
$recipients = $recipients_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Fields - Leave Management System</title>
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
                    <h1 class="text-2xl font-bold text-gray-800">System Fields Management</h1>
                    <p class="text-gray-600">Manage all system reference data and fields</p>
                </div>
                <a href="dashboard.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-300 text-sm font-medium">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tabs Navigation -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
            <div class="border-b border-gray-200">
                <nav class="flex -mb-px">
                    <button onclick="showTab('companies')" id="companies-tab" class="tab-button active py-4 px-6 text-sm font-medium text-center border-b-2 border-transparent hover:text-blue-600 hover:border-blue-300 transition duration-300">
                        <i class="fas fa-building mr-2"></i>Companies
                    </button>
                    <button onclick="showTab('brands')" id="brands-tab" class="tab-button py-4 px-6 text-sm font-medium text-center border-b-2 border-transparent hover:text-blue-600 hover:border-blue-300 transition duration-300">
                        <i class="fas fa-tag mr-2"></i>Brands
                    </button>
                    <button onclick="showTab('departments')" id="departments-tab" class="tab-button py-4 px-6 text-sm font-medium text-center border-b-2 border-transparent hover:text-blue-600 hover:border-blue-300 transition duration-300">
                        <i class="fas fa-users mr-2"></i>Departments
                    </button>
                    <button onclick="showTab('stores')" id="stores-tab" class="tab-button py-4 px-6 text-sm font-medium text-center border-b-2 border-transparent hover:text-blue-600 hover:border-blue-300 transition duration-300">
                        <i class="fas fa-store mr-2"></i>Stores
                    </button>
                    <button onclick="showTab('roles')" id="roles-tab" class="tab-button py-4 px-6 text-sm font-medium text-center border-b-2 border-transparent hover:text-blue-600 hover:border-blue-300 transition duration-300">
                        <i class="fas fa-user-tie mr-2"></i>Roles
                    </button>
                    <button onclick="showTab('recipients')" id="recipients-tab" class="tab-button py-4 px-6 text-sm font-medium text-center border-b-2 border-transparent hover:text-blue-600 hover:border-blue-300 transition duration-300">
                        <i class="fas fa-address-book mr-2"></i>Recipients
                    </button>
                </nav>
            </div>
        </div>

        <!-- Tab Contents -->
        <div id="tab-contents">
            
            <!-- Companies Tab -->
            <div id="companies-tab-content" class="tab-content active">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-semibold text-gray-800">Manage Companies</h2>
                        <button onclick="openAddModal('company')" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-300 text-sm font-medium">
                            <i class="fas fa-plus mr-2"></i>Add Company
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php while ($company = $companies->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo $company['id']; ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($company['company_name']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-500"><?php echo date('M j, Y', strtotime($company['created_at'])); ?></td>
                                    <td class="px-4 py-3">
                                        <div class="flex space-x-2">
                                            <button onclick="openEditModal('company', <?php echo $company['id']; ?>, '<?php echo htmlspecialchars($company['company_name']); ?>')" 
                                                    class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                                <i class="fas fa-edit mr-1"></i>Edit
                                            </button>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="company_id" value="<?php echo $company['id']; ?>">
                                                <button type="submit" name="delete_company" 
                                                        onclick="return confirm('Are you sure you want to delete this company? This will affect all related brands and users.')"
                                                        class="text-red-600 hover:text-red-900 text-sm font-medium">
                                                    <i class="fas fa-trash mr-1"></i>Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Brands Tab -->
            <div id="brands-tab-content" class="tab-content hidden">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-semibold text-gray-800">Manage Brands</h2>
                        <button onclick="openAddModal('brand')" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-300 text-sm font-medium">
                            <i class="fas fa-plus mr-2"></i>Add Brand
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brand Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php 
                                $brands->execute();
                                while ($brand = $brands->fetch(PDO::FETCH_ASSOC)): 
                                ?>
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo $brand['id']; ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($brand['brand_name']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($brand['company_name'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-500"><?php echo date('M j, Y', strtotime($brand['created_at'])); ?></td>
                                    <td class="px-4 py-3">
                                        <div class="flex space-x-2">
                                            <button onclick="openEditModal('brand', <?php echo $brand['id']; ?>, '<?php echo htmlspecialchars($brand['brand_name']); ?>', '<?php echo $brand['company_id']; ?>')" 
                                                    class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                                <i class="fas fa-edit mr-1"></i>Edit
                                            </button>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="brand_id" value="<?php echo $brand['id']; ?>">
                                                <button type="submit" name="delete_brand" 
                                                        onclick="return confirm('Are you sure you want to delete this brand? This will affect all related stores and users.')"
                                                        class="text-red-600 hover:text-red-900 text-sm font-medium">
                                                    <i class="fas fa-trash mr-1"></i>Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Departments Tab -->
            <div id="departments-tab-content" class="tab-content hidden">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-semibold text-gray-800">Manage Departments</h2>
                        <button onclick="openAddModal('department')" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-300 text-sm font-medium">
                            <i class="fas fa-plus mr-2"></i>Add Department
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php while ($dept = $departments->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo $dept['id']; ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($dept['department_name']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-500"><?php echo date('M j, Y', strtotime($dept['created_at'])); ?></td>
                                    <td class="px-4 py-3">
                                        <div class="flex space-x-2">
                                            <button onclick="openEditModal('department', <?php echo $dept['id']; ?>, '<?php echo htmlspecialchars($dept['department_name']); ?>')" 
                                                    class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                                <i class="fas fa-edit mr-1"></i>Edit
                                            </button>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="department_id" value="<?php echo $dept['id']; ?>">
                                                <button type="submit" name="delete_department" 
                                                        onclick="return confirm('Are you sure you want to delete this department? This will affect all related users.')"
                                                        class="text-red-600 hover:text-red-900 text-sm font-medium">
                                                    <i class="fas fa-trash mr-1"></i>Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Stores Tab -->
            <div id="stores-tab-content" class="tab-content hidden">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-semibold text-gray-800">Manage Stores</h2>
                        <button onclick="openAddModal('store')" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-300 text-sm font-medium">
                            <i class="fas fa-plus mr-2"></i>Add Store
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Store Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brand</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php 
                                $stores->execute();
                                while ($store = $stores->fetch(PDO::FETCH_ASSOC)): 
                                ?>
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo $store['id']; ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($store['store_name']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($store['brand_name'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-500"><?php echo date('M j, Y', strtotime($store['created_at'])); ?></td>
                                    <td class="px-4 py-3">
                                        <div class="flex space-x-2">
                                            <button onclick="openEditModal('store', <?php echo $store['id']; ?>, '<?php echo htmlspecialchars($store['store_name']); ?>', '<?php echo $store['brand_id']; ?>')" 
                                                    class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                                <i class="fas fa-edit mr-1"></i>Edit
                                            </button>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="store_id" value="<?php echo $store['id']; ?>">
                                                <button type="submit" name="delete_store" 
                                                        onclick="return confirm('Are you sure you want to delete this store? This will affect all related users and recipients.')"
                                                        class="text-red-600 hover:text-red-900 text-sm font-medium">
                                                    <i class="fas fa-trash mr-1"></i>Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Roles Tab -->
            <div id="roles-tab-content" class="tab-content hidden">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-semibold text-gray-800">Manage Roles</h2>
                        <button onclick="openAddModal('role')" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-300 text-sm font-medium">
                            <i class="fas fa-plus mr-2"></i>Add Role
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                                    <th class="px-4-py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php while ($role = $roles->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo $role['id']; ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($role['role_name']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-500"><?php echo date('M j, Y', strtotime($role['created_at'])); ?></td>
                                    <td class="px-4 py-3">
                                        <div class="flex space-x-2">
                                            <button onclick="openEditModal('role', <?php echo $role['id']; ?>, '<?php echo htmlspecialchars($role['role_name']); ?>')" 
                                                    class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                                <i class="fas fa-edit mr-1"></i>Edit
                                            </button>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="role_id" value="<?php echo $role['id']; ?>">
                                                <button type="submit" name="delete_role" 
                                                        onclick="return confirm('Are you sure you want to delete this role? This will affect all users with this role.')"
                                                        class="text-red-600 hover:text-red-900 text-sm font-medium">
                                                    <i class="fas fa-trash mr-1"></i>Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recipients Tab -->
            <div id="recipients-tab-content" class="tab-content hidden">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-semibold text-gray-800">Manage Store Recipients</h2>
                        <button onclick="openAddModal('recipient')" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-300 text-sm font-medium">
                            <i class="fas fa-plus mr-2"></i>Add Recipient
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Store</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recipient Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($recipients as $recipient): ?>
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo $recipient['id']; ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($recipient['store_name']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($recipient['name']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($recipient['position']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-500"><?php echo date('M j, Y', strtotime($recipient['created_at'])); ?></td>
                                    <td class="px-4 py-3">
                                        <div class="flex space-x-2">
                                            <button onclick="openEditModal('recipient', <?php echo $recipient['id']; ?>, '<?php echo htmlspecialchars($recipient['name']); ?>', '<?php echo htmlspecialchars($recipient['position']); ?>')" 
                                                    class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                                <i class="fas fa-edit mr-1"></i>Edit
                                            </button>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="recipient_id" value="<?php echo $recipient['id']; ?>">
                                                <button type="submit" name="delete_recipient" 
                                                        onclick="return confirm('Are you sure you want to delete this recipient?')"
                                                        class="text-red-600 hover:text-red-900 text-sm font-medium">
                                                    <i class="fas fa-trash mr-1"></i>Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Add/Edit Modals -->
    <!-- Company Modal -->
    <div id="companyModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800" id="companyModalTitle">Add Company</h3>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="company_id" id="companyId">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Company Name</label>
                    <input type="text" name="company_name" id="companyName" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeModal('companyModal')" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-300">
                        Cancel
                    </button>
                    <button type="submit" name="add_company" id="companySubmitBtn" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-300 font-medium">
                        Add Company
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Brand Modal -->
    <div id="brandModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800" id="brandModalTitle">Add Brand</h3>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="brand_id" id="brandId">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Brand Name</label>
                    <input type="text" name="brand_name" id="brandName" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Company</label>
                    <select name="company_id" id="brandCompanyId" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select Company</option>
                        <?php 
                        $companies->execute();
                        while ($company = $companies->fetch(PDO::FETCH_ASSOC)): 
                        ?>
                        <option value="<?php echo $company['id']; ?>"><?php echo htmlspecialchars($company['company_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeModal('brandModal')" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-300">
                        Cancel
                    </button>
                    <button type="submit" name="add_brand" id="brandSubmitBtn" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-300 font-medium">
                        Add Brand
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Department Modal -->
    <div id="departmentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800" id="departmentModalTitle">Add Department</h3>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="department_id" id="departmentId">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Department Name</label>
                    <input type="text" name="department_name" id="departmentName" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeModal('departmentModal')" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-300">
                        Cancel
                    </button>
                    <button type="submit" name="add_department" id="departmentSubmitBtn" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-300 font-medium">
                        Add Department
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Store Modal -->
    <div id="storeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800" id="storeModalTitle">Add Store</h3>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="store_id" id="storeId">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Store Name</label>
                    <input type="text" name="store_name" id="storeName" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                    <select name="brand_id" id="storeBrandId" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select Brand</option>
                        <?php 
                        $brands->execute();
                        while ($brand = $brands->fetch(PDO::FETCH_ASSOC)): 
                        ?>
                        <option value="<?php echo $brand['id']; ?>"><?php echo htmlspecialchars($brand['brand_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeModal('storeModal')" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-300">
                        Cancel
                    </button>
                    <button type="submit" name="add_store" id="storeSubmitBtn" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-300 font-medium">
                        Add Store
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Role Modal -->
    <div id="roleModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800" id="roleModalTitle">Add Role</h3>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="role_id" id="roleId">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role Name</label>
                    <input type="text" name="role_name" id="roleName" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeModal('roleModal')" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-300">
                        Cancel
                    </button>
                    <button type="submit" name="add_role" id="roleSubmitBtn" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-300 font-medium">
                        Add Role
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Recipient Modal -->
    <div id="recipientModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800" id="recipientModalTitle">Add Recipient</h3>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="recipient_id" id="recipientId">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Store</label>
                    <select name="store_id" id="recipientStoreId" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select Store</option>
                        <?php 
                        $stores->execute();
                        while ($store = $stores->fetch(PDO::FETCH_ASSOC)): 
                        ?>
                        <option value="<?php echo $store['id']; ?>"><?php echo htmlspecialchars($store['store_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Recipient Name</label>
                    <input type="text" name="name" id="recipientName" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
                    <input type="text" name="position" id="recipientPosition" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeModal('recipientModal')" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-300">
                        Cancel
                    </button>
                    <button type="submit" name="add_recipient" id="recipientSubmitBtn" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-300 font-medium">
                        Add Recipient
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Tab functionality
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('hidden');
                tab.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active', 'border-blue-500', 'text-blue-600');
                button.classList.add('text-gray-500');
            });
            
            // Show selected tab content
            document.getElementById(tabName + '-tab-content').classList.remove('hidden');
            document.getElementById(tabName + '-tab-content').classList.add('active');
            
            // Activate selected tab button
            document.getElementById(tabName + '-tab').classList.add('active', 'border-blue-500', 'text-blue-600');
            document.getElementById(tabName + '-tab').classList.remove('text-gray-500');
        }

        // Modal functionality
        function openAddModal(type) {
            const modal = document.getElementById(type + 'Modal');
            const title = modal.querySelector('h3');
            const submitBtn = modal.querySelector('button[type="submit"]');
            
            title.textContent = 'Add ' + type.charAt(0).toUpperCase() + type.slice(1);
            submitBtn.name = 'add_' + type;
            submitBtn.textContent = 'Add ' + type.charAt(0).toUpperCase() + type.slice(1);
            
            // Clear form
            modal.querySelector('form').reset();
            modal.querySelector('input[type="hidden"]').value = '';
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function openEditModal(type, id, name, extraValue = null) {
            const modal = document.getElementById(type + 'Modal');
            const title = modal.querySelector('h3');
            const submitBtn = modal.querySelector('button[type="submit"]');
            const idField = modal.querySelector('input[type="hidden"]');
            const nameField = modal.querySelector('input[type="text"]');
            
            title.textContent = 'Edit ' + type.charAt(0).toUpperCase() + type.slice(1);
            submitBtn.name = 'update_' + type;
            submitBtn.textContent = 'Update ' + type.charAt(0).toUpperCase() + type.slice(1);
            
            idField.value = id;
            nameField.value = name;
            
            // Handle extra values for specific modals
            if (extraValue) {
                if (type === 'brand' || type === 'store') {
                    const selectField = modal.querySelector('select');
                    if (selectField) selectField.value = extraValue;
                } else if (type === 'recipient') {
                    const positionField = modal.querySelectorAll('input[type="text"]')[1];
                    if (positionField) positionField.value = extraValue;
                }
            }
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        // Close modals when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('fixed')) {
                e.target.classList.remove('flex');
                e.target.classList.add('hidden');
            }
        });

        // Initialize first tab
        document.addEventListener('DOMContentLoaded', function() {
            showTab('companies');
        });
    </script>
</body>
</html>