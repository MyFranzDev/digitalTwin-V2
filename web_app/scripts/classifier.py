#!/usr/bin/env python3
"""
Classify reactions by pathway phase
"""
import json
import sys
from cobra.io import load_model

def classify_reaction_phase(rxn):
    """Classify reaction into pathway phase"""
    name_lower = rxn.name.lower()
    rxn_id = rxn.id.lower()

    # Transport reactions
    if 'transport' in name_lower or 'exchange' in name_lower or 'uptake' in name_lower:
        return "TRASPORTO"

    # Activation (butanoate → butanoyl-CoA)
    if 'ligase' in name_lower and 'butanoate' in name_lower:
        return "ATTIVAZIONE"
    if 'synthetase' in name_lower and 'butyr' in name_lower:
        return "ATTIVAZIONE"

    # β-Oxidation
    if any(x in name_lower for x in ['oxidoreductase', 'dehydrogenase', 'hydro-lyase', 'enoyl']):
        if any(x in name_lower for x in ['butanoyl', 'hydroxybutanoyl', 'butyryl', 'croton']):
            return "β-OSSIDAZIONE"

    # Hydrolysis (optional)
    if 'hydrolase' in name_lower or 'thioesterase' in name_lower:
        return "ALTRO"

    return "ALTRO"

def main():
    if len(sys.argv) < 2:
        print(json.dumps({"success": False, "error": "Reaction IDs not provided"}))
        sys.exit(1)

    reaction_ids_json = sys.argv[1]

    try:
        reaction_ids = json.loads(reaction_ids_json)

        # Load model
        model = load_model("Human-GEM")

        # Classify each reaction
        classified = []
        for rxn_id in reaction_ids:
            try:
                rxn = model.reactions.get_by_id(rxn_id)
                phase = classify_reaction_phase(rxn)

                classified.append({
                    "id": rxn.id,
                    "name": rxn.name,
                    "phase": phase,
                    "subsystem": rxn.subsystem if rxn.subsystem else ""
                })
            except KeyError:
                continue

        output = {
            "success": True,
            "classified": classified
        }

        print(json.dumps(output))

    except Exception as e:
        print(json.dumps({"success": False, "error": str(e)}))
        sys.exit(1)

if __name__ == "__main__":
    main()
