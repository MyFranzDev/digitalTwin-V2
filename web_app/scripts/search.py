#!/usr/bin/env python3
"""
Search reactions in Human-GEM by query
"""
import json
import sys
from cobra.io import load_model

def main():
    if len(sys.argv) < 2:
        print(json.dumps({"success": False, "error": "Query not provided"}))
        sys.exit(1)

    query = sys.argv[1].lower()

    try:
        # Load model
        model = load_model("Human-GEM")

        # Search reactions
        results = []
        for rxn in model.reactions:
            # Search in ID, name, and equation
            if (query in rxn.id.lower() or
                query in rxn.name.lower() or
                query in rxn.reaction.lower()):
                results.append({
                    "id": rxn.id,
                    "name": rxn.name,
                    "equation": rxn.reaction,
                    "subsystem": rxn.subsystem if rxn.subsystem else ""
                })

        # Output JSON
        output = {
            "success": True,
            "reactions": results[:100]  # Limit to 100 results
        }

        print(json.dumps(output))

    except Exception as e:
        print(json.dumps({"success": False, "error": str(e)}))
        sys.exit(1)

if __name__ == "__main__":
    main()
