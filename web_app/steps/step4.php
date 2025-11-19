<?php
// Get selected reactions from database
$session_id = getSessionId();
$pdo = getDbConnection();
$stmt = $pdo->prepare("SELECT reaction_id FROM user_selections WHERE session_id = ? AND model_id = 1");
$stmt->execute([$session_id]);
$selectedReactions = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get reaction details for bounds table
$pathwayReactions = [];
if (!empty($selectedReactions)) {
    $placeholders = implode(',', array_fill(0, count($selectedReactions), '?'));
    $stmt = $pdo->prepare("
        SELECT id, name, lower_bound, upper_bound, reversibility
        FROM reactions
        WHERE id IN ($placeholders) AND model_id = 1
        ORDER BY id
    ");
    $stmt->execute($selectedReactions);
    $pathwayReactions = $stmt->fetchAll();
}
?>

<style>
.autocomplete-container {
    position: relative;
}
.autocomplete-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-top: none;
    max-height: 200px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
}
.autocomplete-item {
    padding: 8px 12px;
    cursor: pointer;
    font-size: 13px;
}
.autocomplete-item:hover {
    background: #f0f0f0;
}
.bounds-table {
    width: 100%;
    font-size: 13px;
}
.bounds-table th {
    background: #e9ecef;
    padding: 8px;
    text-align: left;
    font-weight: 600;
}
.bounds-table td {
    padding: 6px 8px;
    border-bottom: 1px solid #dee2e6;
}
.bounds-input {
    width: 80px;
    padding: 4px 6px;
    font-size: 12px;
    border: 1px solid #ddd;
    border-radius: 3px;
}
.section-divider {
    margin: 30px 0;
    border-top: 2px solid #dee2e6;
    padding-top: 25px;
}
.medium-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    margin-bottom: 8px;
}
.constraint-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 40px;
    gap: 10px;
    margin-bottom: 10px;
    align-items: center;
}
</style>

