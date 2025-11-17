#!/usr/bin/env python3
"""
Upload Human-GEM reactions to Google Sheets con interfaccia search per Daniela
"""

import gspread
from google.oauth2.credentials import Credentials
from google_auth_oauthlib.flow import InstalledAppFlow
from google.auth.transport.requests import Request
import pandas as pd
import cobra
from pathlib import Path
import pickle
import urllib.request

# Config
SCOPES = [
    'https://www.googleapis.com/auth/spreadsheets',
    'https://www.googleapis.com/auth/drive.file'
]
CREDS_DIR = Path.home() / '.config' / 'gcloud'
TOKEN_FILE = CREDS_DIR / 'sheets_token.pickle'
CREDENTIALS_FILE = CREDS_DIR / 'sheets_credentials.json'


def get_credentials():
    """Get Google Sheets OAuth2 credentials"""
    creds = None

    if TOKEN_FILE.exists():
        with open(TOKEN_FILE, 'rb') as token:
            creds = pickle.load(token)

    if not creds or not creds.valid:
        if creds and creds.expired and creds.refresh_token:
            print("🔄 Refresh token...")
            creds.refresh(Request())
        else:
            if not CREDENTIALS_FILE.exists():
                print(f"❌ Credenziali OAuth2 non trovate in: {CREDENTIALS_FILE}")
                print("\n📋 SETUP RICHIESTO:")
                print("1. Vai a https://console.cloud.google.com/")
                print("2. Crea progetto o usa esistente")
                print("3. Abilita Google Sheets API")
                print("4. Crea OAuth2 client ID (Desktop app)")
                print("5. Download JSON credentials")
                print(f"6. Salva in: {CREDENTIALS_FILE}")
                raise FileNotFoundError(f"Setup richiesto - vedi istruzioni sopra")

            print("🔐 Prima autenticazione - apro browser...")
            flow = InstalledAppFlow.from_client_secrets_file(
                CREDENTIALS_FILE, SCOPES)
            creds = flow.run_local_server(port=0)

        CREDS_DIR.mkdir(parents=True, exist_ok=True)
        with open(TOKEN_FILE, 'wb') as token:
            pickle.dump(creds, token)
        print("✅ Token salvato\n")

    return creds


def load_human_gem():
    """Download e carica Human-GEM"""
    data_dir = Path('data')
    data_dir.mkdir(exist_ok=True)
    model_path = data_dir / 'Human-GEM.xml'

    if not model_path.exists():
        print("📥 Download Human-GEM...")
        url = 'https://github.com/SysBioChalmers/Human-GEM/raw/main/model/Human-GEM.xml'
        urllib.request.urlretrieve(url, model_path)
        print(f"✓ Downloaded: {model_path.stat().st_size / 1e6:.1f} MB")

    print("📂 Carico modello...")
    model = cobra.io.read_sbml_model(str(model_path))
    print(f"✓ {len(model.reactions):,} reazioni caricate\n")

    return model


def create_reactions_dataframe(model):
    """Crea DataFrame con tutte le reazioni"""
    print("📊 Costruisco dataset...")

    data_rows = []
    for rxn in model.reactions:
        data_rows.append({
            'Reaction_ID': rxn.id,
            'Name': rxn.name,
            'Equation': rxn.build_reaction_string(),
            'GPR': rxn.gene_reaction_rule,
            'Lower_Bound': rxn.lower_bound,
            'Upper_Bound': rxn.upper_bound
        })

    df = pd.DataFrame(data_rows)
    print(f"✓ {len(df)} reazioni × {len(df.columns)} colonne\n")

    return df


