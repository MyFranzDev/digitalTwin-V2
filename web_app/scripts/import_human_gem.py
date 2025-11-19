#!/usr/bin/env python3
"""
Import Human-GEM model into MySQL database
Reads from pickle cache for speed, inserts into relational tables
"""

import pickle
import json
import sys
from pathlib import Path
import pymysql

def connect_db():
    """Connect to MySQL database"""
    return pymysql.connect(
        host='mysql.newrality.com',
        user='digitaltwin',
        password='touchlabs2',
        database='digitaltwin',
        charset='utf8mb4',
        cursorclass=pymysql.cursors.DictCursor
    )

def main():
    print("🚀 Starting Human-GEM import...")

    # Load model from pickle
    script_dir = Path(__file__).parent.parent
    pickle_path = script_dir / 'data' / 'models' / 'Human-GEM.pkl'

    if not pickle_path.exists():
        print(f"❌ Pickle file not found: {pickle_path}")
        print("   Run load_gem.py first to create pickle cache")
        sys.exit(1)

    print(f"📖 Loading model from {pickle_path}...")
    with open(pickle_path, 'rb') as f:
        model = pickle.load(f)

    print(f"✅ Model loaded: {len(model.reactions)} reactions, {len(model.genes)} genes, {len(model.metabolites)} metabolites")

    # Connect to database
    print("🔌 Connecting to database...")
    conn = connect_db()
    cursor = conn.cursor()

    model_id = 1  # Human-GEM v1.15.0

    try:
        # Import reactions
        print(f"\n📊 Importing {len(model.reactions)} reactions...")
        reaction_count = 0
        for rxn in model.reactions:
            cursor.execute("""
                INSERT INTO reactions (id, model_id, name, subsystem, formula, reversibility, lower_bound, upper_bound)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
                ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    subsystem = VALUES(subsystem),
                    formula = VALUES(formula),
                    reversibility = VALUES(reversibility),
                    lower_bound = VALUES(lower_bound),
                    upper_bound = VALUES(upper_bound)
            """, (
                rxn.id,
                model_id,
                rxn.name,
                rxn.subsystem if rxn.subsystem else None,
                rxn.reaction,
                rxn.reversibility,
                rxn.lower_bound,
                rxn.upper_bound
            ))
            reaction_count += 1
            if reaction_count % 1000 == 0:
                print(f"   Progress: {reaction_count}/{len(model.reactions)} reactions...")
                conn.commit()

        conn.commit()
        print(f"✅ Imported {reaction_count} reactions")

        # Import genes
        print(f"\n🧬 Importing {len(model.genes)} genes...")
        gene_count = 0
        for gene in model.genes:
            cursor.execute("""
                INSERT INTO genes (id, model_id)
                VALUES (%s, %s)
                ON DUPLICATE KEY UPDATE id = id
            """, (gene.id, model_id))
            gene_count += 1
            if gene_count % 500 == 0:
                print(f"   Progress: {gene_count}/{len(model.genes)} genes...")
                conn.commit()

        conn.commit()
        print(f"✅ Imported {gene_count} genes")

        # Import metabolites
        print(f"\n🧪 Importing {len(model.metabolites)} metabolites...")
        met_count = 0
        for met in model.metabolites:
            cursor.execute("""
                INSERT INTO metabolites (id, model_id, name)
                VALUES (%s, %s, %s)
                ON DUPLICATE KEY UPDATE name = VALUES(name)
            """, (met.id, model_id, met.name))
            met_count += 1
            if met_count % 1000 == 0:
                print(f"   Progress: {met_count}/{len(model.metabolites)} metabolites...")
                conn.commit()

        conn.commit()
        print(f"✅ Imported {met_count} metabolites")

        # Import reaction-gene relationships
        print(f"\n🔗 Importing reaction-gene relationships...")
        rg_count = 0
        for rxn in model.reactions:
            gpr_rule = rxn.gene_reaction_rule if rxn.gene_reaction_rule else None
            for gene in rxn.genes:
                cursor.execute("""
                    INSERT INTO reaction_genes (reaction_id, gene_id, model_id, gpr_rule)
                    VALUES (%s, %s, %s, %s)
                    ON DUPLICATE KEY UPDATE gpr_rule = VALUES(gpr_rule)
                """, (rxn.id, gene.id, model_id, gpr_rule))
                rg_count += 1

            if rg_count % 1000 == 0:
                print(f"   Progress: {rg_count} relationships...")
                conn.commit()

        conn.commit()
        print(f"✅ Imported {rg_count} reaction-gene relationships")

        # Import reaction-metabolite relationships
        print(f"\n🔗 Importing reaction-metabolite relationships...")
        rm_count = 0
        for rxn in model.reactions:
            for met, coeff in rxn.metabolites.items():
                cursor.execute("""
                    INSERT INTO reaction_metabolites (reaction_id, metabolite_id, model_id, coefficient)
                    VALUES (%s, %s, %s, %s)
                    ON DUPLICATE KEY UPDATE coefficient = VALUES(coefficient)
                """, (rxn.id, met.id, model_id, float(coeff)))
                rm_count += 1

            if rm_count % 1000 == 0:
                print(f"   Progress: {rm_count} relationships...")
                conn.commit()

        conn.commit()
        print(f"✅ Imported {rm_count} reaction-metabolite relationships")

        # Import annotations
        print(f"\n📝 Importing annotations...")
        ann_count = 0
        for rxn in model.reactions:
            if hasattr(rxn, 'annotation') and rxn.annotation:
                for db_name, db_value in rxn.annotation.items():
                    # Convert lists to JSON
                    if isinstance(db_value, list):
                        db_value_str = json.dumps(db_value)
                    else:
                        db_value_str = str(db_value)

                    cursor.execute("""
                        INSERT INTO reaction_annotations (reaction_id, model_id, db_name, db_value)
                        VALUES (%s, %s, %s, %s)
                        ON DUPLICATE KEY UPDATE db_value = VALUES(db_value)
                    """, (rxn.id, model_id, db_name, db_value_str))
                    ann_count += 1

            if ann_count % 1000 == 0:
                print(f"   Progress: {ann_count} annotations...")
                conn.commit()

        conn.commit()
        print(f"✅ Imported {ann_count} annotations")

        # Import notes
        print(f"\n📌 Importing notes...")
        note_count = 0
        for rxn in model.reactions:
            if hasattr(rxn, 'notes') and rxn.notes:
                for note_key, note_value in rxn.notes.items():
                    cursor.execute("""
                        INSERT INTO reaction_notes (reaction_id, model_id, note_key, note_value)
                        VALUES (%s, %s, %s, %s)
                        ON DUPLICATE KEY UPDATE note_value = VALUES(note_value)
                    """, (rxn.id, model_id, note_key, str(note_value)))
                    note_count += 1

            if note_count % 1000 == 0:
                print(f"   Progress: {note_count} notes...")
                conn.commit()

        conn.commit()
        print(f"✅ Imported {note_count} notes")

        print("\n" + "="*60)
        print("✅ IMPORT COMPLETED SUCCESSFULLY!")
        print("="*60)
        print(f"📊 {reaction_count} reactions")
        print(f"🧬 {gene_count} genes")
        print(f"🧪 {met_count} metabolites")
        print(f"🔗 {rg_count} reaction-gene links")
        print(f"🔗 {rm_count} reaction-metabolite links")
        print(f"📝 {ann_count} annotations")
        print(f"📌 {note_count} notes")
        print("="*60)

    except Exception as e:
        print(f"\n❌ Error during import: {e}")
        conn.rollback()
        raise
    finally:
        cursor.close()
        conn.close()

if __name__ == "__main__":
    main()
