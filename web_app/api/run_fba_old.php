<?php
require_once '../config.php';
requireAuth();

header('Content-Type: application/json');

try {
    // Get FBA configuration from POST request
    $input = json_decode(file_get_contents('php://input'), true);

    $config = [
        'exchange_reaction' => $input['exchange_reaction'] ?? 'MAR09809',
        'lower_bound' => $input['lower_bound'] ?? -1.0,
        'upper_bound' => $input['upper_bound'] ?? 0,
        'carbon_strategy' => $input['carbon_strategy'] ?? 'strict',
        'objective' => $input['objective'] ?? 'biomass',
        'solver' => $input['solver'] ?? 'glpk',
        'flux_threshold' => $input['flux_threshold'] ?? 1e-6,
        'pfba' => $input['pfba'] ?? false,
        'loopless' => $input['loopless'] ?? false
    ];

    // Get selected reactions from database
    $session_id = getSessionId();
    $pdo = getDbConnection();

    $stmt = $pdo->prepare("SELECT reaction_id FROM user_selections WHERE session_id = ? AND model_id = 1");
    $stmt->execute([$session_id]);
    $reactionIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($reactionIds)) {
        throw new Exception("No reactions selected");
    }

    $reactionIdsJson = json_encode($reactionIds);
    $configJson = json_encode($config);

    $pythonScript = SCRIPTS_PATH . '/fba_validator.py';

    // Execute Python script with configuration
    $command = "python3 " . escapeshellarg($pythonScript) . " " . escapeshellarg($reactionIdsJson) . " " . escapeshellarg($configJson) . " 2>&1";
    exec($command, $output, $returnCode);

    if ($returnCode === 0) {
        $jsonOutput = implode("\n", $output);
        $result = json_decode($jsonOutput, true);

        if ($result && json_last_error() === JSON_ERROR_NONE) {
            // Store FBA results in session and database
            if ($result['success']) {
                $_SESSION['fba_results'] = $result;

                $sessionId = getSessionId();
                $pdo = getDbConnection();

                // Count active reactions
                $activeCount = 0;
                foreach ($result['fluxes'] as $flux) {
                    if (abs($flux['flux']) > 1e-6) {
                        $activeCount++;
                    }
                }

                // Store in database with configuration
                $stmt = $pdo->prepare("INSERT INTO fba_results (session_id, active_reactions, total_flux, results_json, config_json) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $sessionId,
                    $activeCount,
                    $result['objective_value'],
                    json_encode($result['fluxes']),
                    $configJson
                ]);
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
