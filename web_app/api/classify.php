<?php
require_once '../config.php';
requireAuth();

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['selected_reactions']) || empty($_SESSION['selected_reactions'])) {
        throw new Exception("No reactions selected");
    }

    // Get reaction IDs
    $reactionIds = array_column($_SESSION['selected_reactions'], 'id');
    $reactionIdsJson = json_encode($reactionIds);

    $pythonScript = SCRIPTS_PATH . '/classifier.py';

    // Execute Python script
    $command = "python3 " . escapeshellarg($pythonScript) . " " . escapeshellarg($reactionIdsJson) . " 2>&1";
    exec($command, $output, $returnCode);

    if ($returnCode === 0) {
        $jsonOutput = implode("\n", $output);
        $result = json_decode($jsonOutput, true);

        if ($result && json_last_error() === JSON_ERROR_NONE) {
            // Update session with phase information
            if ($result['success']) {
                foreach ($_SESSION['selected_reactions'] as &$rxn) {
                    foreach ($result['classified'] as $classified) {
                        if ($rxn['id'] === $classified['id']) {
                            $rxn['phase'] = $classified['phase'];
                            break;
                        }
                    }
                }

                // Update database
                $sessionId = getSessionId();
                $pdo = getDbConnection();

                foreach ($result['classified'] as $classified) {
                    $stmt = $pdo->prepare("UPDATE selected_reactions SET phase = ? WHERE session_id = ? AND reaction_id = ?");
                    $stmt->execute([$classified['phase'], $sessionId, $classified['id']]);
                }
            }

            echo json_encode($result);
        } else {
            throw new Exception("Invalid JSON from Python script");
        }
    } else {
        throw new Exception("Python script failed: " . implode("\n", $output));
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
