<?php
require_once 'config.php';
requireAuth();
getSessionId();

// Get current step from URL
$currentStep = isset($_GET['step']) ? (int)$_GET['step'] : 0;
$currentStep = max(0, min(3, $currentStep)); // Limit 0-3

// Steps configuration
$steps = [
    0 => ['title' => 'Background & Model', 'icon' => '📚', 'file' => 'steps/step0.php'],
    1 => ['title' => 'Select Reactions', 'icon' => '🔍', 'file' => 'steps/step2.php'],
    2 => ['title' => 'Extract Genes', 'icon' => '🧬', 'file' => 'steps/step3.php'],
    3 => ['title' => 'FBA Validation', 'icon' => '⚡', 'file' => 'steps/step4.php'],
];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Step <?= $currentStep ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="assets/newrality.png" alt="Logo" class="sidebar-logo">
                <h2><?= APP_NAME ?></h2>
            </div>

            <nav class="sidebar-nav">
                <?php foreach ($steps as $stepNum => $stepInfo): ?>
                    <a href="?step=<?= $stepNum ?>"
                       class="step-item <?= $stepNum === $currentStep ? 'active' : '' ?>">
                        <span class="step-number">Step <?= $stepNum ?></span>
                        <span class="step-icon"><?= $stepInfo['icon'] ?></span>
                        <span class="step-title"><?= $stepInfo['title'] ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="sidebar-footer">
                <div style="font-size: 11px; opacity: 0.85; margin-bottom: 20px; line-height: 1.5; text-align: center;">
                    <div style="font-weight: 600; margin-bottom: 4px; color: rgba(255,255,255,0.9);">Scientific Direction</div>
                    <div style="color: rgba(255,255,255,0.8);">Dr. Daniela Olivero</div>

                    <div style="font-weight: 600; margin-top: 14px; margin-bottom: 4px; color: rgba(255,255,255,0.9);">Development</div>
                    <div style="color: rgba(255,255,255,0.8);">Newrality</div>
                </div>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-wrapper">
                <?php
                // Include current step file
                $stepFile = $steps[$currentStep]['file'];
                if (file_exists($stepFile)) {
                    include $stepFile;
                } else {
                    echo '<div class="error">Step non trovato</div>';
                }
                ?>
            </div>
        </main>
    </div>

    <script src="script.js"></script>
</body>
</html>
