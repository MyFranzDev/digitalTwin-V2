<?php
require_once '../config.php';
requireAuth();

try {
    if (!isset($_SESSION['ensg_list']) || empty($_SESSION['ensg_list'])) {
        die("No ENSG data to download");
    }

    $ensgList = $_SESSION['ensg_list'];

    // Generate CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="ensg_list_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');

    // Header
    fputcsv($output, ['ENSG_ID']);

    // Data
    foreach ($ensgList as $ensg) {
        fputcsv($output, [$ensg]);
    }

    fclose($output);
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
