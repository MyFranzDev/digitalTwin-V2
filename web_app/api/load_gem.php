<?php
require_once '../config.php';
requireAuth();

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $model = $input['model'] ?? 'Human-GEM';
    $offset = 0; // Always start from 0 on initial load
    $limit = 50; // Load first 50 reactions

    $pythonScript = SCRIPTS_PATH . '/load_gem.py';

    // Execute Python script with model parameter and pagination
    $command = "python3 " . escapeshellarg($pythonScript) . " " . escapeshellarg($model) . " " . escapeshellarg($offset) . " " . escapeshellarg($limit) . " 2>&1";
    exec($command, $output, $returnCode);

    if ($returnCode === 0) {
        // Parse JSON output from Python (last line only, as stderr messages are mixed in)
        $jsonOutput = end($output);
        $result = json_decode($jsonOutput, true);

        if ($result && json_last_error() === JSON_ERROR_NONE) {
            // Save model info in session for persistence across steps
            $_SESSION['model_loaded'] = [
                'url' => $model,
                'stats' => $result['stats'],
                'preview' => $result['preview'],
                'pagination' => $result['pagination'],
                'loaded_at' => time()
            ];

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
