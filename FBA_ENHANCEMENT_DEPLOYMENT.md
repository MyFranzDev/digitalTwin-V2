# FBA Configuration Enhancement - Deployment Guide

## Implementato

Nuove funzionalità FBA per Dr. Daniela Olivero con massima libertà configurazione parametri.

### 1. Form FBA Configurabile (Step 4)

**File modificato:** `web_app/steps/step4.php`

**Controlli aggiunti:**
- Exchange Reaction: MAR09809 (Butyrate), MAR09034 (Glucose), MAR09072 (Glutamine), MAR09079 (Acetate), MAR09135 (Propionate)
- Uptake Constraints: Lower Bound, Upper Bound (configurabili)
- Carbon Source Strategy: Strict (chiude altri uptake) / Permissive (mantiene medium aperto)
- Objective Function: Biomass Maximization / ATP Production (ATPM)
- Solver: GLPK (default) / CPLEX / Gurobi
- Flux Threshold: soglia per classificare reazioni active/inactive
- Advanced Options: pFBA (Parsimonious FBA), Loopless Solution

### 2. Backend API Aggiornata

**File modificato:** `web_app/api/run_fba.php`

Modifiche:
- Accetta configurazione FBA da POST request
- Passa configurazione a Python script
- Salva configurazione in database con risultati

### 3. Python Script Parametrizzato

**File modificato:** `web_app/scripts/fba_validator.py`

Modifiche:
- Accetta configurazione come secondo argomento command-line
- Applica exchange reaction configurabile
- Applica bounds configurabili
- Implementa carbon source strategy (strict/permissive)
- Supporta cambio objective function (biomass/ATPM)
- Supporta solver selection
- Supporta pFBA e loopless
- Ritorna configurazione usata nei risultati

### 4. Export & Riproducibilità

Funzioni JavaScript aggiunte:
- `exportConfigJSON()`: esporta solo configurazione corrente
- `exportResultsJSON()`: esporta risultati completi con config
- Timestamp ISO8601 nei filename

### 5. Sistema Presets

Funzioni JavaScript:
- `savePreset()`: salva configurazione con nome (localStorage)
- `loadPreset()`: carica preset salvato
- Gestione presets multipli

### 6. Confronto Multi-Run

Funzioni JavaScript:
- `showComparison()`: tabella comparativa automatica dopo 2+ run
- `exportComparisonCSV()`: export confronto come CSV
- `clearHistory()`: pulisce storico run
- Storia completa in `fbaHistory` array

## Database Migration

**File creati:**
- `web_app/schema_v2.sql` (aggiornato con fba_results table)
- `web_app/schema_fba_results.sql` (migration standalone)
- `web_app/scripts/migrate_fba_results.php` (script migration)

**Nuova tabella:** `fba_results`
```sql
CREATE TABLE fba_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id VARCHAR(255) NOT NULL,
    active_reactions INT,
    total_flux FLOAT,
    results_json LONGTEXT,
    config_json TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_session (session_id),
    KEY idx_created_at (created_at)
);
```

## Deployment Steps

### 1. Database Migration (OBBLIGATORIO)

Eseguire da server production o IP whitelisted:

```bash
# Opzione 1: MySQL client
mysql -h mysql.newrality.com -u digitaltwin -p digitaltwin < web_app/schema_fba_results.sql

# Opzione 2: PHP script
cd web_app
php scripts/migrate_fba_results.php
```

### 2. Upload Files

Caricare su server production:
- `web_app/steps/step4.php` (modificato)
- `web_app/api/run_fba.php` (modificato)
- `web_app/scripts/fba_validator.py` (modificato)
- `web_app/schema_v2.sql` (aggiornato)
- `web_app/schema_fba_results.sql` (nuovo)
- `web_app/scripts/migrate_fba_results.php` (nuovo)

### 3. Verifica Dipendenze Python

Sul server, verificare che COBRApy supporti le funzioni usate:

```bash
python3 -c "from cobra.flux_analysis import pfba; print('pFBA OK')"
```

## Testing Checklist

### Test Base
- [ ] Login web app funziona
- [ ] Step 4 carica correttamente
- [ ] Form FBA mostra tutti i controlli
- [ ] Valori default corretti (Butyrate, -1.0, 0, strict, biomass, glpk, 1e-6)

### Test Configurazione
- [ ] Cambio Exchange Reaction (provare Glucose)
- [ ] Cambio Bounds (provare -0.5, -0.1)
- [ ] Cambio Strategy (provare Permissive)
- [ ] Cambio Objective (provare ATPM)
- [ ] Checkbox pFBA
- [ ] Checkbox Loopless

### Test FBA Run
- [ ] Click "Run FBA Analysis" esegue correttamente
- [ ] Risultati mostrano fluxes
- [ ] Configurazione applicata è quella scelta

### Test Export
- [ ] "Export Config JSON" scarica JSON configurazione
- [ ] "Export Results JSON" scarica JSON completo
- [ ] JSON contiene tutti i parametri

### Test Presets
- [ ] "Save Preset" chiede nome e salva
- [ ] "Load Preset" mostra presets salvati
- [ ] Caricamento preset ripristina form correttamente

### Test Confronto
- [ ] Dopo 2+ run, appare "Comparison Area"
- [ ] Tabella confronto mostra tutte le run
- [ ] Export Comparison CSV funziona
- [ ] Clear History pulisce storico

## Known Issues / Limitazioni

1. **Loopless**: implementazione semplificata, richiede package aggiuntivi per full loopless
2. **CPLEX/Gurobi**: disponibili solo se installati sul server
3. **ATPM**: assume che reazione ATPM esista nel modello Human-GEM
4. **Database connection**: migration richiede accesso da IP whitelisted

## File Structure Summary

```
web_app/
├── steps/
│   └── step4.php (MODIFICATO - form + JavaScript completo)
├── api/
│   └── run_fba.php (MODIFICATO - accetta config, passa a Python)
├── scripts/
│   ├── fba_validator.py (MODIFICATO - parametrizzato)
│   └── migrate_fba_results.php (NUOVO - script migration)
├── schema_v2.sql (AGGIORNATO - include fba_results)
└── schema_fba_results.sql (NUOVO - migration standalone)
```

## Next Steps

1. ✅ Tutti i task implementati
2. ⏳ Eseguire migration database
3. ⏳ Upload files su production
4. ⏳ Testing completo
5. ⏳ Feedback Dr. Daniela Olivero

## Contact

Per problemi durante deployment o testing, verificare:
- Database connection (IP whitelisting)
- Python dependencies (COBRApy versione)
- File permissions (scripts/)
