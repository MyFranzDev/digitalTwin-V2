<?php
// Get selected reactions from database
$session_id = getSessionId();
$pdo = getDbConnection();
$stmt = $pdo->prepare("SELECT reaction_id FROM user_selections WHERE session_id = ? AND model_id = 1");
$stmt->execute([$session_id]);
$selectedReactions = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="step-container">
    <h1 class="step-title">⚡ FBA Validation</h1>
    <p class="step-subtitle">Validate pathway functional completeness with Flux Balance Analysis</p>

    <div style="margin-top: 30px;">
        <?php if (empty($selectedReactions)): ?>
            <div class="alert alert-warning">
                No reactions selected. <a href="?step=1">Go back to Step 1</a> to select reactions.
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <strong>ℹ️ FBA (Flux Balance Analysis):</strong> Computational method that predicts metabolic fluxes
                through a reaction network using linear optimization.
            </div>

            <!-- FBA Configuration Panel -->
            <div style="background: #f8f9fa; padding: 25px; border-radius: 8px; margin-top: 25px;">
                <h3 style="margin-top: 0; color: #0f364c; font-size: 16px; margin-bottom: 20px;">FBA Configuration</h3>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <!-- Exchange Reaction -->
                    <div class="form-group">
                        <label style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 6px; display: block;">Exchange Reaction</label>
                        <select id="exchangeReaction" style="width: 100%; padding: 8px; font-size: 13px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="MAR09809">Butyrate (MAR09809)</option>
                            <option value="MAR09034">Glucose (MAR09034)</option>
                            <option value="MAR09072">Glutamine (MAR09072)</option>
                            <option value="MAR09079">Acetate (MAR09079)</option>
                            <option value="MAR09135">Propionate (MAR09135)</option>
                        </select>
                    </div>

                    <!-- Carbon Source Strategy -->
                    <div class="form-group">
                        <label style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 6px; display: block;">Carbon Source Strategy</label>
                        <select id="carbonStrategy" style="width: 100%; padding: 8px; font-size: 13px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="strict">Strict (close all others)</option>
                            <option value="permissive">Permissive (keep medium open)</option>
                        </select>
                    </div>

                    <!-- Uptake Constraints -->
                    <div class="form-group">
                        <label style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 6px; display: block;">Uptake Constraints</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div>
                                <label style="font-size: 12px; color: #666;">Lower Bound</label>
                                <input type="number" id="lowerBound" value="-1.0" step="0.1" style="width: 100%; padding: 6px; font-size: 13px; border: 1px solid #ddd; border-radius: 4px;">
                            </div>
                            <div>
                                <label style="font-size: 12px; color: #666;">Upper Bound</label>
                                <input type="number" id="upperBound" value="0" step="0.1" style="width: 100%; padding: 6px; font-size: 13px; border: 1px solid #ddd; border-radius: 4px;">
                            </div>
                        </div>
                    </div>

                    <!-- Objective Function -->
                    <div class="form-group">
                        <label style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 6px; display: block;">Objective Function</label>
                        <select id="objectiveFunction" style="width: 100%; padding: 8px; font-size: 13px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="biomass">Biomass Maximization</option>
                            <option value="atpm">ATP Production (ATPM)</option>
                        </select>
                    </div>

                    <!-- Flux Threshold -->
                    <div class="form-group">
                        <label style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 6px; display: block;">Flux Threshold (active/inactive)</label>
                        <input type="text" id="fluxThreshold" value="1e-6" style="width: 100%; padding: 8px; font-size: 13px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>

                    <!-- Solver -->
                    <div class="form-group">
                        <label style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 6px; display: block;">Solver</label>
                        <select id="solver" style="width: 100%; padding: 8px; font-size: 13px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="glpk">GLPK (default)</option>
                            <option value="cplex">CPLEX (if available)</option>
                            <option value="gurobi">Gurobi (if available)</option>
                        </select>
                    </div>
                </div>

                <!-- Advanced Options -->
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                    <div style="display: flex; gap: 20px; font-size: 13px;">
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="checkbox" id="pfba">
                            <span>Parsimonious FBA (pFBA)</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="checkbox" id="loopless">
                            <span>Loopless Solution</span>
                        </label>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div style="margin-top: 25px; display: flex; gap: 12px; justify-content: flex-end;">
                    <button onclick="savePreset()" class="btn btn-secondary" style="font-size: 13px;">
                        💾 Save Preset
                    </button>
                    <button onclick="loadPreset()" class="btn btn-secondary" style="font-size: 13px;">
                        📂 Load Preset
                    </button>
                    <button onclick="runFBA()" class="btn btn-primary" style="font-size: 14px;">
                        ⚡ Run FBA Analysis
                    </button>
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
// Store FBA results history for comparison
let fbaHistory = [];