<div class="step-container">
    <h1 class="step-title">⚡ Advanced FBA Configuration</h1>
    <p class="step-subtitle">Full control over Flux Balance Analysis parameters</p>

    <div style="margin-top: 30px;">
        <?php if (empty($selectedReactions)): ?>
            <div class="alert alert-warning">
                No reactions selected. <a href="?step=1">Go back to Step 1</a> to select reactions.
            </div>
        <?php else: ?>

            <!-- Main Configuration -->
            <div style="background: #f8f9fa; padding: 25px; border-radius: 8px;">
                <h3 style="margin-top: 0; color: #0f364c; font-size: 16px; margin-bottom: 20px;">1. Primary Exchange & Objective</h3>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <!-- Exchange Reaction Autocomplete -->
                    <div class="form-group">
                        <label style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 6px; display: block;">Primary Exchange Reaction</label>
                        <div class="autocomplete-container">
                            <input type="text" id="exchangeSearch" placeholder="Search exchange reactions..."
                                   style="width: 100%; padding: 8px; font-size: 13px; border: 1px solid #ddd; border-radius: 4px;">
                            <input type="hidden" id="exchangeReaction" value="MAR09809">
                            <div id="exchangeResults" class="autocomplete-results"></div>
                        </div>
                        <div id="exchangeSelected" style="margin-top: 6px; font-size: 12px; color: #666;">
                            Selected: <strong>MAR09809 - Butyrate exchange</strong>
                        </div>
                    </div>

                    <!-- Objective Function Autocomplete -->
                    <div class="form-group">
                        <label style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 6px; display: block;">Objective Function</label>
                        <div class="autocomplete-container">
                            <input type="text" id="objectiveSearch" placeholder="Search reactions..."
                                   style="width: 100%; padding: 8px; font-size: 13px; border: 1px solid #ddd; border-radius: 4px;">
                            <input type="hidden" id="objectiveFunction" value="biomass">
                            <div id="objectiveResults" class="autocomplete-results"></div>
                        </div>
                        <div id="objectiveSelected" style="margin-top: 6px; font-size: 12px; color: #666;">
                            Selected: <strong>Biomass (default)</strong>
                        </div>
                    </div>

                    <!-- Primary Exchange Bounds -->
                    <div class="form-group">
                        <label style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 6px; display: block;">Primary Exchange Bounds</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div>
                                <label style="font-size: 12px; color: #666;">Lower Bound</label>
                                <input type="number" id="lowerBound" value="-1.0" step="0.1"
                                       style="width: 100%; padding: 6px; font-size: 13px; border: 1px solid #ddd; border-radius: 4px;">
                            </div>
                            <div>
                                <label style="font-size: 12px; color: #666;">Upper Bound</label>
                                <input type="number" id="upperBound" value="0" step="0.1"
                                       style="width: 100%; padding: 6px; font-size: 13px; border: 1px solid #ddd; border-radius: 4px;">
                            </div>
                        </div>
                    </div>

                    <!-- Solver & Threshold -->
                    <div class="form-group">
                        <label style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 6px; display: block;">Solver & Threshold</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <select id="solver" style="padding: 6px; font-size: 13px; border: 1px solid #ddd; border-radius: 4px;">
                                <option value="glpk">GLPK</option>
                                <option value="cplex">CPLEX</option>
                                <option value="gurobi">Gurobi</option>
                            </select>
                            <input type="text" id="fluxThreshold" value="1e-6" placeholder="Flux threshold"
                                   style="padding: 6px; font-size: 13px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                    </div>
                </div>

                <!-- Advanced Options -->
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; display: flex; gap: 20px;">
                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                        <input type="checkbox" id="pfba">
                        <span style="font-size: 13px;">Parsimonious FBA</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                        <input type="checkbox" id="loopless">
                        <span style="font-size: 13px;">Loopless</span>
                    </label>
                </div>
            </div>

            <!-- Pathway Reactions Bounds -->
            <div class="section-divider">
                <h3 style="color: #0f364c; font-size: 16px; margin-bottom: 15px;">2. Pathway Reactions Individual Bounds</h3>
                <p style="font-size: 13px; color: #666; margin-bottom: 15px;">Override bounds for specific pathway reactions</p>

                <table class="bounds-table">
                    <thead>
                        <tr>
                            <th>Reaction ID</th>
                            <th>Name</th>
                            <th>Default Lower</th>
                            <th>Default Upper</th>
                            <th>Custom Lower</th>
                            <th>Custom Upper</th>
                            <th>Override</th>
                        </tr>
                    </thead>
                    <tbody id="boundsTableBody">
                        <?php foreach ($pathwayReactions as $rxn): ?>
                        <tr>
                            <td><code style="font-size: 11px;"><?= htmlspecialchars($rxn['id']) ?></code></td>
                            <td style="font-size: 12px;"><?= htmlspecialchars($rxn['name']) ?></td>
                            <td><?= $rxn['lower_bound'] ?></td>
                            <td><?= $rxn['upper_bound'] ?></td>
                            <td>
                                <input type="number" class="bounds-input custom-lower"
                                       data-reaction="<?= $rxn['id'] ?>"
                                       value="<?= $rxn['lower_bound'] ?>" step="0.1">
                            </td>
                            <td>
                                <input type="number" class="bounds-input custom-upper"
                                       data-reaction="<?= $rxn['id'] ?>"
                                       value="<?= $rxn['upper_bound'] ?>" step="0.1">
                            </td>
                            <td>
                                <input type="checkbox" class="override-check" data-reaction="<?= $rxn['id'] ?>">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Medium Composition -->
            <div class="section-divider">
                <h3 style="color: #0f364c; font-size: 16px; margin-bottom: 15px;">3. Medium Composition (Exchange Reactions)</h3>
                <p style="font-size: 13px; color: #666; margin-bottom: 15px;">Select additional exchange reactions to open/close</p>

                <div style="margin-bottom: 15px;">
                    <div class="autocomplete-container" style="max-width: 500px;">
                        <input type="text" id="mediumSearch" placeholder="Search exchange reactions to add..."
                               style="width: 100%; padding: 8px; font-size: 13px; border: 1px solid #ddd; border-radius: 4px;">
                        <div id="mediumResults" class="autocomplete-results"></div>
                    </div>
                </div>

                <div id="mediumList" style="max-height: 300px; overflow-y: auto;">
                    <!-- Medium items will be added here dynamically -->
                </div>
            </div>

            <!-- Custom Constraints -->
            <div class="section-divider">
                <h3 style="color: #0f364c; font-size: 16px; margin-bottom: 15px;">4. Custom Constraints</h3>
                <p style="font-size: 13px; color: #666; margin-bottom: 15px;">Add arbitrary constraints on any reaction</p>

                <div id="constraintsList">
                    <!-- Constraints will be added here -->
                </div>

                <button onclick="addConstraint()" class="btn btn-secondary" style="font-size: 13px; margin-top: 10px;">
                    ➕ Add Constraint
                </button>
            </div>

            <!-- Action Buttons -->
            <div style="margin-top: 30px; padding-top: 25px; border-top: 2px solid #dee2e6;">
                <div style="display: flex; gap: 12px; justify-content: flex-end; flex-wrap: wrap;">
                    <button onclick="savePreset()" class="btn btn-secondary">💾 Save Preset</button>
                    <button onclick="loadPreset()" class="btn btn-secondary">📂 Load Preset</button>
                    <button onclick="runFBA()" class="btn btn-primary" style="font-size: 14px;">⚡ Run FBA Analysis</button>
                </div>
            </div>

            <!-- Results Area -->
            <div id="fbaResults" style="margin-top: 25px;"></div>

            <!-- Comparison Area -->
            <div id="comparisonArea" style="margin-top: 25px; display: none;"></div>

        <?php endif; ?>
    </div>
