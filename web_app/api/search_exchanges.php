<?php
require_once '../config.php';
requireAuth();

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $query = $input['query'] ?? '';

    $pdo = getDbConnection();

    // Find exchange reactions (reactions where all metabolites are extracellular)
    // In Human-GEM, exchange reactions typically have IDs starting with MAR and involve [s] compartment
    $sql = "
        SELECT DISTINCT r.id, r.name, r.formula
        FROM reactions r
        WHERE r.model_id = 1
        AND (r.id LIKE 'MAR%' OR r.name LIKE '%exchange%' OR r.name LIKE '%EX_%')
        AND (r.name LIKE ? OR r.id LIKE ?)
        ORDER BY r.id
        LIMIT 50
    ";

    $searchTerm = '%' . $query . '%';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$searchTerm, $searchTerm]);

    $exchanges = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'exchanges' => $exchanges
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
