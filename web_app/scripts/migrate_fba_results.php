<?php
/**
 * Migration script to add fba_results table
 * Run once to upgrade database schema
 */
require_once __DIR__ . '/../config.php';

try {
    $pdo = getDbConnection();

    // Read migration SQL
    $sql = file_get_contents(__DIR__ . '/../schema_fba_results.sql');

    // Execute migration
    $pdo->exec($sql);

    echo "✅ Migration completed successfully!\n";
    echo "fba_results table created.\n";

} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
