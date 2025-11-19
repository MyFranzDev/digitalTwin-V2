<?php
require_once '../config.php';
requireAuth();

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['id'])) {
        throw new Exception("Missing reaction ID");
    }

    $reactionId = $input['id'];

    // Remove from session
    if (isset($_SESSION['selected_reactions'])) {
        $_SESSION['selected_reactions'] = array_filter(
            $_SESSION['selected_reactions'],
            function($rxn) use ($reactionId) {
                return $rxn['id'] !== $reactionId;
            }
        );
        $_SESSION['selected_reactions'] = array_values($_SESSION['selected_reactions']); // Re-index
    }

    // Remove from database
    $sessionId = getSessionId();
    $pdo = getDbConnection();

    $stmt = $pdo->prepare("DELETE FROM selected_reactions WHERE session_id = ? AND reaction_id = ?");
    $stmt->execute([$sessionId, $reactionId]);

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
