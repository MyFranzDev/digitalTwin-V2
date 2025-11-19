<?php
// Database configuration
define('DB_HOST', 'mysql.newrality.com');
define('DB_NAME', 'digitaltwin');
define('DB_USER', 'digitaltwin');
define('DB_PASS', 'touchlabs2');

// App configuration
define('APP_PASSWORD', 'digitaltwin2025');  // Password condivisa per accesso
define('APP_NAME', 'Butyrate Pathway Explorer');

// Paths
define('ROOT_PATH', __DIR__);
define('SCRIPTS_PATH', ROOT_PATH . '/scripts');

// Database connection
function getDbConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        die("Errore connessione database");
    }
}

// Initialize session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
function requireAuth() {
    if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
        header('Location: index.php');
        exit;
    }
}

// Get or create session ID
function getSessionId() {
    if (!isset($_SESSION['app_session_id'])) {
        $_SESSION['app_session_id'] = bin2hex(random_bytes(16));

        // Insert into DB
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("INSERT INTO sessions (session_id) VALUES (?)");
        $stmt->execute([$_SESSION['app_session_id']]);
    }
    return $_SESSION['app_session_id'];
}
?>
