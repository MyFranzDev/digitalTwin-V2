<?php
// Get selected reactions from database
$session_id = getSessionId();
$pdo = getDbConnection();

$stmt = $pdo->prepare("SELECT reaction_id FROM user_selections WHERE session_id = ? AND model_id = 1");
$stmt->execute([$session_id]);
$selectedReactions = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Extract ENSG genes from selected reactions
$genes_data = [];
$unique_ensg = [];

if (!empty($selectedReactions)) {
    $placeholders = implode(',', array_fill(0, count($selectedReactions), '?'));

    // Get genes for each reaction
    $stmt = $pdo->prepare("
        SELECT rg.reaction_id, r.name as reaction_name, g.id as gene_id
        FROM reaction_genes rg
        JOIN reactions r ON rg.reaction_id = r.id AND rg.model_id = r.model_id
        JOIN genes g ON rg.gene_id = g.id AND rg.model_id = g.model_id
        WHERE rg.reaction_id IN ($placeholders) AND rg.model_id = 1
        ORDER BY rg.reaction_id, g.id
    ");
    $stmt->execute($selectedReactions);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group by reaction
    foreach ($results as $row) {
        $rxn_id = $row['reaction_id'];
        if (!isset($genes_data[$rxn_id])) {
            $genes_data[$rxn_id] = [
                'name' => $row['reaction_name'],
                'genes' => []
            ];
        }
        $genes_data[$rxn_id]['genes'][] = $row['gene_id'];
        $unique_ensg[$row['gene_id']] = true;
    }
}

$unique_ensg = array_keys($unique_ensg);
sort($unique_ensg);
?>

<div class="step-container">
    <h1 class="step-title">🧬 Gene Extraction</h1>
    <p class="step-subtitle">Human genes (ENSG IDs) extracted from selected reactions</p>

    <div style="margin-top: 30px;">
        <?php if (empty($selectedReactions)): ?>
            <div class="alert alert-warning">
                No reactions selected. <a href="?step=1">Go back to Step 1</a> to select reactions.
            </div>
        <?php else: ?>
            <!-- Summary -->
            <div class="alert alert-success">
                ✅ Extracted <strong><?= count($unique_ensg) ?></strong> unique genes from <strong><?= count($selectedReactions) ?></strong> selected reactions
            </div>

            <!-- Info box -->
            <div class="alert alert-info" style="margin-top: 20px;">
                <strong>ℹ️ About ENSG IDs:</strong><br>
                Ensembl Gene IDs (ENSG + 11 digits) are stable identifiers for human genes. These are extracted from the
                Gene-Protein-Reaction (GPR) rules associated with each metabolic reaction.
            </div>

            <!-- Unique ENSG list -->
            <div style="margin-top: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="color: #0f364c; margin: 0;">📋 Unique ENSG List (<?= count($unique_ensg) ?>)</h3>
                    <a href="api/download_ensg.php" class="btn btn-secondary" style="font-size: 13px; padding: 6px 16px;">
                        📥 Download CSV
                    </a>
                </div>

                <?php if (empty($unique_ensg)): ?>
                    <div class="alert" style="background: #fff3cd; color: #856404; padding: 20px;">
                        ⚠️ No genes found for selected reactions. This may indicate reactions without gene associations.
                    </div>
                <?php else: ?>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; max-height: 400px; overflow-y: auto;">
                        <?php foreach ($unique_ensg as $ensg): ?>
                            <code style="display: inline-block; margin: 5px; padding: 6px 12px; background: white; border-radius: 4px; border: 1px solid #dee2e6; font-size: 13px;">
                                <?= htmlspecialchars($ensg) ?>
                            </code>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Genes per reaction table -->
            <div style="margin-top: 40px;">
                <h3 style="color: #0f364c; margin-bottom: 15px;">🔬 Genes by Reaction</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Reaction ID</th>
                            <th>Reaction Name</th>
                            <th>ENSG Genes</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($selectedReactions as $rxn_id): ?>
                            <?php
                                // Get reaction details
                                $stmt = $pdo->prepare("SELECT name FROM reactions WHERE id = ? AND model_id = 1");
                                $stmt->execute([$rxn_id]);
                                $rxn = $stmt->fetch();
                                $genes = $genes_data[$rxn_id]['genes'] ?? [];
                            ?>
                            <tr>
                                <td><code style="font-size: 12px;"><?= htmlspecialchars($rxn_id) ?></code></td>
                                <td style="font-size: 13px;"><?= htmlspecialchars($rxn['name'] ?? 'Unknown') ?></td>
                                <td style="font-size: 12px;">
                                    <?php if (empty($genes)): ?>
                                        <em style="color: #999;">No genes</em>
                                    <?php else: ?>
                                        <?= htmlspecialchars(implode(', ', $genes)) ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-primary"><?= count($genes) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Next button outside container -->
<div style="margin-top: 40px; text-align: right; max-width: 1400px; margin-left: auto; margin-right: auto;">
    <a href="?step=3" class="btn btn-primary">Continue →</a>
</div>
