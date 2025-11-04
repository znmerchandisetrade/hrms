<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include_once 'config/Database.php';
include_once 'models/Reference.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $reference = new Reference($db);

    $brand_id = isset($_GET['brand_id']) ? (int)$_GET['brand_id'] : null;
    
    if ($brand_id) {
        $stores = $reference->getStores($brand_id);
    } else {
        $stores = $reference->getStores();
    }

    $result = [];
    if ($stores) {
        while ($row = $stores->fetch(PDO::FETCH_ASSOC)) {
            $result[] = [
                'id' => (int)$row['id'],
                'store_name' => $row['store_name'],
                'brand_id' => $row['brand_id'] ? (int)$row['brand_id'] : null
            ];
        }
    }

    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Get stores error: " . $e->getMessage());
    echo json_encode([]);
}
?>