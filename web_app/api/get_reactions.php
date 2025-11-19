<?php
require_once '../config.php';
requireAuth();

header('Content-Type: application/json');

try {
    // Check if model is loaded
    if (!isset($_SESSION['model_loaded'])) {
        throw new Exception("Nessun modello caricato");
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $offset = isset($input['offset']) ? (int)$input['offset'] : 0;
    $limit = isset($input['limit']) ? (int)$input['limit'] : 50;

    $modelUrl = $_SESSION['model_loaded']['url'];
    $pythonScript = SCRIPTS_PATH . '/load_gem.py';

    // Execute Python script with pagination
    $command = "python3 " . escapeshellarg($pythonScript) . " " . escapeshellarg($modelUrl) . " " . escapeshellarg($offset) . " " . escapeshellarg($limit) . " 2>&1";
    exec($command, $output, $returnCode);

    if ($returnCode === 0) {
        $jsonOutput = end($output);
        $result = json_decode($jsonOutput, true);

        if ($result && json_last_error() === JSON_ERROR_NONE) {
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