// Get FBA configuration from form
function getFBAConfig() {
    return {
        exchange_reaction: document.getElementById('exchangeReaction').value,
        lower_bound: parseFloat(document.getElementById('lowerBound').value),
        upper_bound: parseFloat(document.getElementById('upperBound').value),
        carbon_strategy: document.getElementById('carbonStrategy').value,
        objective: document.getElementById('objectiveFunction').value,
        solver: document.getElementById('solver').value,
        flux_threshold: parseFloat(document.getElementById('fluxThreshold').value),
        pfba: document.getElementById('pfba').checked,
        loopless: document.getElementById('loopless').checked
    };
}

async function runFBA() {
    const resultsDiv = document.getElementById('fbaResults');
    const config = getFBAConfig();

    resultsDiv.innerHTML = '<div class="loading">⏳ Running FBA analysis (may take 1-2 minutes)...</div>';

    try {
        const result = await apiCall('api/run_fba.php', config);

        if (result.success) {
            // Store in history
            fbaHistory.push({
                timestamp: new Date().toISOString(),
                config: config,
                result: result
            });

            // Enable comparison if multiple runs
            if (fbaHistory.length > 1) {
                document.getElementById('comparisonArea').style.display = 'block';
                showComparison();
            }
            const activeCount = result.fluxes.filter(f => Math.abs(f.flux) > 1e-6).length;
            const totalCount = result.fluxes.length;

            let alertClass = activeCount > 0 ? 'alert-success' : 'alert-danger';
            let statusIcon = activeCount > 0 ? '✅' : '❌';

            let html = `
                <div class="alert ${alertClass}">
                    ${statusIcon} <strong>FBA Completed:</strong> ${activeCount}/${totalCount} reactions with active flux
                </div>
            `;

            if (activeCount === 0) {
                html += `
                    <div class="alert alert-warning">
                        <strong>⚠️ WARNING:</strong> No reaction has active flux, even forcing butyrate uptake.
                        This suggests the selected pathway is <strong>NOT functionally complete</strong>.
                        <br><br>
                        <strong>Possible causes:</strong>
                        <ul style="margin-top: 10px; margin-left: 20px;">
                            <li>Missing one or more critical reactions (e.g., final thiolase: 3-ketobutanoyl-CoA → 2× acetyl-CoA)</li>
                            <li>Final product (acetyl-CoA) is not properly consumed in the model</li>
                            <li>There are gaps in the pathway (missing intermediate reactions)</li>
                        </ul>
                    </div>
                `;
            }

            html += `
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
                const isActive = Math.abs(f.flux) > 1e-6;
                const statusBadge = isActive ? '<span class="badge badge-success">✅ Active</span>' : '<span class="badge badge-danger">❌ Inactive</span>';

                html += `
                    <tr style="${!isActive ? 'opacity: 0.6;' : ''}">
                        <td><code style="font-size: 12px;">${f.reaction_id}</code></td>
                        <td>${f.flux.toFixed(6)}</td>
                        <td>${statusBadge}</td>
                    </tr>
                `;
            });

            html += `
                    </tbody>
                </table>
                <div style="margin-top: 30px; display: flex; gap: 12px; flex-wrap: wrap;">
                    <button class="btn btn-secondary" onclick="downloadReport()">
                        📥 Download Complete Report
                    </button>
                    <button class="btn btn-secondary" onclick="exportResultsJSON()">
                        📄 Export Results JSON
                    </button>
                    <button class="btn btn-secondary" onclick="exportConfigJSON()">
                        ⚙️ Export Config JSON
                    </button>
                    ${fbaHistory.length > 1 ? `
                        <button class="btn btn-secondary" onclick="showComparison()">
                            📊 Show Comparison
                        </button>
                    ` : ''}
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

function downloadReport() {
    window.location.href = 'api/download_report.php';
}

// Export current configuration as JSON
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

// Export FBA results with configuration as JSON
function exportResultsJSON() {
    if (fbaHistory.length === 0) {
        alert('No FBA results to export. Run FBA first.');
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

// Save preset to localStorage
function savePreset() {
    const presetName = prompt('Enter a name for this preset:');
    if (!presetName) return;

    const config = getFBAConfig();
    const presets = JSON.parse(localStorage.getItem('fba_presets') || '{}');
    presets[presetName] = config;
    localStorage.setItem('fba_presets', JSON.stringify(presets));

    alert(`Preset "${presetName}" saved successfully!`);
}

// Load preset from localStorage
function loadPreset() {
    const presets = JSON.parse(localStorage.getItem('fba_presets') || '{}');
    const presetNames = Object.keys(presets);

    if (presetNames.length === 0) {
        alert('No saved presets found. Save a preset first.');
        return;
    }

    const presetName = prompt(`Available presets:\n${presetNames.join('\n')}\n\nEnter preset name to load:`);
    if (!presetName || !presets[presetName]) {
        alert('Preset not found.');
        return;
    }

    const config = presets[presetName];

    // Apply configuration to form
    document.getElementById('exchangeReaction').value = config.exchange_reaction;
    document.getElementById('lowerBound').value = config.lower_bound;
    document.getElementById('upperBound').value = config.upper_bound;
    document.getElementById('carbonStrategy').value = config.carbon_strategy;
    document.getElementById('objectiveFunction').value = config.objective;
    document.getElementById('solver').value = config.solver;
    document.getElementById('fluxThreshold').value = config.flux_threshold;
    document.getElementById('pfba').checked = config.pfba;
    document.getElementById('loopless').checked = config.loopless;

    alert(`Preset "${presetName}" loaded successfully!`);
}

// Show comparison between multiple FBA runs
function showComparison() {
    if (fbaHistory.length < 2) {
        alert('Need at least 2 FBA runs to compare. Run FBA with different configurations.');
        return;
    }

    const comparisonArea = document.getElementById('comparisonArea');

    let html = `
        <div style="background: #f8f9fa; padding: 25px; border-radius: 8px;">
            <h3 style="color: #0f364c; margin-top: 0;">📊 Multi-Run Comparison</h3>
            <p style="color: #666; font-size: 14px;">Comparing ${fbaHistory.length} FBA runs</p>

            <div style="overflow-x: auto;">
                <table style="width: 100%; min-width: 800px;">
                    <thead>
                        <tr>
                            <th>Run</th>
                            <th>Timestamp</th>
                            <th>Exchange Rxn</th>
                            <th>Bounds</th>
                            <th>Strategy</th>
                            <th>Objective</th>
                            <th>Active Rxns</th>
                            <th>Obj Value</th>
                        </tr>
                    </thead>
                    <tbody>
    `;

    fbaHistory.forEach((run, index) => {
        const activeCount = run.result.fluxes.filter(f => Math.abs(f.flux) > 1e-6).length;
        html += `
            <tr>
                <td><strong>#${index + 1}</strong></td>
                <td style="font-size: 12px;">${new Date(run.timestamp).toLocaleString()}</td>
                <td><code>${run.config.exchange_reaction}</code></td>
                <td><code>[${run.config.lower_bound}, ${run.config.upper_bound}]</code></td>
                <td>${run.config.carbon_strategy}</td>
                <td>${run.config.objective}</td>
                <td><span class="badge ${activeCount > 0 ? 'badge-success' : 'badge-danger'}">${activeCount}/${run.result.fluxes.length}</span></td>
                <td>${run.result.objective_value.toFixed(4)}</td>
            </tr>
        `;
    });

    html += `
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 20px; display: flex; gap: 12px;">
                <button class="btn btn-secondary" onclick="exportComparisonCSV()">
                    📥 Export Comparison CSV
                </button>
                <button class="btn btn-secondary" onclick="clearHistory()">
                    🗑️ Clear History
                </button>
            </div>
        </div>
    `;

    comparisonArea.innerHTML = html;
}

// Export comparison as CSV
function exportComparisonCSV() {
    let csv = 'Run,Timestamp,Exchange Reaction,Lower Bound,Upper Bound,Carbon Strategy,Objective,Active Reactions,Total Reactions,Objective Value\n';

    fbaHistory.forEach((run, index) => {
        const activeCount = run.result.fluxes.filter(f => Math.abs(f.flux) > 1e-6).length;
        csv += `${index + 1},"${new Date(run.timestamp).toISOString()}",${run.config.exchange_reaction},${run.config.lower_bound},${run.config.upper_bound},${run.config.carbon_strategy},${run.config.objective},${activeCount},${run.result.fluxes.length},${run.result.objective_value}\n`;
    });

    const blob = new Blob([csv], {type: 'text/csv'});
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `fba_comparison_${new Date().toISOString().slice(0,10)}.csv`;
    link.click();
    URL.revokeObjectURL(url);
}

// Clear FBA history
function clearHistory() {
    if (confirm('Clear all FBA run history?')) {
        fbaHistory = [];
        document.getElementById('comparisonArea').style.display = 'none';
        alert('History cleared.');
    }
}

// Auto-run FBA on page load
window.addEventListener('DOMContentLoaded', function() {
    runFBA();
});
</script>
