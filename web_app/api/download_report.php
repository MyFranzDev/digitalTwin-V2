<?php
require_once '../config.php';
requireAuth();

try {
    if (!isset($_SESSION['selected_reactions']) || empty($_SESSION['selected_reactions'])) {
        die("No data to download");
    }

    $reactions = $_SESSION['selected_reactions'];
    $fbaResults = $_SESSION['fba_results'] ?? null;

    // Generate CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="butyrate_pathway_report_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');

    // Header
    fputcsv($output, ['Reaction_ID', 'Reaction_Name', 'Phase', 'Subsystem', 'ENSG_List', 'FBA_Flux']);

    // Data
    foreach ($reactions as $rxn) {
        $flux = 'N/A';

        if ($fbaResults && isset($fbaResults['fluxes'])) {
            foreach ($fbaResults['fluxes'] as $fbaFlux) {
                if ($fbaFlux['reaction_id'] === $rxn['id']) {
                    $flux = number_format($fbaFlux['flux'], 6);
                    break;
                }
            }
        }

        fputcsv($output, [
            $rxn['id'],
            $rxn['name'],
            $rxn['phase'] ?? 'N/A',
            $rxn['subsystem'] ?? 'N/A',
            $rxn['ensg_list'] ?? '',
            $flux
        ]);
    }

    fclose($output);
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
