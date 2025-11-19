# Butyrate Pathway Explorer

Interfaccia web interattiva per l'analisi del pathway metabolico butirrato → acetil-CoA usando Human-GEM.

## 🌐 Accesso

**URL:** https://newrality.com/demo/digitaltwin/
**Password:** `digitaltwin2025`

## 📋 Features

- **Step 0 - Background:** Descrizione biologica del pathway butirrato
- **Step 1 - Setup:** Download e caricamento Human-GEM (12,971 reazioni)
- **Step 2 - Selezione:** Ricerca e selezione reazioni (search interattivo)
- **Step 3 - Classificazione:** Classificazione per fase (Trasporto/Attivazione/β-Ossidazione)
- **Step 4 - ENSG:** Estrazione geni umani dalle reazioni selezionate
- **Step 5 - FBA:** Validazione funzionale pathway con Flux Balance Analysis

## 🔧 Stack Tecnologico

### Frontend
- PHP 8.x (session management + routing)
- HTML/CSS/JavaScript (UI moderna responsive)
- MySQL (persistenza dati sessioni)

### Backend
- Python 3.10.12 (DreamHost)
- COBRApy 0.30.0 (metabolic modeling)
- Pandas 2.3.3 (data manipulation)
- NumPy 2.2.6 (calcoli numerici)

### Pattern Architetturale
- **PHP Frontend:** Gestisce UI, autenticazione, routing
- **Python Backend:** Esegue analisi metaboliche (load GEM, FBA, etc.)
- **API Bridge:** PHP chiama Python via `exec()`, riceve JSON output
- **Session + DB:** Persistenza dati utente in MySQL

## 📁 Struttura Progetto

```
/home/newrality/newrality.com/demo/digitaltwin/
├── index.php              # Login page
├── dashboard.php          # Main dashboard con sidebar
├── logout.php
├── config.php             # DB credentials + constants
├── styles.css             # UI styling
├── script.js              # Frontend interactions
├── schema.sql             # Database schema
├── steps/
│   ├── step0.php         # Background biologico
│   ├── step1.php         # Setup Human-GEM
│   ├── step2.php         # Selezione reazioni
│   ├── step3.php         # Classificazione fasi
│   ├── step4.php         # Estrazione ENSG
│   └── step5.php         # FBA validation
├── api/
│   ├── load_gem.php      # Carica Human-GEM
│   ├── search_reactions.php # Cerca reazioni
│   ├── add_reaction.php  # Aggiungi a selezione
│   ├── remove_reaction.php # Rimuovi da selezione
│   ├── classify.php      # Classifica per fase
│   ├── extract_ensg.php  # Estrai ENSG IDs
│   ├── run_fba.php       # Esegui FBA
│   ├── download_ensg.php # Download CSV ENSG
│   └── download_report.php # Download report completo
├── scripts/
│   ├── load_gem.py       # Download Human-GEM
│   ├── search.py         # Search engine reazioni
│   ├── classifier.py     # Classificazione fasi
│   ├── ensg_extractor.py # Estrazione ENSG
│   ├── fba_validator.py  # FBA validation
│   └── requirements.txt
└── assets/
    ├── 3070961.jpg       # Background image
    └── newrality.png     # Logo Newrality
```

## 🗄️ Database Schema

```sql
sessions (
  id INT PRIMARY KEY,
  session_id VARCHAR(64) UNIQUE,
  created_at TIMESTAMP,
  last_activity TIMESTAMP
)

selected_reactions (
  id INT PRIMARY KEY,
  session_id VARCHAR(64) FK,
  reaction_id VARCHAR(32),
  reaction_name TEXT,
  subsystem VARCHAR(255),
  phase VARCHAR(32),
  ensg_list TEXT,
  selected_at TIMESTAMP
)

fba_results (
  id INT PRIMARY KEY,
  session_id VARCHAR(64) FK,
  run_at TIMESTAMP,
  active_reactions INT,
  total_flux DECIMAL(10,4),
  results_json TEXT
)
```

## 🚀 Workflow Utente

