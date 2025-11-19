# Email Draft per Daniela

**Oggetto:** Interfaccia web interattiva pathway butirrato - Problema critico identificato con FBA

---

Ciao Daniela,

Ho completato l'interfaccia web interattiva per esplorare insieme il pathway del butirrato. Puoi accedervi subito:

🔗 **URL:** https://newrality.com/demo/digitaltwin/
🔑 **Password:** `digitaltwin2025`

## Cosa trovi nell'interfaccia

L'app replica il lavoro del notebook Jupyter ma in formato web interattivo, con 6 step:

**Step 0 - Background:**
Descrizione biologica del pathway butirrato → acetil-CoA (β-ossidazione)

**Step 1 - Setup Human-GEM:**
Download del modello metabolico Human-GEM (12,971 reazioni, 8,455 metaboliti, 2,887 geni)

**Step 2 - Selezione Reazioni:**
Ricerca interattiva delle reazioni del pathway. Puoi:
- Cercare per keyword (ID, nome, descrizione)
- Aggiungere reazioni al pathway
- Rimuovere reazioni dalla selezione
- Le tue selezioni rimangono salvate nella sessione

**Step 3 - Classificazione Fasi:**
Classifica automatica delle reazioni in:
- TRASPORTO (uptake butirrato)
- ATTIVAZIONE (butirrato → butanoyl-CoA)
- β-OSSIDAZIONE (degradazione catena)
- ALTRO (opzionali/accessorie)

**Step 4 - Estrazione ENSG:**
Estrae i 21 geni umani (ENSG IDs) dalle reazioni selezionate. Puoi scaricare la lista in CSV.

**Step 5 - FBA Validation:** ⚠️
Valida la completezza funzionale del pathway con Flux Balance Analysis.

---

## ⚠️ PROBLEMA CRITICO IDENTIFICATO

Ho eseguito la FBA validation sulle 12 reazioni che mi hai indicato e ho trovato un **problema importante**:

**Risultato:** 0/12 reazioni con flusso attivo (anche forzando l'uptake di SOLO butirrato)

Questo significa che le 12 reazioni **NON formano un pathway funzionalmente completo** nel modello.

### Possibili cause:
1. **Manca una o più reazioni critiche** (es. tiolasi finale: 3-ketobutanoyl-CoA → 2× acetil-CoA)
2. Il prodotto finale (acetil-CoA) non viene consumato correttamente nel modello
3. Ci sono gap nel pathway (reazioni intermedie mancanti)

---

## Domande per te

Prima di procedere con la fase 2 (mapping human→dog ortholog), vorrei capire:

1. **Le 12 reazioni dovrebbero essere funzionali standalone?**
   O si appoggiano ad altre reazioni generiche del modello Human-GEM per completare il pathway?

2. **Manca la tiolasi finale?**
   Dovrebbe esserci una reazione che converte 3-ketobutanoyl-CoA → 2× acetil-CoA?

3. **Qual è il prodotto finale atteso?**
   È acetil-CoA oppure c'è un altro prodotto terminale che dovremmo considerare?

---

## Prossimi passi

Puoi:
1. **Esplorare l'interfaccia** e provare a cercare/selezionare altre reazioni
2. **Vedere i risultati FBA** nello Step 5 che evidenziano il problema
3. **Scaricare i CSV** (ENSG list + report completo) dallo Step 4 e Step 5
4. **Dirmi cosa ne pensi** e se vuoi che aggiungiamo altre reazioni al pathway

Una volta che abbiamo un pathway completo e validato, possiamo procedere con:
- Mapping ENSG human → ENSCAFG dog (BioMart)
- Creazione sub-model canino
- Validazione finale con FBA

---

## Note Tecniche

L'interfaccia è stata sviluppata con:
- Frontend: PHP + HTML/CSS/JavaScript
- Backend: Python 3.10 + COBRApy 0.30.0
- Database: MySQL (salva le tue sessioni/selezioni)
- Deployed su: DreamHost (server Newrality)

Tutto il codice è trasparente e puoi vedere esattamente cosa fa ogni step. Niente "magia AI" nascosta - solo analisi metabolica standard con COBRApy.

---

Fammi sapere quando hai tempo di provare l'interfaccia e ne parliamo!

Francesco

---

**P.S.** Se hai domande o problemi ad accedere, scrivimi subito.

**P.P.S.** L'interfaccia salva automaticamente le tue selezioni, quindi puoi fermarti e riprendere quando vuoi.