</div>

<script>
// Store FBA results history
let fbaHistory = [];
let mediumExchanges = [];
let customConstraints = [];

// Autocomplete for Exchange Reactions
let exchangeTimeout;
document.getElementById('exchangeSearch').addEventListener('input', function(e) {
    clearTimeout(exchangeTimeout);
    const query = e.target.value;

    if (query.length < 2) {
        document.getElementById('exchangeResults').style.display = 'none';
        return;
    }

    exchangeTimeout = setTimeout(async () => {
        const result = await apiCall('api/search_exchanges.php', { query });
        if (result.success) {
            const resultsDiv = document.getElementById('exchangeResults');
            resultsDiv.innerHTML = result.exchanges.map(ex => `
                <div class="autocomplete-item" onclick="selectExchange('${ex.id}', '${ex.name.replace(/'/g, "\\'")}')">
                    <strong>${ex.id}</strong> - ${ex.name}
                </div>
            `).join('');
            resultsDiv.style.display = 'block';
        }
    }, 300);
});

function selectExchange(id, name) {
    document.getElementById('exchangeReaction').value = id;
    document.getElementById('exchangeSearch').value = '';
    document.getElementById('exchangeSelected').innerHTML = `Selected: <strong>${id} - ${name}</strong>`;
    document.getElementById('exchangeResults').style.display = 'none';
}

// Autocomplete for Objective Function
let objectiveTimeout;
document.getElementById('objectiveSearch').addEventListener('input', function(e) {
    clearTimeout(objectiveTimeout);
    const query = e.target.value;

    if (query.length < 2) {
        document.getElementById('objectiveResults').style.display = 'none';
        return;
    }

    objectiveTimeout = setTimeout(async () => {
        const result = await apiCall('api/search_all_reactions.php', { query });
        if (result.success) {
            const resultsDiv = document.getElementById('objectiveResults');
            resultsDiv.innerHTML = result.reactions.map(rxn => `
                <div class="autocomplete-item" onclick="selectObjective('${rxn.id}', '${rxn.name.replace(/'/g, "\\'")}')">
                    <strong>${rxn.id}</strong> - ${rxn.name}
                </div>
            `).join('');
            resultsDiv.style.display = 'block';
        }
    }, 300);
});

