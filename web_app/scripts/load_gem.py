#!/usr/bin/env python3
"""
Load metabolic model and output statistics + preview
"""
import json
import sys
import urllib.request
import pickle
from pathlib import Path
from cobra.io import read_sbml_model

def main():
    # Get URL and pagination params from arguments
    url = sys.argv[1] if len(sys.argv) > 1 else 'https://github.com/SysBioChalmers/Human-GEM/raw/main/model/Human-GEM.xml'
    offset = int(sys.argv[2]) if len(sys.argv) > 2 else 0
    limit = int(sys.argv[3]) if len(sys.argv) > 3 else 50

    try:
        # Setup data directory (permanent path)
        script_dir = Path(__file__).parent.parent
        data_dir = script_dir / 'data' / 'models'
        data_dir.mkdir(parents=True, exist_ok=True)

        # Extract filename from URL
        filename = url.split('/')[-1]
        model_path = data_dir / filename
        pickle_path = data_dir / (filename.replace('.xml', '.pkl'))

        # Download model if not exists
        if not model_path.exists():
            print(f"📥 Downloading from {url}...", file=sys.stderr)
            urllib.request.urlretrieve(url, model_path)
            print(f"✓ Downloaded: {model_path.stat().st_size / 1e6:.1f} MB", file=sys.stderr)
        else:
            print(f"✓ Using cached model: {model_path}", file=sys.stderr)

        # Load model from pickle cache if exists (much faster)
        if pickle_path.exists():
            print(f"⚡ Loading from pickle cache...", file=sys.stderr)
            with open(pickle_path, 'rb') as f:
                model = pickle.load(f)
            print(f"✓ Loaded from cache in seconds", file=sys.stderr)
        else:
            # Load model from XML file (slow)
            print(f"📖 Loading model from XML (first time, ~1-2 min)...", file=sys.stderr)
            model = read_sbml_model(str(model_path))

            # Save to pickle for future fast loading
            print(f"💾 Saving to pickle cache...", file=sys.stderr)
            with open(pickle_path, 'wb') as f:
                pickle.dump(model, f)
            print(f"✓ Cache saved: {pickle_path.stat().st_size / 1e6:.1f} MB", file=sys.stderr)

        # Get statistics
        stats = {
            "reactions": len(model.reactions),
            "metabolites": len(model.metabolites),
            "genes": len(model.genes)
        }

        # Get reactions with pagination
        all_reactions = list(model.reactions)
        total_reactions = len(all_reactions)
        paginated_reactions = all_reactions[offset:offset+limit]

        preview = []
        for rxn in paginated_reactions:
            preview.append({
                "id": rxn.id,
                "name": rxn.name,
                "subsystem": rxn.subsystem if rxn.subsystem else "N/A",
                "reaction": rxn.reaction,
                "gene_reaction_rule": rxn.gene_reaction_rule,
                "lower_bound": rxn.lower_bound,
                "upper_bound": rxn.upper_bound,
                "reversibility": rxn.reversibility,
                "metabolites": {m.id: coeff for m, coeff in rxn.metabolites.items()},
                "genes": [g.id for g in rxn.genes],
                "annotation": dict(rxn.annotation) if hasattr(rxn, 'annotation') else {},
                "notes": dict(rxn.notes) if hasattr(rxn, 'notes') else {}
            })

        # Output JSON
        result = {
            "success": True,
            "stats": stats,
            "preview": preview,
            "pagination": {
                "offset": offset,
                "limit": limit,
                "total": total_reactions,
                "current_count": len(preview)
            }
        }

        print(json.dumps(result))

    except Exception as e:
        result = {
            "success": False,
            "error": str(e)
        }
        print(json.dumps(result))
        sys.exit(1)

if __name__ == "__main__":
    main()