def upload_to_sheets(gc, df, spreadsheet_id='1rc7xy-NyZ3UEn2Th_eXRkHbxfTagjeIWe8oIFGL-ep8'):
    """Upload a Google Sheets con 2 fogli: Database + Search"""

    print(f"📤 Apro spreadsheet esistente: {spreadsheet_id}")

    # Apri spreadsheet esistente
    sh = gc.open_by_key(spreadsheet_id)
    print(f"✓ Spreadsheet: {sh.title}\n")

    # Foglio 1: Database_Reactions
    print("📄 Foglio 1: Database_Reactions")

    # Crea o pulisci foglio
    try:
        sheet1 = sh.worksheet("Database_Reactions")
        sheet1.clear()
        print("  ✓ Foglio esistente pulito")
    except:
        sheet1 = sh.add_worksheet(title="Database_Reactions", rows=15000, cols=6)
        print("  ✓ Nuovo foglio creato")

    # Upload dati
    data = [df.columns.tolist()] + df.fillna('').values.tolist()
    sheet1.update(values=data, range_name='A1')

    # Formattazione header
    sheet1.format('A1:F1', {
        'textFormat': {'bold': True, 'fontSize': 11},
        'backgroundColor': {'red': 0.2, 'green': 0.4, 'blue': 0.7},
        'horizontalAlignment': 'CENTER'
    })
    sheet1.freeze(rows=1)

    print(f"  ✓ {len(df)} reazioni caricate\n")

    # Foglio 2: Search
    print("📄 Foglio 2: Search (interfaccia Daniela)")

    # Crea o pulisci foglio
    try:
        sheet2 = sh.worksheet("Search")
        sheet2.clear()
        print("  ✓ Foglio esistente pulito")
    except:
        sheet2 = sh.add_worksheet(title="Search", rows=1000, cols=10)
        print("  ✓ Nuovo foglio creato")

    # Setup interfaccia
    setup_data = [
        ['🔍 RICERCA REAZIONI HUMAN-GEM', '', '', '', '', ''],
        ['', '', '', '', '', ''],
        ['Keyword:', '', '', '', 'Totale:', ''],
        ['(formato: "but", "butyr")', '', '', '', '', ''],
        ['', '', '', '', '', ''],
        ['RISULTATI:', '', '', '', '', ''],
        ['Reaction_ID', 'Name', 'Equation', 'GPR', 'Lower_Bound', 'Upper_Bound']
    ]

    sheet2.update(values=setup_data, range_name='A1')

    # Formula conteggio risultati in F4
    count_formula = '=IF(ISBLANK(B4); ""; COUNTA(A8:A))'
    sheet2.update(values=[[count_formula]], range_name='F4', raw=False)

    # Formula FILTER con support multiple keyword (OR logic)
    # Rimuove virgolette e converte "but", "butyr" → but|butyr per OR matching
    filter_formula = '=FILTER(Database_Reactions!A:F; REGEXMATCH(LOWER(Database_Reactions!B:B); LOWER(REGEXREPLACE(REGEXREPLACE(B4; """"; ""); ",\\s*"; "|"))))'

    sheet2.update(values=[[filter_formula]], range_name='A8', raw=False)

    # Formattazione
    sheet2.format('A1:F1', {
        'textFormat': {'bold': True, 'fontSize': 16},
        'backgroundColor': {'red': 0.9, 'green': 0.9, 'blue': 1.0},
        'horizontalAlignment': 'CENTER'
    })

    sheet2.format('A3', {
        'textFormat': {'bold': True, 'fontSize': 12}
    })

    sheet2.format('B4', {
        'backgroundColor': {'red': 1.0, 'green': 1.0, 'blue': 0.8},
        'textFormat': {'fontSize': 12}
    })

    sheet2.format('A7:F7', {
        'textFormat': {'bold': True, 'fontSize': 11},
        'backgroundColor': {'red': 0.8, 'green': 0.8, 'blue': 0.8},
        'horizontalAlignment': 'CENTER'
    })

    # Freeze header risultati
    sheet2.freeze(rows=7)

    print(f"  ✓ Interfaccia configurata\n")

    return spreadsheet_id


def main():
    """Main workflow"""

    print("=" * 60)
    print("🚀 UPLOAD HUMAN-GEM → GOOGLE SHEETS")
    print("=" * 60)
    print()

    # Autenticazione
    try:
        creds = get_credentials()
        gc = gspread.authorize(creds)
        print("✅ Autenticato\n")
    except Exception as e:
        print(f"❌ Errore: {e}")
        return 1

    # Carica modello
    model = load_human_gem()

    # Crea DataFrame
    df = create_reactions_dataframe(model)

    # Upload
    try:
        spreadsheet_id = upload_to_sheets(gc, df)
    except Exception as e:
        print(f"❌ Errore upload: {e}")
        return 1

    print("=" * 60)
    print("✅ COMPLETATO")
    print("=" * 60)
    print(f"\n🔗 URL: https://docs.google.com/spreadsheets/d/{spreadsheet_id}/edit")
    print(f"\n📋 Istruzioni per Daniela:")
    print(f"   1. Apri link sopra")
    print(f"   2. Vai al foglio 'Search'")
    print(f"   3. Scrivi keyword in cella B4 (es. 'but')")
    print(f"   4. Risultati appaiono automaticamente sotto\n")

    return 0


if __name__ == '__main__':
    import sys
    sys.exit(main())
