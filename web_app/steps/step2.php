<?php
// Get session ID
$session_id = getSessionId();
$pdo = getDbConnection();

// Get filter options for dropdowns
$subsystems_stmt = $pdo->query("SELECT DISTINCT subsystem FROM reactions WHERE model_id = 1 AND subsystem IS NOT NULL ORDER BY subsystem");
$subsystems = $subsystems_stmt->fetchAll(PDO::FETCH_COLUMN);

// Get user's currently selected reactions
$selections_stmt = $pdo->prepare("SELECT reaction_id FROM user_selections WHERE session_id = ? AND model_id = 1");
$selections_stmt->execute([$session_id]);
$selected_reactions = $selections_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<script>
// JavaScript functions at top for availability
function toggleReactionDetails(idx) {
    const detailsRow = document.getElementById('details-' + idx);
    if (detailsRow) {
        if (detailsRow.style.display === 'none' || detailsRow.style.display === '') {
            detailsRow.style.display = 'table-row';
        } else {
            detailsRow.style.display = 'none';
        }
    }
}
</script>

<div class="step-container">
    <h1 class="step-title">🔍 Reaction Selection</h1>
    <p class="step-subtitle">Search, filter, and select reactions for pathway analysis</p>

    <!-- Floating Selection Badge -->
    <div id="selectionBadge" onclick="toggleDrawer()" style="position: fixed; top: 20px; right: 20px; background: #0066cc; color: white; padding: 12px 20px; border-radius: 25px; cursor: pointer; box-shadow: 0 4px 12px rgba(0,102,204,0.3); z-index: 100; transition: all 0.3s; display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600;">
        <span>✓</span>
        <span id="badgeCount"><?= count($selected_reactions) ?></span>
        <span style="opacity: 0.9; font-weight: 400;">Reactions</span>
    </div>

    <div style="margin-top: 30px;">
            <!-- Search & Filter Form -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <h3 style="margin-top: 0; color: #0f364c; font-size: 16px;">🔍 Search & Filter</h3>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Search (ID, Name, Subsystem)</label>
                    <input type="text" id="searchQuery" placeholder="e.g. butyrate, MAR12345, beta-oxidation..."
                           style="width: 100%; font-size: 14px;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="form-group">
                        <label>Subsystem</label>
                        <select id="filterSubsystem" style="width: 100%; font-size: 14px;">
                            <option value="">All Subsystems</option>
                            <?php foreach ($subsystems as $sub): ?>
                                <option value="<?= htmlspecialchars($sub) ?>"><?= htmlspecialchars($sub) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Reversibility</label>
                        <select id="filterReversibility" style="width: 100%; font-size: 14px;">
                            <option value="">All Types</option>
                            <option value="1">Reversible Only</option>
                            <option value="0">Irreversible Only</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button onclick="searchReactions()" class="btn btn-primary" style="font-size: 14px; padding: 8px 20px;">
                        🔍 Search
                    </button>
                    <button onclick="clearFilters()" class="btn btn-secondary" style="font-size: 14px; padding: 8px 20px;">
                        Clear Filters
                    </button>
                </div>
            </div>

            <!-- Results table -->
            <div id="resultsContainer">
                <p style="text-align: center; color: #666; padding: 40px;">
                    Use the search and filters above to find reactions
                </p>
            </div>

            <!-- Pagination (hidden initially) -->
            <div id="paginationControls" style="display: none; margin-top: 20px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <button id="prevPageBtn" onclick="loadPage(currentPage - 1)" class="btn btn-secondary" disabled
                            style="font-size: 13px; padding: 8px 16px;">
                        ← Previous
                    </button>
                    <button id="nextPageBtn" onclick="loadPage(currentPage + 1)" class="btn btn-secondary" disabled
                            style="font-size: 13px; padding: 8px 16px; margin-left: 10px;">
                        Next →
                    </button>
                </div>
                <div style="font-size: 13px; color: #666;">
                    Page <strong id="currentPageNum">1</strong> of <strong id="totalPagesNum">1</strong>
                    (Total: <strong id="totalResultsNum">0</strong> reactions)
                </div>
            </div>

    <!-- Selection Drawer (slide-in panel) -->
    <div id="selectionDrawer" style="position: fixed; top: 0; right: -400px; width: 400px; height: 100vh; background: white; box-shadow: -4px 0 20px rgba(0,0,0,0.15); z-index: 1000; transition: right 0.3s ease; overflow-y: auto;">
        <div style="padding: 25px; border-bottom: 1px solid #e0e0e0; background: #f8f9fa; position: sticky; top: 0; z-index: 10;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; color: #0f364c; font-size: 18px;">Selected Reactions</h3>
                <button onclick="toggleDrawer()" style="background: none; border: none; font-size: 24px; color: #666; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">&times;</button>
            </div>
            <div style="margin-top: 10px; font-size: 14px; color: #666;">
                <span id="drawerCount"><?= count($selected_reactions) ?></span> reaction(s) selected
            </div>
        </div>

        <div id="drawerList" style="padding: 20px;">
            <?php if (empty($selected_reactions)): ?>
                <p style="color: #999; text-align: center; padding: 40px 20px;">
                    No reactions selected yet.<br>
                    <span style="font-size: 13px;">Use checkboxes to select reactions.</span>
                </p>
            <?php else: ?>
                <?php foreach ($selected_reactions as $rxn_id): ?>
                    <?php
                        $rxn_stmt = $pdo->prepare("SELECT id, name FROM reactions WHERE id = ? AND model_id = 1");
                        $rxn_stmt->execute([$rxn_id]);
                        $rxn = $rxn_stmt->fetch();
                    ?>
                    <div class="selected-reaction-item" data-reaction-id="<?= htmlspecialchars($rxn['id']) ?>"
                         style="background: #f8f9fa; padding: 12px; margin-bottom: 10px; border-radius: 6px; border-left: 3px solid #0066cc;">
                        <div style="display: flex; justify-content: space-between; align-items: start; gap: 10px;">
                            <div style="flex: 1; font-size: 13px;">
                                <code style="font-size: 11px; color: #0066cc; font-weight: 600;"><?= htmlspecialchars($rxn['id']) ?></code><br>
                                <span style="color: #333; line-height: 1.4;"><?= htmlspecialchars($rxn['name']) ?></span>
                            </div>
                            <button onclick="removeSelection('<?= htmlspecialchars($rxn['id']) ?>')"
                                    style="background: #dc3545; border: none; color: white; cursor: pointer; font-size: 12px; padding: 4px 8px; border-radius: 4px; flex-shrink: 0;"
                                    title="Remove">✕</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($selected_reactions)): ?>
        <div style="padding: 20px; border-top: 1px solid #e0e0e0; background: #f8f9fa; position: sticky; bottom: 0;">
            <button onclick="clearAllSelections()" class="btn btn-secondary" style="width: 100%; margin-bottom: 10px;">
                Clear All
            </button>
            <a href="?step=2" class="btn btn-primary" style="width: 100%; text-align: center; display: block; text-decoration: none;">
                Continue →
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Overlay for drawer -->
    <div id="drawerOverlay" onclick="toggleDrawer()" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; display: none; opacity: 0; transition: opacity 0.3s;"></div>

    </div>