function selectObjective(id, name) {
    document.getElementById('objectiveFunction').value = id;
    document.getElementById('objectiveSearch').value = '';
    document.getElementById('objectiveSelected').innerHTML = `Selected: <strong>${id} - ${name}</strong>`;
    document.getElementById('objectiveResults').style.display = 'none';
}

// Medium composition autocomplete
let mediumTimeout;
document.getElementById('mediumSearch').addEventListener('input', function(e) {
    clearTimeout(mediumTimeout);
    const query = e.target.value;

    if (query.length < 2) {
        document.getElementById('mediumResults').style.display = 'none';
        return;
    }

    mediumTimeout = setTimeout(async () => {
        const result = await apiCall('api/search_exchanges.php', { query });
        if (result.success) {
            const resultsDiv = document.getElementById('mediumResults');
            resultsDiv.innerHTML = result.exchanges.map(ex => `
                <div class="autocomplete-item" onclick="addMediumExchange('${ex.id}', '${ex.name.replace(/'/g, "\\'")}')">
                    <strong>${ex.id}</strong> - ${ex.name}
                </div>
            `).join('');
            resultsDiv.style.display = 'block';
        }
    }, 300);
});

function addMediumExchange(id, name) {
    if (mediumExchanges.find(m => m.id === id)) return;

    mediumExchanges.push({ id, name, lower: -10, upper: 1000, active: true });
    document.getElementById('mediumSearch').value = '';
    document.getElementById('mediumResults').style.display = 'none';
    renderMediumList();
}

function removeMediumExchange(id) {
    mediumExchanges = mediumExchanges.filter(m => m.id !== id);
    renderMediumList();
}

function renderMediumList() {
    const listDiv = document.getElementById('mediumList');
    listDiv.innerHTML = mediumExchanges.map((m, idx) => `
        <div class="medium-item">
            <input type="checkbox" ${m.active ? 'checked' : ''} onchange="mediumExchanges[${idx}].active = this.checked">
            <strong style="min-width: 80px;">${m.id}</strong>
            <span style="flex: 1; font-size: 12px;">${m.name}</span>
            <input type="number" value="${m.lower}" step="0.1" style="width: 70px; padding: 4px;"
                   onchange="mediumExchanges[${idx}].lower = parseFloat(this.value)">
            <input type="number" value="${m.upper}" step="0.1" style="width: 70px; padding: 4px;"
                   onchange="mediumExchanges[${idx}].upper = parseFloat(this.value)">
            <button onclick="removeMediumExchange('${m.id}')" class="btn btn-secondary" style="padding: 4px 8px; font-size: 11px;">✖</button>
        </div>
    `).join('');
}

// Custom constraints
function addConstraint() {
    customConstraints.push({ reaction: '', lower: null, upper: null });
    renderConstraints();
}

function removeConstraint(idx) {
    customConstraints.splice(idx, 1);
    renderConstraints();
}

