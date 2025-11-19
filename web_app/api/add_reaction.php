<?php
require_once '../config.php';
requireAuth();

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['id']) || !isset($input['name'])) {
        throw new Exception("Missing required fields");
    }

    $reaction = [
        'id' => $input['id'],
        'name' => $input['name'],
        'subsystem' => $input['subsystem'] ?? ''
    ];

    // Initialize selected reactions in session
    if (!isset($_SESSION['selected_reactions'])) {
        $_SESSION['selected_reactions'] = [];
    }

    // Check if already selected
    foreach ($_SESSION['selected_reactions'] as $rxn) {
        if ($rxn['id'] === $reaction['id']) {
            throw new Exception("Reaction already selected");
        }
    }

    // Add to session
    $_SESSION['selected_reactions'][] = $reaction;

    // Also save to database
    $sessionId = getSessionId();
    $pdo = getDbConnection();

    $stmt = $pdo->prepare("INSERT INTO selected_reactions (session_id, reaction_id, reaction_name, subsystem) VALUES (?, ?, ?, ?)");
    $stmt->execute([$sessionId, $reaction['id'], $reaction['name'], $reaction['subsystem']]);

    echo json_encode([
        'success' => true,
        'total_selected' => count($_SESSION['selected_reactions'])
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
