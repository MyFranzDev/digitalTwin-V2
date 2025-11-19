<?php
require_once '../config.php';
requireAuth();

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $query = $input['query'] ?? '';

    $pdo = getDbConnection();

    // Search all reactions for objective function
    $sql = "
        SELECT r.id, r.name, r.subsystem, r.formula
        FROM reactions r
        WHERE r.model_id = 1
        AND (r.name LIKE ? OR r.id LIKE ? OR r.subsystem LIKE ?)
        ORDER BY r.id
        LIMIT 100
    ";

    $searchTerm = '%' . $query . '%';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);

    $reactions = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'reactions' => $reactions
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
