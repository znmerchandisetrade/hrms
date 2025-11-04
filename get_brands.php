<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include_once 'config/Database.php';
include_once 'models/Reference.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $reference = new Reference($db);

    $company_id = isset($_GET['company_id']) ? (int)$_GET['company_id'] : null;
    
    if ($company_id) {
        $brands = $reference->getBrands($company_id);
    } else {
        $brands = $reference->getBrands();
    }

    $result = [];
    if ($brands) {
        while ($row = $brands->fetch(PDO::FETCH_ASSOC)) {
            $result[] = [
                'id' => (int)$row['id'],
                'brand_name' => $row['brand_name'],
                'company_id' => $row['company_id'] ? (int)$row['company_id'] : null
            ];
        }
    }

    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Get brands error: " . $e->getMessage());
    echo json_encode([]);
}
?>