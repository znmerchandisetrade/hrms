<?php
// user_form_fields.php - Reusable user form fields
include_once 'config/Database.php';
include_once 'models/Reference.php';

$database = new Database();
$db = $database->getConnection();
$reference = new Reference($db);

// Get reference data
$companies = $reference->getCompanies();
$brands = $reference->getBrands();
$departments = $reference->getDepartments();
$stores = $reference->getStores();
$roles = $reference->getRoles();
?>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Username *</label>
        <input type="text" name="username" required 
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
               placeholder="Enter username">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
        <input type="text" name="full_name" required 
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
               placeholder="Enter full name">
    </div>
</div>

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">Profile Image</label>
    <input type="file" name="profile_image" accept="image/*"
           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
    <p class="text-xs text-gray-500 mt-1">Supported formats: JPG, JPEG, PNG, GIF (Max: 2MB)</p>
</div>

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
    <input type="password" name="password" required 
           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
           placeholder="Enter password">
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Company *</label>
        <select name="company_id" required 
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="">Select Company</option>
            <?php while ($company = $companies->fetch(PDO::FETCH_ASSOC)): ?>
            <option value="<?php echo $company['id']; ?>"><?php echo htmlspecialchars($company['company_name']); ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Brand *</label>
        <select name="brand_id" required 
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="">Select Brand</option>
            <?php while ($brand = $brands->fetch(PDO::FETCH_ASSOC)): ?>
            <option value="<?php echo $brand['id']; ?>"><?php echo htmlspecialchars($brand['brand_name']); ?></option>
            <?php endwhile; ?>
        </select>
    </div>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Department *</label>
        <select name="department_id" required 
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="">Select Department</option>
            <?php while ($dept = $departments->fetch(PDO::FETCH_ASSOC)): ?>
            <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Store *</label>
        <select name="store_id" required 
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="">Select Store</option>
            <?php while ($store = $stores->fetch(PDO::FETCH_ASSOC)): ?>
            <option value="<?php echo $store['id']; ?>"><?php echo htmlspecialchars($store['store_name']); ?></option>
            <?php endwhile; ?>
        </select>
    </div>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
        <select name="role_id" required 
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="">Select Role</option>
            <?php while ($role = $roles->fetch(PDO::FETCH_ASSOC)): ?>
            <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['role_name']); ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Date Hired *</label>
        <input type="date" name="date_hired" required 
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
    </div>
</div>

<div class="flex space-x-3 pt-4">
    <button type="button" onclick="closeUserModal()" 
            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-300">
        Cancel
    </button>
    <button type="submit" 
            class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-300 font-medium">
        Create User
    </button>
</div>