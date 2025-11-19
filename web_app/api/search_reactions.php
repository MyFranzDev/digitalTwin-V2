<?php
require_once '../config.php';
requireAuth();

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $query = $input['query'] ?? '';
    $subsystem = $input['subsystem'] ?? '';
    $reversibility = $input['reversibility'] ?? '';
    $page = max(1, (int)($input['page'] ?? 1));
    $limit = min(100, max(10, (int)($input['limit'] ?? 50)));
    $offset = ($page - 1) * $limit;

    $model_id = 1; // Human-GEM

    $pdo = getDbConnection();

    // Build WHERE clause
    $where = ["r.model_id = ?"];
    $params = [$model_id];

    // Search on name, id, subsystem
    if (!empty($query)) {
        $where[] = "(r.id LIKE ? OR r.name LIKE ? OR r.subsystem LIKE ?)";
        $searchTerm = '%' . $query . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    // Subsystem filter
    if (!empty($subsystem)) {
        $where[] = "r.subsystem = ?";
        $params[] = $subsystem;
    }

    // Reversibility filter
    if ($reversibility !== '') {
        $where[] = "r.reversibility = ?";
        $params[] = (int)$reversibility;
    }

    $where_sql = implode(' AND ', $where);

    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM reactions r WHERE $where_sql";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total = $count_stmt->fetch()['total'];

    // Get paginated results
    $sql = "
        SELECT
            r.id,
            r.name,
            r.subsystem,
            r.formula,
            r.reversibility,
            r.lower_bound,
            r.upper_bound,
            COUNT(DISTINCT rg.gene_id) as gene_count
        FROM reactions r
        LEFT JOIN reaction_genes rg ON r.id = rg.reaction_id AND r.model_id = rg.model_id
        WHERE $where_sql
        GROUP BY r.id, r.name, r.subsystem, r.formula, r.reversibility, r.lower_bound, r.upper_bound
        ORDER BY r.id
        LIMIT ? OFFSET ?
    ";

    $params[] = $limit;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $reactions = $stmt->fetchAll();

    // Enrich each reaction with detailed data
    if (!empty($reactions)) {
        $reaction_ids = array_column($reactions, 'id');
        $placeholders = implode(',', array_fill(0, count($reaction_ids), '?'));

        // Get genes with GPR rules
        $genes_sql = "
            SELECT rg.reaction_id, g.id as gene_id, rg.gpr_rule
            FROM reaction_genes rg
            JOIN genes g ON rg.gene_id = g.id AND rg.model_id = g.model_id
            WHERE rg.reaction_id IN ($placeholders) AND rg.model_id = ?
            ORDER BY rg.reaction_id, g.id
        ";
        $genes_params = array_merge($reaction_ids, [$model_id]);
        $genes_stmt = $pdo->prepare($genes_sql);
        $genes_stmt->execute($genes_params);
        $genes_data = [];
        foreach ($genes_stmt->fetchAll() as $row) {
            $genes_data[$row['reaction_id']][] = [
                'gene_id' => $row['gene_id'],
                'gpr_rule' => $row['gpr_rule']
            ];
        }

        // Get metabolites with coefficients
        $metabolites_sql = "
            SELECT rm.reaction_id, m.id as metabolite_id, m.name as metabolite_name, rm.coefficient
            FROM reaction_metabolites rm
            JOIN metabolites m ON rm.metabolite_id = m.id AND rm.model_id = m.model_id
            WHERE rm.reaction_id IN ($placeholders) AND rm.model_id = ?
            ORDER BY rm.reaction_id, ABS(rm.coefficient) DESC
        ";
        $metabolites_params = array_merge($reaction_ids, [$model_id]);
        $metabolites_stmt = $pdo->prepare($metabolites_sql);
        $metabolites_stmt->execute($metabolites_params);
        $metabolites_data = [];
        foreach ($metabolites_stmt->fetchAll() as $row) {
            $metabolites_data[$row['reaction_id']][] = [
                'id' => $row['metabolite_id'],
                'name' => $row['metabolite_name'],
                'coefficient' => (float)$row['coefficient']
            ];
        }

        // Get annotations
        $annotations_sql = "
            SELECT reaction_id, db_name, db_value
            FROM reaction_annotations
            WHERE reaction_id IN ($placeholders) AND model_id = ?
            ORDER BY reaction_id, db_name
        ";
        $annotations_params = array_merge($reaction_ids, [$model_id]);
        $annotations_stmt = $pdo->prepare($annotations_sql);
        $annotations_stmt->execute($annotations_params);
        $annotations_data = [];
        foreach ($annotations_stmt->fetchAll() as $row) {
            $annotations_data[$row['reaction_id']][$row['db_name']] = $row['db_value'];
        }

        // Get notes
        $notes_sql = "
            SELECT reaction_id, note_key, note_value
            FROM reaction_notes
            WHERE reaction_id IN ($placeholders) AND model_id = ?
            ORDER BY reaction_id, note_key
        ";
        $notes_params = array_merge($reaction_ids, [$model_id]);
        $notes_stmt = $pdo->prepare($notes_sql);
        $notes_stmt->execute($notes_params);
        $notes_data = [];
        foreach ($notes_stmt->fetchAll() as $row) {
            $notes_data[$row['reaction_id']][$row['note_key']] = $row['note_value'];
        }

        // Attach enriched data to each reaction
        foreach ($reactions as &$rxn) {
            $rxn['genes'] = $genes_data[$rxn['id']] ?? [];
            $rxn['metabolites'] = $metabolites_data[$rxn['id']] ?? [];
            $rxn['annotations'] = $annotations_data[$rxn['id']] ?? [];
            $rxn['notes'] = $notes_data[$rxn['id']] ?? [];
        }
    }

    echo json_encode([
        'success' => true,
        'reactions' => $reactions,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