</div>

<!-- Bottom Action Bar -->
<div style="position: sticky; bottom: 0; left: 0; width: 100%; background: white; border-top: 2px solid #e0e0e0; box-shadow: 0 -4px 12px rgba(0,0,0,0.08); z-index: 50; margin-top: 40px;">
    <div style="max-width: 1400px; margin: 0 auto; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center;">
        <div style="font-size: 14px; color: #666;">
            Selection: <strong id="bottomCount" style="color: #0066cc;"><?= count($selected_reactions) ?></strong> reactions
        </div>
        <div style="display: flex; gap: 12px;">
            <button onclick="toggleDrawer()" class="btn btn-secondary" style="font-size: 14px;">
                Review Selection
            </button>
            <a href="?step=2" class="btn btn-primary" style="font-size: 14px; text-decoration: none;">
                Continue →
            </a>
        </div>
    </div>
</div>

<script>
// Drawer toggle function
function toggleDrawer() {
    const drawer = document.getElementById('selectionDrawer');
    const overlay = document.getElementById('drawerOverlay');
    const isOpen = drawer.style.right === '0px';

    if (isOpen) {
        drawer.style.right = '-400px';
        overlay.style.display = 'none';
        setTimeout(() => overlay.style.opacity = '0', 10);
    } else {
        drawer.style.right = '0px';
        overlay.style.display = 'block';
        setTimeout(() => overlay.style.opacity = '1', 10);
    }
}

// Update all counters
function updateCounters(count) {
    document.getElementById('badgeCount').textContent = count;
    document.getElementById('bottomCount').textContent = count;
    if (document.getElementById('drawerCount')) {
        document.getElementById('drawerCount').textContent = count;
    }
}

let currentPage = 1;
let currentFilters = {};

async function searchReactions() {
    currentPage = 1;
    currentFilters = {
        query: document.getElementById('searchQuery').value.trim(),
        subsystem: document.getElementById('filterSubsystem').value,
        reversibility: document.getElementById('filterReversibility').value
    };

    loadPage(1);
}