function renderConstraints() {
    const listDiv = document.getElementById('constraintsList');
    listDiv.innerHTML = customConstraints.map((c, idx) => `
        <div class="constraint-row">
            <input type="text" placeholder="Reaction ID" value="${c.reaction}"
                   onchange="customConstraints[${idx}].reaction = this.value"
                   style="padding: 6px; font-size: 13px; border: 1px solid #ddd; border-radius: 4px;">
            <input type="number" placeholder="Lower" value="${c.lower || ''}" step="0.1"
                   onchange="customConstraints[${idx}].lower = this.value ? parseFloat(this.value) : null"
                   style="padding: 6px; font-size: 13px; border: 1px solid #ddd; border-radius: 4px;">
            <input type="number" placeholder="Upper" value="${c.upper || ''}" step="0.1"
                   onchange="customConstraints[${idx}].upper = this.value ? parseFloat(this.value) : null"
                   style="padding: 6px; font-size: 13px; border: 1px solid #ddd; border-radius: 4px;">
            <span style="font-size: 11px; color: #666;">mmol/gDW/h</span>
            <button onclick="removeConstraint(${idx})" style="padding: 4px 8px; font-size: 11px;">✖</button>
        </div>
    `).join('');
}

// Get FBA configuration
function getFBAConfig() {
    // Get pathway reaction bounds overrides
    const pathwayBounds = {};
    document.querySelectorAll('.override-check:checked').forEach(cb => {
        const rxnId = cb.dataset.reaction;
        const lower = parseFloat(document.querySelector(`.custom-lower[data-reaction="${rxnId}"]`).value);
        const upper = parseFloat(document.querySelector(`.custom-upper[data-reaction="${rxnId}"]`).value);
        pathwayBounds[rxnId] = { lower, upper };
    });

    return {
        exchange_reaction: document.getElementById('exchangeReaction').value,
        lower_bound: parseFloat(document.getElementById('lowerBound').value),
        upper_bound: parseFloat(document.getElementById('upperBound').value),
        objective: document.getElementById('objectiveFunction').value,
        solver: document.getElementById('solver').value,
        flux_threshold: parseFloat(document.getElementById('fluxThreshold').value),
        pfba: document.getElementById('pfba').checked,
        loopless: document.getElementById('loopless').checked,
        pathway_bounds: pathwayBounds,
        medium_exchanges: mediumExchanges.filter(m => m.active),
        custom_constraints: customConstraints.filter(c => c.reaction)
    };
}

