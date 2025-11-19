#!/usr/bin/env python3
"""
Extract ENSG IDs from reaction GPR rules
"""
import json
import sys
import re
from cobra.io import load_model

def extract_ensg_from_gpr(rxn):
    """Extract all ENSG IDs from GPR rule"""
    gpr = rxn.gene_reaction_rule
    if not gpr:
        return []

    ensg_pattern = r'ENSG\d{11}'
    ensg_list = re.findall(ensg_pattern, gpr)
    return sorted(list(set(ensg_list)))

def main():
    if len(sys.argv) < 2:
        print(json.dumps({"success": False, "error": "Reaction IDs not provided"}))
        sys.exit(1)

    reaction_ids_json = sys.argv[1]

    try:
        reaction_ids = json.loads(reaction_ids_json)

        # Load model
        model = load_model("Human-GEM")

        # Extract ENSG from each reaction
        reactions_ensg = []
        all_ensg = set()

        for rxn_id in reaction_ids:
            try:
                rxn = model.reactions.get_by_id(rxn_id)
                ensg_list = extract_ensg_from_gpr(rxn)

                reactions_ensg.append({
                    "id": rxn.id,
                    "name": rxn.name,
                    "ensg_list": ensg_list
                })

                all_ensg.update(ensg_list)
            except KeyError:
                continue

        output = {
            "success": True,
            "reactions_ensg": reactions_ensg,
            "unique_ensg": sorted(list(all_ensg)),
            "total_reactions": len(reactions_ensg)
        }

        print(json.dumps(output))

    except Exception as e:
        print(json.dumps({"success": False, "error": str(e)}))
        sys.exit(1)

if __name__ == "__main__":
    main()
