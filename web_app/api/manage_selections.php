<?php
require_once '../config.php';
requireAuth();

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $action = $input['action'] ?? '';
    $reaction_ids = $input['reaction_ids'] ?? [];

    if (!in_array($action, ['add', 'remove', 'clear'])) {
        throw new Exception("Invalid action");
    }

    $session_id = getSessionId();
    $model_id = 1; // Human-GEM

    $pdo = getDbConnection();

    if ($action === 'add') {
        if (empty($reaction_ids)) {
            throw new Exception("No reactions specified");
        }

        $stmt = $pdo->prepare("
            INSERT IGNORE INTO user_selections (session_id, model_id, reaction_id)
            VALUES (?, ?, ?)
        ");

        foreach ($reaction_ids as $rxn_id) {
            $stmt->execute([$session_id, $model_id, $rxn_id]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Added ' . count($reaction_ids) . ' reaction(s)'
        ]);

    } elseif ($action === 'remove') {
        if (empty($reaction_ids)) {
            throw new Exception("No reactions specified");
        }

        $placeholders = implode(',', array_fill(0, count($reaction_ids), '?'));
        $stmt = $pdo->prepare("
            DELETE FROM user_selections
            WHERE session_id = ? AND model_id = ? AND reaction_id IN ($placeholders)
        ");

        $params = array_merge([$session_id, $model_id], $reaction_ids);
        $stmt->execute($params);

        echo json_encode([
            'success' => true,
            'message' => 'Removed ' . count($reaction_ids) . ' reaction(s)'
        ]);

    } elseif ($action === 'clear') {
        $stmt = $pdo->prepare("
            DELETE FROM user_selections
            WHERE session_id = ? AND model_id = ?
        ");
        $stmt->execute([$session_id, $model_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Cleared all selections'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