1. **Login** con password condivisa
2. **Step 0:** Leggi background biologico
3. **Step 1:** Carica Human-GEM (attendi 1-2 min)
4. **Step 2:** Cerca reazioni (es. "butyrate") e aggiungi alla selezione
5. **Step 3:** Classifica reazioni per fase metabolica
6. **Step 4:** Estrai ENSG IDs dai GPR rules
7. **Step 5:** Esegui FBA validation → visualizza flussi (evidenzia problema 0/12 attive)
8. **Download:** Export CSV risultati

## ⚙️ Deploy / Update

### Update Codice
```bash
# Da locale
cd /Users/francesco/newrality/digitalTwin-V2
sshpass -p 'touchlabs2' rsync -avz web_app/ newrality@iad1-shared-e1-31.dreamhost.com:/home/newrality/newrality.com/demo/digitaltwin/
```

### Update Schema Database
```bash
# Da DreamHost SSH
cd /home/newrality/newrality.com/demo/digitaltwin
mysql -h mysql.newrality.com -u digitaltwin -p'touchlabs2' digitaltwin < schema.sql
```

### Rendi Eseguibili Script Python
```bash
chmod +x scripts/*.py
```

## 🧪 Test Locali

### Requisiti
- PHP 8.x con MySQLi/PDO
- Python 3.10+ con pip
- MySQL 8.x

### Setup
```bash
# Install Python dependencies
cd web_app/scripts
pip install -r requirements.txt

# Create database schema
mysql -h localhost -u root -p < schema.sql

# Update config.php with local credentials
# Run PHP server
php -S localhost:8000
```

## 📊 Risultati Attesi

### FBA Validation (Step 5)
- **Problema noto:** 0/12 reazioni con flusso attivo
- **Causa:** Pathway NON funzionalmente completo
- **Ipotesi:** Manca tiolasi finale (3-ketobutanoyl-CoA → 2× acetil-CoA)

### ENSG Extraction (Step 4)
- **21 geni unici** identificati dalle 12 reazioni core
- Pronti per mapping human→dog ortholog via BioMart

## 👥 Utilizzo con Daniela

1. Invia link: https://newrality.com/demo/digitaltwin/
2. Condividi password: `digitaltwin2025`
3. Daniela può:
   - Esplorare reazioni interattivamente
   - Selezionare/rimuovere reazioni dal pathway
   - Visualizzare classificazione fasi
   - Vedere risultati FBA e gap pathway
   - Scaricare CSV risultati

## 📧 Note per Email Daniela

```
Ciao Daniela,

Ho creato un'interfaccia web interattiva per esplorare insieme il pathway butirrato:

🔗 URL: https://newrality.com/demo/digitaltwin/
🔑 Password: digitaltwin2025

Cosa puoi fare:
- Step 1: Caricare il modello Human-GEM
- Step 2: Cercare e selezionare reazioni del pathway
- Step 3: Vedere la classificazione in fasi (Trasporto/Attivazione/β-Ossidazione)
- Step 4: Estrarre i geni ENSG
- Step 5: Validare il pathway con FBA

⚠️ Problema identificato nello Step 5:
Tutte le 12 reazioni hanno flusso=0 anche forzando l'uptake di butirrato.
Questo suggerisce che il pathway NON è completo.

Domande per te:
1. Le 12 reazioni dovrebbero essere funzionali standalone?
2. Manca la tiolasi finale (3-ketobutanoyl-CoA → 2× acetil-CoA)?
3. Il prodotto finale atteso è acetil-CoA o altro?

Prova l'interfaccia e dimmi cosa ne pensi!
```

## 🛠️ Troubleshooting

### Python non trova COBRApy
```bash
# Reinstall in user space
python3 -m pip install --user cobra pandas numpy
```

### Database connection error
```php
// Check config.php credentials
define('DB_HOST', 'mysql.newrality.com');  // NOT 'localhost'
define('DB_NAME', 'digitaltwin');
define('DB_USER', 'digitaltwin');
define('DB_PASS', 'touchlabs2');
```

### Python script timeout
```php
// Increase timeout in api/*.php
set_time_limit(300);  // 5 minutes
```

## 📝 Changelog

### 2025-11-18 - Initial Release
- Implemented full stack PHP + Python + MySQL
- Deployed to DreamHost
- 6 steps workflow (0-5)
- 9 API endpoints
- 5 Python analysis scripts
- MySQL persistence layer
- Interactive reaction search & selection
- FBA validation with problem detection