async function runFBA() {
    const resultsDiv = document.getElementById('fbaResults');
    const config = getFBAConfig();

    resultsDiv.innerHTML = '<div class="loading">⏳ Running FBA analysis...</div>';

    try {
        const result = await apiCall('api/run_fba.php', config);

        if (result.success) {
            fbaHistory.push({
                timestamp: new Date().toISOString(),
                config: config,
                result: result
            });

            if (fbaHistory.length > 1) {
                document.getElementById('comparisonArea').style.display = 'block';
                showComparison();
            }

            const activeCount = result.fluxes.filter(f => Math.abs(f.flux) > config.flux_threshold).length;
            const totalCount = result.fluxes.length;

            let html = `
                <div class="alert ${activeCount > 0 ? 'alert-success' : 'alert-danger'}">
                    ${activeCount > 0 ? '✅' : '❌'} <strong>FBA Completed:</strong> ${activeCount}/${totalCount} reactions with active flux
                </div>
                <h3 style="color: #0f364c; margin: 25px 0 15px;">📊 Reaction Fluxes</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Reaction ID</th>
                            <th>Flux</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            result.fluxes.forEach(f => {
                const isActive = Math.abs(f.flux) > config.flux_threshold;
                html += `
                    <tr style="${!isActive ? 'opacity: 0.6;' : ''}">
                        <td><code>${f.reaction_id}</code></td>
                        <td>${f.flux.toFixed(6)}</td>
                        <td><span class="badge ${isActive ? 'badge-success' : 'badge-danger'}">${isActive ? '✅ Active' : '❌ Inactive'}</span></td>
                    </tr>
                `;
            });

            html += `
                    </tbody>
                </table>
                <div style="margin-top: 30px; display: flex; gap: 12px;">
                    <button class="btn btn-secondary" onclick="exportResultsJSON()">📄 Export Results JSON</button>
                    <button class="btn btn-secondary" onclick="exportConfigJSON()">⚙️ Export Config JSON</button>
                </div>
            `;

            resultsDiv.innerHTML = html;
        } else {
            throw new Error(result.error || 'FBA Error');
        }
    } catch (error) {
        resultsDiv.innerHTML = `<div class="error">Error: ${error.message}</div>`;
    }
}

function exportConfigJSON() {
    const config = getFBAConfig();
    const dataStr = JSON.stringify(config, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
    const url = URL.createObjectURL(dataBlob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `fba_config_${new Date().toISOString().slice(0,19).replace(/:/g,'-')}.json`;
    link.click();
    URL.revokeObjectURL(url);
}

function exportResultsJSON() {
    if (fbaHistory.length === 0) {
        alert('No FBA results to export');
        return;
    }
    const latestRun = fbaHistory[fbaHistory.length - 1];
    const dataStr = JSON.stringify(latestRun, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
    const url = URL.createObjectURL(dataBlob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `fba_results_${new Date().toISOString().slice(0,19).replace(/:/g,'-')}.json`;
    link.click();
    URL.revokeObjectURL(url);
}

function savePreset() {
    const presetName = prompt('Enter preset name:');
    if (!presetName) return;
    const config = getFBAConfig();
    const presets = JSON.parse(localStorage.getItem('fba_presets') || '{}');
    presets[presetName] = config;
    localStorage.setItem('fba_presets', JSON.stringify(presets));
    alert(`Preset "${presetName}" saved!`);
}

function loadPreset() {
    const presets = JSON.parse(localStorage.getItem('fba_presets') || '{}');
    const names = Object.keys(presets);
    if (names.length === 0) {
        alert('No presets found');
        return;
    }
    const name = prompt(`Available presets:\n${names.join('\n')}\n\nEnter name:`);
    if (!name || !presets[name]) return;

    const config = presets[name];
    document.getElementById('exchangeReaction').value = config.exchange_reaction;
    document.getElementById('lowerBound').value = config.lower_bound;
    document.getElementById('upperBound').value = config.upper_bound;
    document.getElementById('objectiveFunction').value = config.objective;
    document.getElementById('solver').value = config.solver;
    document.getElementById('fluxThreshold').value = config.flux_threshold;
    document.getElementById('pfba').checked = config.pfba;
    document.getElementById('loopless').checked = config.loopless;
    mediumExchanges = config.medium_exchanges || [];
    customConstraints = config.custom_constraints || [];
    renderMediumList();
    renderConstraints();
    alert(`Preset "${name}" loaded!`);
}

function showComparison() {
    const comparisonArea = document.getElementById('comparisonArea');
    let html = `
        <div style="background: #f8f9fa; padding: 25px; border-radius: 8px;">
            <h3 style="color: #0f364c; margin-top: 0;">📊 Multi-Run Comparison (${fbaHistory.length} runs)</h3>
            <table style="width: 100%; font-size: 13px;">
                <thead>
                    <tr>
                        <th>Run</th>
                        <th>Timestamp</th>
                        <th>Exchange</th>
                        <th>Objective</th>
                        <th>Active Rxns</th>
                    </tr>
                </thead>
                <tbody>
    `;
    fbaHistory.forEach((run, idx) => {
        const activeCount = run.result.fluxes.filter(f => Math.abs(f.flux) > 1e-6).length;
        html += `
            <tr>
                <td><strong>#${idx + 1}</strong></td>
                <td>${new Date(run.timestamp).toLocaleString()}</td>
                <td><code>${run.config.exchange_reaction}</code></td>
                <td><code>${run.config.objective}</code></td>
                <td>${activeCount}/${run.result.fluxes.length}</td>
            </tr>
        `;
    });
    html += `
                </tbody>
            </table>
        </div>
    `;
    comparisonArea.innerHTML = html;
}

// Close autocomplete when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.autocomplete-container')) {
        document.querySelectorAll('.autocomplete-results').forEach(div => div.style.display = 'none');
    }
});
</script>
