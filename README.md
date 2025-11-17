# Digital Twin - Butyrate Pathway Extraction

Progetto di ricerca per l'estrazione e la caninizzazione del pathway metabolico del butirrato dal modello genome-scale Human-GEM.

## Obiettivo

Creare un modello metabolico canino specifico per il metabolismo energetico dei colonociti, partendo dal metabolismo del butirrato nel modello umano Human-GEM.

## Background Biologico - Pathway Butirrato

**Dal cibo all'energia cellulare:**

Il butirrato è un acido grasso a catena corta (SCFA) fondamentale per l'energia dei colonociti. Fornisce circa il 70% dell'energia necessaria a queste cellule.

### Fasi del pathway:

1. **Produzione nel lume intestinale**
   - Le fibre alimentari vengono fermentate dai batteri intestinali (microbiota)
   - Questo processo produce butirrato come metabolita principale

2. **Trasporto nella cellula**
   - Il butirrato attraversa la membrana cellulare del colonocita
   - Passa dal lume intestinale al citoplasma della cellula tramite trasportatori specifici (MCT1, SMCT1/2)

3. **Attivazione metabolica**
   - Il butirrato viene convertito in butirril-CoA
   - Questa forma attivata può entrare nel ciclo di β-ossidazione

4. **β-Ossidazione**
   - Il butirril-CoA viene progressivamente degradato
   - Ogni ciclo rimuove 2 atomi di carbonio producendo acetil-CoA
   - L'acetil-CoA entra nel ciclo di Krebs

5. **Produzione energia**
   - Il ciclo di Krebs e la fosforilazione ossidativa producono ATP
   - Questo ATP fornisce l'energia necessaria per le funzioni cellulari dei colonociti

## Workflow

Il progetto segue un approccio incrementale diviso in fasi:

1. **Estrazione reazioni** - Identificazione delle reazioni metaboliche rilevanti dal modello Human-GEM
2. **Estrazione GPR e geni** - Identificazione dei geni umani (ENSG IDs) associati alle reazioni
3. **Mapping ortologhi** - Conversione geni umani → geni canini tramite BioMart
4. **Creazione sub-modello** - Costruzione modello metabolico specifico per il pathway
5. **Validazione** - Verifica completezza pathway e analisi flussi metabolici (FBA)

## Setup

```bash
# Crea virtual environment
python3 -m venv venv
source venv/bin/activate

# Installa dipendenze
pip install -r requirements.txt

# Avvia notebook
jupyter lab butyrate_pathway_extraction.ipynb
```

## Tecnologie

- **COBRApy**: Constraint-Based Reconstruction and Analysis per modelli metabolici
- **Human-GEM**: Genome-scale metabolic model umano (~13k reazioni, ~2.9k geni)
- **Jupyter**: Notebook interattivi per analisi e documentazione
- **BioMart**: Database per mapping geni ortologhi tra specie

## Note

Il progetto procede per piccoli step validati progressivamente. Ogni fase viene completata e verificata prima di passare alla successiva.
