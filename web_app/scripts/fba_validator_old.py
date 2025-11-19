#!/usr/bin/env python3
"""
Run FBA validation on selected reactions with configurable parameters
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
        carbon_strategy = config.get('carbon_strategy', 'strict')
        objective_type = config.get('objective', 'biomass')
        solver_name = config.get('solver', 'glpk')
        flux_threshold = float(config.get('flux_threshold', 1e-6))
        use_pfba = config.get('pfba', False)
        use_loopless = config.get('loopless', False)

        # Load model from pickle
        script_dir = Path(__file__).parent.parent
        pickle_path = script_dir / 'data' / 'models' / 'Human-GEM.pkl'

        if not pickle_path.exists():
            raise Exception(f"Model pickle not found: {pickle_path}")

        with open(pickle_path, 'rb') as f:
            model = pickle.load(f)

        # Set solver if specified
        if solver_name and solver_name != 'glpk':
            model.solver = solver_name

        # Get exchange reaction by ID
        try:
            exchange_rxn = model.reactions.get_by_id(exchange_reaction_id)
        except KeyError:
            raise Exception(f"Exchange reaction {exchange_reaction_id} not found in model")

        # Save original bounds for all reactions
        original_bounds = {}
        for rxn in model.reactions:
            original_bounds[rxn.id] = rxn.bounds

        # Apply carbon source strategy
        if carbon_strategy == 'strict':
            # Close all other carbon source uptakes
            for rxn in model.exchanges:
                if rxn.id != exchange_rxn.id and rxn.lower_bound < 0:
                    rxn.lower_bound = 0

        # Set exchange reaction bounds
        exchange_rxn.lower_bound = lower_bound
        exchange_rxn.upper_bound = upper_bound

        # Set objective function
        if objective_type == 'atpm':
            # Find ATPM reaction
            atpm_rxn = None
            for rxn in model.reactions:
                if 'ATPM' in rxn.id or 'atpm' in rxn.id.lower():
                    atpm_rxn = rxn
                    break
            if atpm_rxn:
                model.objective = atpm_rxn.id
            else:
                raise Exception("ATPM reaction not found in model")
        # else: keep default biomass objective

        # Run FBA with options
        if use_pfba:
            from cobra.flux_analysis import pfba
            solution = pfba(model)
        elif use_loopless:
            solution = model.optimize(solver=solver_name if solver_name else 'glpk')
            # Note: loopless requires additional constraints, simplified here
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
            "config": {
                "exchange_reaction": exchange_reaction_id,
                "lower_bound": lower_bound,
                "upper_bound": upper_bound,
                "carbon_strategy": carbon_strategy,
                "objective": objective_type,
                "solver": solver_name,
                "flux_threshold": flux_threshold,
                "pfba": use_pfba,
                "loopless": use_loopless
            }
        }

        print(json.dumps(output))

    except Exception as e:
        print(json.dumps({"success": False, "error": str(e)}))
        sys.exit(1)

if __name__ == "__main__":
    main()
