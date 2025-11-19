#!/usr/bin/env python3
"""
Advanced FBA validator with full configurability
"""
import json
import sys
import pickle
from pathlib import Path

def main():
    if len(sys.argv) < 2:
        print(json.dumps({"success": False, "error": "Reaction IDs not provided"}))
        sys.exit(1)

    reaction_ids_json = sys.argv[1]

    # Get configuration (optional second argument)
    config = {}
    if len(sys.argv) >= 3:
        config_json = sys.argv[2]
        config = json.loads(config_json)

    try:
        reaction_ids = json.loads(reaction_ids_json)

        # Extract configuration with defaults
        exchange_reaction_id = config.get('exchange_reaction', 'MAR09809')
        lower_bound = float(config.get('lower_bound', -1.0))
        upper_bound = float(config.get('upper_bound', 0))
        objective_id = config.get('objective', 'biomass')
        solver_name = config.get('solver', 'glpk')
        flux_threshold = float(config.get('flux_threshold', 1e-6))
        use_pfba = config.get('pfba', False)
        use_loopless = config.get('loopless', False)

        # Advanced parameters
        pathway_bounds = config.get('pathway_bounds', {})  # {rxn_id: {lower, upper}}
        medium_exchanges = config.get('medium_exchanges', [])  # [{id, lower, upper, active}]
        custom_constraints = config.get('custom_constraints', [])  # [{reaction, lower, upper}]

        # Load model from pickle
        script_dir = Path(__file__).parent.parent
        pickle_path = script_dir / 'data' / 'models' / 'Human-GEM.pkl'

        if not pickle_path.exists():
            raise Exception(f"Model pickle not found: {pickle_path}")

        with open(pickle_path, 'rb') as f:
            model = pickle.load(f)

        # Set solver if specified
        if solver_name and solver_name != 'glpk':
            try:
                model.solver = solver_name
            except:
                pass  # Fallback to default if solver not available

        # Save original bounds for all reactions
        original_bounds = {}
        for rxn in model.reactions:
            original_bounds[rxn.id] = rxn.bounds

        # 1. Set primary exchange reaction
        try:
            exchange_rxn = model.reactions.get_by_id(exchange_reaction_id)
            exchange_rxn.lower_bound = lower_bound
            exchange_rxn.upper_bound = upper_bound
        except KeyError:
            raise Exception(f"Exchange reaction {exchange_reaction_id} not found in model")

        # 2. Apply pathway-specific bounds overrides
        for rxn_id, bounds in pathway_bounds.items():
            try:
                rxn = model.reactions.get_by_id(rxn_id)
                rxn.lower_bound = float(bounds['lower'])
                rxn.upper_bound = float(bounds['upper'])
            except KeyError:
                pass  # Skip if reaction not found

        # 3. Apply medium exchanges
        for med in medium_exchanges:
            if not med.get('active', True):
                continue
            try:
                rxn = model.reactions.get_by_id(med['id'])
                rxn.lower_bound = float(med['lower'])
                rxn.upper_bound = float(med['upper'])
            except KeyError:
                pass  # Skip if reaction not found

        # 4. Apply custom constraints
        for constraint in custom_constraints:
            rxn_id = constraint.get('reaction')
            if not rxn_id:
                continue
            try:
                rxn = model.reactions.get_by_id(rxn_id)
                if constraint.get('lower') is not None:
                    rxn.lower_bound = float(constraint['lower'])
                if constraint.get('upper') is not None:
                    rxn.upper_bound = float(constraint['upper'])
            except KeyError:
                pass  # Skip if reaction not found

        # 5. Set objective function
        if objective_id and objective_id != 'biomass':
            try:
                # Check if it's a specific reaction ID
                if objective_id in [rxn.id for rxn in model.reactions]:
                    model.objective = objective_id
                elif objective_id.lower() == 'atpm':
                    # Find ATPM reaction
                    atpm_rxn = None
                    for rxn in model.reactions:
                        if 'ATPM' in rxn.id or 'atpm' in rxn.id.lower():
                            atpm_rxn = rxn
                            break
                    if atpm_rxn:
                        model.objective = atpm_rxn.id
            except:
                pass  # Keep default biomass objective

        # 6. Run FBA with options
        if use_pfba:
            from cobra.flux_analysis import pfba
            solution = pfba(model)
        elif use_loopless:
            # Loopless FBA requires additional setup
            solution = model.optimize()
        else:
            solution = model.optimize()

        # Get fluxes for selected reactions
        fluxes = []
        for rxn_id in reaction_ids:
            try:
                rxn = model.reactions.get_by_id(rxn_id)
                flux = solution.fluxes[rxn_id] if solution.status == 'optimal' else 0.0

                fluxes.append({
                    "reaction_id": rxn.id,
                    "reaction_name": rxn.name,
                    "flux": float(flux)
                })
            except KeyError:
                continue

        # Restore original bounds for all reactions
        for rxn in model.reactions:
            if rxn.id in original_bounds:
                rxn.bounds = original_bounds[rxn.id]

        output = {
            "success": True,
            "status": solution.status,
            "objective_value": float(solution.objective_value) if solution.status == 'optimal' else 0.0,
            "fluxes": fluxes,
            "config": config
        }

        print(json.dumps(output))

    except Exception as e:
        print(json.dumps({"success": False, "error": str(e)}))
        sys.exit(1)

if __name__ == "__main__":
    main()
