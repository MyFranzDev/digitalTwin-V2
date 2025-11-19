<div class="step-container">
    <h1 class="step-title">📚 Biological Background</h1>
    <p class="step-subtitle">Butyrate metabolism in the β-oxidation pathway</p>

    <div style="margin-top: 30px;">
        <h3 style="color: #0f364c; margin-bottom: 15px;">🔬 Scientific Context</h3>
        <p style="line-height: 1.8; color: #555; margin-bottom: 20px;">
            <strong>Butyrate (butanoic acid, C₄H₈O₂)</strong> is a short-chain fatty acid (SCFA)
            produced by bacterial fermentation of dietary fibers in the colon.
            It represents the main energy source for colonocytes and plays critical roles in regulating
            intestinal homeostasis, energy metabolism, and immune response.
        </p>

        <h3 style="color: #0f364c; margin-bottom: 15px;">⚙️ Metabolic Pathway</h3>
        <p style="line-height: 1.8; color: #555; margin-bottom: 20px;">
            The butyrate degradation pathway follows these main phases:
        </p>

        <div style="max-width: 600px; margin: 0 auto 20px;">
            <!-- Phase 1: Transport -->
            <div style="background: #e3f2fd; padding: 18px; border-radius: 8px;">
                <div style="font-weight: 600; color: #0d47a1; margin-bottom: 8px; font-size: 14px;">1. Transport</div>
                <div style="color: #555; font-size: 13px; line-height: 1.6;">
                    Extracellular butyrate uptake via MCT (MonoCarboxylate Transporter) and SMCT transporters
                </div>
            </div>

            <!-- Arrow -->
            <div style="text-align: center; color: #0d47a1; font-size: 24px; margin: 10px 0;">↓</div>

            <!-- Phase 2: Activation -->
            <div style="background: #e3f2fd; padding: 18px; border-radius: 8px;">
                <div style="font-weight: 600; color: #0d47a1; margin-bottom: 8px; font-size: 14px;">2. Activation</div>
                <div style="color: #555; font-size: 13px; line-height: 1.6;">
                    Butyrate → butanoyl-CoA conversion (requires CoA + ATP)
                </div>
            </div>

            <!-- Arrow -->
            <div style="text-align: center; color: #0d47a1; font-size: 24px; margin: 10px 0;">↓</div>

            <!-- Phase 3: β-Oxidation -->
            <div style="background: #e3f2fd; padding: 18px; border-radius: 8px;">
                <div style="font-weight: 600; color: #0d47a1; margin-bottom: 8px; font-size: 14px;">3. β-Oxidation</div>
                <div style="color: #555; font-size: 13px; line-height: 1.6;">
                    Iterative oxidation cycle shortening the chain by 2 carbons per cycle:<br>
                    Butanoyl-CoA → 3-Hydroxybutanoyl-CoA → 3-Ketobutanoyl-CoA
                </div>
            </div>

            <!-- Arrow -->
            <div style="text-align: center; color: #0d47a1; font-size: 24px; margin: 10px 0;">↓</div>

            <!-- Phase 4: Final Product -->
            <div style="background: #e3f2fd; padding: 18px; border-radius: 8px;">
                <div style="font-weight: 600; color: #0d47a1; margin-bottom: 8px; font-size: 14px;">4. Final Product</div>
                <div style="color: #555; font-size: 13px; line-height: 1.6;">
                    2 molecules of <strong>acetyl-CoA</strong> entering the Krebs cycle for ATP production
                </div>
            </div>
        </div>

        <!-- Model Information -->
        <div style="margin-top: 50px; padding-top: 40px; border-top: 2px solid #e0e0e0;">
            <h3 style="color: #0f364c; margin-bottom: 15px;">ℹ️ Metabolic Model</h3>

            <?php
            // Get model info from database
            $pdo = getDbConnection();
            $stmt = $pdo->query("SELECT * FROM models WHERE id = 1");
            $model = $stmt->fetch();

            // Get stats from database
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM reactions WHERE model_id = 1");
            $reactions = $stmt->fetch()['count'];

            $stmt = $pdo->query("SELECT COUNT(*) as count FROM genes WHERE model_id = 1");
            $genes = $stmt->fetch()['count'];

            $stmt = $pdo->query("SELECT COUNT(*) as count FROM metabolites WHERE model_id = 1");
            $metabolites = $stmt->fetch()['count'];
            ?>

            <div class="alert alert-info" style="max-width: 700px; margin: 0 auto;">
                <h4 style="color: #0f364c; margin-top: 0; font-size: 16px;">
                    <?= htmlspecialchars($model['name']) ?> <?= htmlspecialchars($model['version']) ?>
                </h4>

                <div style="margin-top: 15px; font-size: 14px; line-height: 1.8;">
                    <strong>Model Statistics:</strong><br>
                    📊 <strong><?= number_format($reactions) ?></strong> reactions<br>
                    🧬 <strong><?= number_format($genes) ?></strong> genes<br>
                    🧪 <strong><?= number_format($metabolites) ?></strong> metabolites
                </div>

                <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid rgba(0,0,0,0.1);">
                    <strong>📖 Documentation:</strong><br>
                    <a href="<?= htmlspecialchars($model['github_url']) ?>" target="_blank" style="color: #0066cc; text-decoration: none;">
                        <?= htmlspecialchars($model['github_url']) ?> ↗
                    </a>
                </div>

                <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid rgba(0,0,0,0.1); color: #666; font-size: 13px;">
                    <strong>ℹ️ About Genome-scale Metabolic Models (GEMs):</strong><br>
                    GEMs represent the complete metabolic network of an organism, including all known biochemical reactions, metabolites, and gene-protein-reaction associations.
                    They are used for constraint-based modeling, flux balance analysis, and predicting metabolic phenotypes.
                </div>
            </div>
        </div>

        <div style="margin-top: 40px; text-align: right;">
            <a href="?step=1" class="btn btn-primary">Continue →</a>
        </div>
    </div>
</div>