async function loadPage(page) {
    currentPage = page;

    const resultsContainer = document.getElementById('resultsContainer');
    resultsContainer.innerHTML = '<div class="loading" style="text-align: center; padding: 40px;">⏳ Loading reactions...</div>';

    try {
        const result = await apiCall('api/search_reactions.php', {
            ...currentFilters,
            page,
            limit: 50
        });

        if (result.success) {
            renderResults(result);
        } else {
            throw new Error(result.error || 'Search failed');
        }
    } catch (error) {
        resultsContainer.innerHTML = `<div class="error" style="padding: 20px;">❌ Error: ${error.message}</div>`;
    }
}

function renderResults(result) {
    const resultsContainer = document.getElementById('resultsContainer');
    const paginationControls = document.getElementById('paginationControls');

    if (result.reactions.length === 0) {
        resultsContainer.innerHTML = '<p style="text-align: center; color: #666; padding: 40px;">No reactions found matching your criteria</p>';
        paginationControls.style.display = 'none';
        return;
    }

    // Render table
    let tableHtml = `
        <div style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
            <div style="font-size: 14px; color: #666;">
                Showing ${result.reactions.length} reactions
            </div>
            <button onclick="addSelectedToSidebar()" class="btn btn-primary" style="font-size: 13px; padding: 6px 16px;">
                Add Selected →
            </button>
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width: 40px;"><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this.checked)"></th>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Subsystem</th>
                </tr>
            </thead>
            <tbody>
    `;

    result.reactions.forEach((rxn, idx) => {
        const isSelected = window.selectedReactionIds && window.selectedReactionIds.has(rxn.id);

        tableHtml += `
            <tr>
                <td><input type="checkbox" class="rxn-checkbox" value="${rxn.id}" ${isSelected ? 'checked' : ''}></td>
                <td colspan="3" style="padding: 0;">
                    <div onclick="toggleReactionDetails(${idx})" style="cursor: pointer; padding: 12px;">
                        <code style="font-size: 12px;">${rxn.id}</code> | ${rxn.name} |
                        <span class="badge badge-primary">${rxn.subsystem || 'N/A'}</span>
                    </div>
                    <tr id="details-${idx}" style="display: none;">
                        <td colspan="4" style="background: #f8f9fa; padding: 25px;">
                            <div style="font-size: 13px;">
                                <!-- Basic Info -->
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                                    <div>
                                        <strong style="color: #0f364c;">Formula:</strong><br>
                                        <code style="font-size: 12px; background: white; padding: 8px; display: block; margin-top: 5px; border-radius: 4px;">${rxn.formula}</code>
                                    </div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                        <div>
                                            <strong style="color: #0f364c;">Reversibility:</strong><br>
                                            ${rxn.reversibility ? '✅ Reversible' : '❌ Irreversible'}
                                        </div>
                                        <div>
                                            <strong style="color: #0f364c;">Bounds:</strong><br>
                                            [${rxn.lower_bound}, ${rxn.upper_bound}]
                                        </div>
                                    </div>
                                </div>

                                <!-- Genes & GPR -->
                                ${rxn.genes && rxn.genes.length > 0 ? `
                                <div style="margin-bottom: 20px; padding: 15px; background: white; border-radius: 6px; border-left: 3px solid #4CAF50;">
                                    <strong style="color: #0f364c;">🧬 Genes (${rxn.genes.length}):</strong><br>
                                    <div style="margin-top: 8px; max-height: 100px; overflow-y: auto;">
                                        ${rxn.genes.map(g => `<code style="display: inline-block; margin: 3px; padding: 4px 8px; background: #e8f5e9; border-radius: 3px; font-size: 11px;">${g.gene_id}</code>`).join('')}
                                    </div>
                                    ${rxn.genes[0].gpr_rule ? `
                                        <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #e0e0e0;">
                                            <strong style="font-size: 12px;">GPR Rule:</strong><br>
                                            <code style="font-size: 11px; color: #555;">${rxn.genes[0].gpr_rule}</code>
                                        </div>
                                    ` : ''}
                                </div>
                                ` : '<div style="margin-bottom: 20px; color: #999;">No genes associated</div>'}

                                <!-- Metabolites -->
                                ${rxn.metabolites && rxn.metabolites.length > 0 ? `
                                <div style="margin-bottom: 20px; padding: 15px; background: white; border-radius: 6px; border-left: 3px solid #2196F3;">
                                    <strong style="color: #0f364c;">🧪 Metabolites (${rxn.metabolites.length}):</strong><br>
                                    <div style="margin-top: 8px; max-height: 150px; overflow-y: auto; font-size: 12px;">
                                        ${rxn.metabolites.map(m => `
                                            <div style="margin: 4px 0; padding: 6px; background: ${m.coefficient < 0 ? '#ffebee' : '#e3f2fd'}; border-radius: 3px;">
                                                <strong>${m.coefficient > 0 ? '+' : ''}${m.coefficient.toFixed(1)}</strong> ×
                                                <code style="font-size: 11px;">${m.id}</code>
                                                ${m.name ? `<span style="color: #666;"> — ${m.name}</span>` : ''}
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                                ` : ''}

                                <!-- Annotations -->
                                ${rxn.annotations && Object.keys(rxn.annotations).length > 0 ? `
                                <div style="margin-bottom: 20px; padding: 15px; background: white; border-radius: 6px; border-left: 3px solid #FF9800;">
                                    <strong style="color: #0f364c;">🔗 Database Cross-References:</strong><br>
                                    <div style="margin-top: 8px; display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 8px; font-size: 12px;">
                                        ${Object.entries(rxn.annotations).map(([db, val]) => `
                                            <div style="background: #fff3e0; padding: 6px 10px; border-radius: 4px;">
                                                <strong style="text-transform: uppercase; font-size: 10px; color: #E65100;">${db}:</strong><br>
                                                <code style="font-size: 11px;">${Array.isArray(val) ? val.join(', ') : val}</code>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                                ` : ''}

                                <!-- Notes -->
                                ${rxn.notes && Object.keys(rxn.notes).length > 0 ? `
                                <div style="padding: 15px; background: white; border-radius: 6px; border-left: 3px solid #9C27B0;">
                                    <strong style="color: #0f364c;">📝 Metadata & References:</strong><br>
                                    <div style="margin-top: 8px; font-size: 12px;">
                                        ${Object.entries(rxn.notes).map(([key, val]) => `
                                            <div style="margin: 6px 0;">
                                                <strong style="color: #7B1FA2;">${key}:</strong>
                                                <span style="color: #555;">${val}</span>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                                ` : ''}
                            </div>
                        </td>
                    </tr>
                </td>
            </tr>
        `;
    });

    tableHtml += '</tbody></table>';

    resultsContainer.innerHTML = tableHtml;

    // Update pagination
    document.getElementById('currentPageNum').textContent = result.pagination.current_page;
    document.getElementById('totalPagesNum').textContent = result.pagination.total_pages;
    document.getElementById('totalResultsNum').textContent = result.pagination.total.toLocaleString();

    document.getElementById('prevPageBtn').disabled = result.pagination.current_page <= 1;
    document.getElementById('nextPageBtn').disabled = result.pagination.current_page >= result.pagination.total_pages;

    paginationControls.style.display = 'flex';
}

function toggleSelectAll(checked) {
    document.querySelectorAll('.rxn-checkbox').forEach(cb => cb.checked = checked);
}

async function addSelectedToSidebar() {
    const checked = Array.from(document.querySelectorAll('.rxn-checkbox:checked')).map(cb => cb.value);

    if (checked.length === 0) {
        alert('Please select at least one reaction');
        return;
    }

    try {
        const result = await apiCall('api/manage_selections.php', {
            action: 'add',
            reaction_ids: checked
        });

        if (result.success) {
            window.location.reload();
        } else {
            throw new Error(result.error);
        }
    } catch (error) {
        alert('Error adding selections: ' + error.message);
    }
}

async function removeSelection(reactionId) {
    try {
        const result = await apiCall('api/manage_selections.php', {
            action: 'remove',
            reaction_ids: [reactionId]
        });

        if (result.success) {
            window.location.reload();
        } else {
            throw new Error(result.error);
        }
    } catch (error) {
        alert('Error removing selection: ' + error.message);
    }
}

async function clearAllSelections() {
    if (!confirm('Are you sure you want to clear all selected reactions?')) {
        return;
    }

    try {
        const result = await apiCall('api/manage_selections.php', {
            action: 'clear'
        });

        if (result.success) {
            window.location.reload();
        } else {
            throw new Error(result.error);
        }
    } catch (error) {
        alert('Error clearing selections: ' + error.message);
    }
}

function clearFilters() {
    document.getElementById('searchQuery').value = '';
    document.getElementById('filterSubsystem').value = '';
    document.getElementById('filterReversibility').value = '';
    document.getElementById('resultsContainer').innerHTML = '<p style="text-align: center; color: #666; padding: 40px;">Use the search and filters above to find reactions</p>';
    document.getElementById('paginationControls').style.display = 'none';
}

// Initialize selected reactions set from server
window.selectedReactionIds = new Set(<?= json_encode($selected_reactions) ?>);
</script>
