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

    $pythonScript = SCRIPTS_PATH . '/ensg_extractor.py';

    // Execute Python script
    $command = "python3 " . escapeshellarg($pythonScript) . " " . escapeshellarg($reactionIdsJson) . " 2>&1";
    exec($command, $output, $returnCode);

    if ($returnCode === 0) {
        $jsonOutput = implode("\n", $output);
        $result = json_decode($jsonOutput, true);

        if ($result && json_last_error() === JSON_ERROR_NONE) {
            // Store ENSG list in session for download
            if ($result['success']) {
                $_SESSION['ensg_list'] = $result['unique_ensg'];

                // Update database with ENSG list
                $sessionId = getSessionId();
                $pdo = getDbConnection();

                foreach ($result['reactions_ensg'] as $rxn_ensg) {
                    $ensgListStr = implode(',', $rxn_ensg['ensg_list']);
                    $stmt = $pdo->prepare("UPDATE selected_reactions SET ensg_list = ? WHERE session_id = ? AND reaction_id = ?");
                    $stmt->execute([$ensgListStr, $sessionId, $rxn_ensg['id']]);
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
