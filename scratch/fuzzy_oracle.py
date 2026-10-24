"""
Independent Mathematical Oracle for Mamdani Fuzzy Logic Inference.
Sistem Web Monitoring Tanaman Melon (Phase 15.1)
Pure Python Standard Library (No external libraries, completely decoupled from Laravel PHP).
Generates exact reference calculations for Scenarios A through P.
"""

import json
from typing import Dict, List, Tuple, Optional, Any

# 1. Membership Function Definitions
def triangular_mu(x: float, a: float, b: float, c: float) -> float:
    if a >= c:
        raise ValueError(f"Invalid triangular params: a must be < c. Got a={a}, b={b}, c={c}")
    if not (a <= b <= c):
        raise ValueError(f"Invalid triangular params: must be a <= b <= c. Got a={a}, b={b}, c={c}")
    
    if x <= a or x >= c:
        return 1.0 if x == b else 0.0
    if x == b:
        return 1.0
    if a < x < b:
        return (x - a) / (b - a) if b > a else 1.0
    if b < x < c:
        return (c - x) / (c - b) if c > b else 1.0
    return 0.0


def trapezoidal_mu(x: float, a: float, b: float, c: float, d: float) -> float:
    if a >= d:
        raise ValueError(f"Invalid trapezoidal params: a must be < d. Got a={a}, d={d}")
    if not (a <= b <= c <= d):
        raise ValueError(f"Invalid trapezoidal params: must be a <= b <= c <= d. Got a={a}, b={b}, c={c}, d={d}")
    if a == b and c == d:
        raise ValueError("Cannot be both left-shoulder and right-shoulder simultaneously")
    
    # Left shoulder: a == b -> 1.0 for all x <= b
    if a == b and x <= b:
        return 1.0
    # Right shoulder: c == d -> 1.0 for all x >= c
    if c == d and x >= c:
        return 1.0
    
    if x <= a or x >= d:
        return 0.0
    if a < x < b:
        return (x - a) / (b - a)
    if b <= x <= c:
        return 1.0
    if c < x < d:
        return (d - x) / (d - c)
    return 0.0


# 2. Configuration for Melon Fuzzy Inference
FUZZY_INPUTS = {
    'temperature': {
        'dingin': ('trapezoidal', [10.0, 10.0, 20.0, 24.0]),
        'optimal': ('triangular', [22.0, 27.0, 32.0]),
        'panas': ('trapezoidal', [29.0, 34.0, 50.0, 50.0]),
    },
    'humidity': {
        'kering': ('trapezoidal', [0.0, 0.0, 50.0, 65.0]),
        'optimal': ('triangular', [58.0, 70.0, 82.0]),
        'lembap': ('trapezoidal', [75.0, 85.0, 100.0, 100.0]),
    },
    'soil_moisture': {
        'kering': ('trapezoidal', [0.0, 0.0, 50.0, 65.0]),
        'ideal': ('triangular', [58.0, 70.0, 82.0]),
        'jenuh': ('trapezoidal', [75.0, 85.0, 100.0, 100.0]),
    },
    'ph': {
        'asam': ('trapezoidal', [0.0, 0.0, 5.5, 6.0]),
        'optimal': ('triangular', [5.8, 6.5, 7.2]),
        'basa': ('trapezoidal', [6.8, 7.5, 14.0, 14.0]),
    },
}

FUZZY_OUTPUTS = {
    'buruk': ('trapezoidal', [0.0, 0.0, 25.0, 45.0]),
    'perlu_perhatian': ('triangular', [35.0, 55.0, 75.0]),
    'baik': ('trapezoidal', [65.0, 80.0, 100.0, 100.0]),
}

RULES = [
    {
        'id': 'R01',
        'antecedents': {'temperature': 'optimal', 'humidity': 'optimal', 'soil_moisture': 'ideal', 'ph': 'optimal'},
        'operator': 'and', 'consequent': 'baik', 'weight': 1.0, 'enabled': True
    },
    {
        'id': 'R02',
        'antecedents': {'temperature': 'panas', 'soil_moisture': 'kering'},
        'operator': 'and', 'consequent': 'buruk', 'weight': 1.0, 'enabled': True
    },
    {
        'id': 'R03',
        'antecedents': {'temperature': 'panas', 'humidity': 'lembap'},
        'operator': 'and', 'consequent': 'perlu_perhatian', 'weight': 1.0, 'enabled': True
    },
    {
        'id': 'R04',
        'antecedents': {'temperature': 'dingin', 'humidity': 'lembap'},
        'operator': 'and', 'consequent': 'perlu_perhatian', 'weight': 1.0, 'enabled': True
    },
    {
        'id': 'R05',
        'antecedents': {'ph': 'asam', 'soil_moisture': 'kering'},
        'operator': 'and', 'consequent': 'buruk', 'weight': 1.0, 'enabled': True
    },
    {
        'id': 'R06',
        'antecedents': {'ph': 'basa', 'soil_moisture': 'jenuh'},
        'operator': 'and', 'consequent': 'buruk', 'weight': 1.0, 'enabled': True
    },
    {
        'id': 'R07',
        'antecedents': {'temperature': 'optimal', 'humidity': 'optimal', 'soil_moisture': 'kering'},
        'operator': 'and', 'consequent': 'perlu_perhatian', 'weight': 1.0, 'enabled': True
    },
    {
        'id': 'R08',
        'antecedents': {'temperature': 'optimal', 'humidity': 'optimal', 'soil_moisture': 'jenuh'},
        'operator': 'and', 'consequent': 'perlu_perhatian', 'weight': 1.0, 'enabled': True
    },
    {
        'id': 'R09',
        'antecedents': {'temperature': 'optimal', 'humidity': 'kering'},
        'operator': 'and', 'consequent': 'perlu_perhatian', 'weight': 1.0, 'enabled': True
    },
    {
        'id': 'R10',
        'antecedents': {'temperature': 'panas', 'soil_moisture': 'ideal'},
        'operator': 'and', 'consequent': 'perlu_perhatian', 'weight': 1.0, 'enabled': True
    },
    {
        'id': 'R11',
        'antecedents': {'temperature': 'dingin', 'soil_moisture': 'ideal'},
        'operator': 'and', 'consequent': 'perlu_perhatian', 'weight': 1.0, 'enabled': True
    },
    {
        'id': 'R12',
        'antecedents': {'ph': 'asam', 'temperature': 'optimal'},
        'operator': 'and', 'consequent': 'perlu_perhatian', 'weight': 1.0, 'enabled': True
    },
    {
        'id': 'R13',
        'antecedents': {'ph': 'basa', 'temperature': 'optimal'},
        'operator': 'and', 'consequent': 'perlu_perhatian', 'weight': 1.0, 'enabled': True
    },
    {
        'id': 'R14',
        'antecedents': {'temperature': 'panas', 'humidity': 'kering', 'soil_moisture': 'kering'},
        'operator': 'and', 'consequent': 'buruk', 'weight': 1.0, 'enabled': True
    },
    {
        'id': 'R15',
        'antecedents': {'temperature': 'dingin', 'humidity': 'lembap', 'soil_moisture': 'jenuh'},
        'operator': 'and', 'consequent': 'buruk', 'weight': 1.0, 'enabled': True
    },
    {
        'id': 'R16',
        'antecedents': {'temperature': 'optimal', 'humidity': 'optimal'},
        'operator': 'and', 'consequent': 'baik', 'weight': 0.9, 'enabled': True
    },
]

def calc_membership(curve_type: str, params: list, val: float) -> float:
    if curve_type == 'triangular':
        return triangular_mu(val, *params)
    elif curve_type == 'trapezoidal':
        return trapezoidal_mu(val, *params)
    else:
        raise ValueError(f"Unknown curve type {curve_type}")


def fuzzify_inputs(inputs: Dict[str, Optional[float]]) -> Dict[str, Dict[str, float]]:
    fuzzified = {}
    for var, sets in FUZZY_INPUTS.items():
        fuzzified[var] = {}
        val = inputs.get(var)
        if val is None:
            for s in sets:
                fuzzified[var][s] = 0.0
        else:
            for s, (ctype, params) in sets.items():
                fuzzified[var][s] = calc_membership(ctype, params, float(val))
    return fuzzified


def evaluate_rules(fuzzified: Dict[str, Dict[str, float]], rules: List[Dict]) -> Tuple[List[Dict], Dict[str, float]]:
    evaluations = []
    consequent_alphas: Dict[str, float] = {}

    for rule in rules:
        if not rule.get('enabled', True):
            continue
        antecedents = rule['antecedents']
        op = rule.get('operator', 'and').lower()
        weight = float(rule.get('weight', 1.0))
        consequent = rule['consequent'].lower()

        degrees = [fuzzified.get(var, {}).get(st, 0.0) for var, st in antecedents.items()]
        if not degrees:
            raw_alpha = 0.0
        elif op == 'or':
            raw_alpha = max(degrees)
        else:
            raw_alpha = min(degrees)

        firing_strength = raw_alpha * weight

        evaluations.append({
            'id': rule['id'],
            'raw_alpha': raw_alpha,
            'firing_strength': firing_strength,
            'consequent': consequent,
            'weight': weight,
        })

        if firing_strength > 0.0:
            if consequent not in consequent_alphas or firing_strength > consequent_alphas[consequent]:
                consequent_alphas[consequent] = firing_strength

    return evaluations, consequent_alphas


def defuzzify_centroid(
    consequent_alphas: Dict[str, float],
    z_min: float = 0.0,
    z_max: float = 100.0,
    step: float = 1.0
) -> Tuple[Optional[float], Optional[float], Optional[float], Dict[float, float]]:
    if not consequent_alphas or max(consequent_alphas.values()) <= 0.0:
        return None, 0.0, 0.0, {}

    num_steps = int(round((z_max - z_min) / step))
    numerator = 0.0
    denominator = 0.0
    curve = {}

    for i in range(num_steps + 1):
        z = z_min + i * step
        mu_z = 0.0

        for cons, alpha in consequent_alphas.items():
            if cons in FUZZY_OUTPUTS and alpha > 0.0:
                ctype, params = FUZZY_OUTPUTS[cons]
                mu_cons = calc_membership(ctype, params, z)
                # Mamdani implication: min(alpha, mu_cons)
                imp = min(alpha, mu_cons)
                # Mamdani aggregation: max across consequents
                if imp > mu_z:
                    mu_z = imp

        curve[z] = mu_z
        numerator += z * mu_z
        denominator += mu_z

    if denominator <= 0.0:
        return None, numerator, denominator, curve

    raw_score = numerator / denominator
    rounded_score = round(raw_score, 2)
    return rounded_score, numerator, denominator, curve


def resolve_category(score: Optional[float]) -> Optional[str]:
    if score is None:
        return None
    if score >= 65.0:
        return 'BAIK'
    elif score >= 40.0:
        return 'PERLU_PERHATIAN'
    else:
        return 'BURUK'


def run_inference(inputs: Dict[str, Optional[float]], z_min=0.0, z_max=100.0, step=1.0) -> Dict[str, Any]:
    fuzz = fuzzify_inputs(inputs)
    evals, cons_alphas = evaluate_rules(fuzz, RULES)
    score, num, den, curve = defuzzify_centroid(cons_alphas, z_min, z_max, step)
    status = 'AVAILABLE' if score is not None else 'INSUFFICIENT_RULE_COVERAGE'
    cat = resolve_category(score)
    return {
        'status': status,
        'score': score,
        'raw_score': (num / den) if (den and den > 0) else None,
        'numerator': num,
        'denominator': den,
        'category': cat,
        'fuzzified': fuzz,
        'consequent_alphas': cons_alphas,
        'active_rules': [e['id'] for e in evals if e['firing_strength'] > 0],
    }


def compute_all_scenarios():
    scenarios = {
        'Scenario_A': {
            'description': 'Semua parameter optimal',
            'inputs': {'temperature': 27.0, 'humidity': 70.0, 'soil_moisture': 70.0, 'ph': 6.5},
            'z_min': 0.0, 'z_max': 100.0, 'step': 1.0
        },
        'Scenario_B': {
            'description': 'Suhu tinggi dan kelembapan tanah rendah',
            'inputs': {'temperature': 36.0, 'humidity': 50.0, 'soil_moisture': 30.0, 'ph': 6.5},
            'z_min': 0.0, 'z_max': 100.0, 'step': 1.0
        },
        'Scenario_C': {
            'description': 'Kelembapan udara tinggi dan tanah jenuh',
            'inputs': {'temperature': 18.0, 'humidity': 90.0, 'soil_moisture': 90.0, 'ph': 6.5},
            'z_min': 0.0, 'z_max': 100.0, 'step': 1.0
        },
        'Scenario_D': {
            'description': 'pH terlalu asam',
            'inputs': {'temperature': 27.0, 'humidity': 70.0, 'soil_moisture': 70.0, 'ph': 4.5},
            'z_min': 0.0, 'z_max': 100.0, 'step': 1.0
        },
        'Scenario_E': {
            'description': 'pH terlalu basa',
            'inputs': {'temperature': 27.0, 'humidity': 70.0, 'soil_moisture': 70.0, 'ph': 8.5},
            'z_min': 0.0, 'z_max': 100.0, 'step': 1.0
        },
        'Scenario_F': {
            'description': 'Kombinasi beberapa aturan dengan output berbeda (overlap)',
            'inputs': {'temperature': 30.0, 'humidity': 62.0, 'soil_moisture': 62.0, 'ph': 6.5},
            'z_min': 0.0, 'z_max': 100.0, 'step': 1.0
        },
        'Scenario_G': {
            'description': 'Input tepat di titik batas membership',
            'inputs': {'temperature': 22.0, 'humidity': 58.0, 'soil_moisture': 58.0, 'ph': 5.8},
            'z_min': 0.0, 'z_max': 100.0, 'step': 1.0
        },
        'Scenario_H': {
            'description': 'Input di area overlap dua himpunan',
            'inputs': {'temperature': 23.0, 'humidity': 60.0, 'soil_moisture': 60.0, 'ph': 6.0},
            'z_min': 0.0, 'z_max': 100.0, 'step': 1.0
        },
        'Scenario_I': {
            'description': 'Tidak ada aturan aktif (Insufficient rule coverage)',
            'inputs': {'temperature': 15.0, 'humidity': 40.0, 'soil_moisture': 90.0, 'ph': 6.5},
            'z_min': 0.0, 'z_max': 100.0, 'step': 1.0
        },
        'Scenario_J': {
            'description': 'Missing/null sensor (Temperature & Humidity missing)',
            'inputs': {'temperature': None, 'humidity': None, 'soil_moisture': 70.0, 'ph': 6.5},
            'z_min': 0.0, 'z_max': 100.0, 'step': 1.0
        },
        'Scenario_K': {
            'description': 'Nilai input di luar domain fisik normal (ekstrem)',
            'inputs': {'temperature': 55.0, 'humidity': -5.0, 'soil_moisture': 110.0, 'ph': 15.0},
            'z_min': 0.0, 'z_max': 100.0, 'step': 1.0
        },
        'Scenario_L': {
            'description': 'Hanya satu aturan aktif (R10 aktif tunggal)',
            'inputs': {'temperature': 35.0, 'humidity': 70.0, 'soil_moisture': 70.0, 'ph': 6.5},
            'z_min': 0.0, 'z_max': 100.0, 'step': 1.0
        },
        'Scenario_M': {
            'description': 'Beberapa aturan aktif dengan konsekuen sama (R01 & R16: Baik)',
            'inputs': {'temperature': 27.0, 'humidity': 70.0, 'soil_moisture': 70.0, 'ph': 6.5},
            'z_min': 0.0, 'z_max': 100.0, 'step': 1.0
        },
        'Scenario_N': {
            'description': 'Beberapa aturan aktif dengan konsekuen berbeda (R04: Perhatian, R15: Buruk)',
            'inputs': {'temperature': 18.0, 'humidity': 90.0, 'soil_moisture': 90.0, 'ph': 6.5},
            'z_min': 0.0, 'z_max': 100.0, 'step': 1.0
        },
        'Scenario_O': {
            'description': 'Output membership left-shoulder vs right-shoulder murni',
            # Left shoulder murni: R02 firing=1.0 -> Buruk [0, 0, 25, 45]
            'inputs': {'temperature': 35.0, 'humidity': 45.0, 'soil_moisture': 40.0, 'ph': 6.5},
            'z_min': 0.0, 'z_max': 100.0, 'step': 1.0
        },
        'Scenario_P_step_0_5': {
            'description': 'Nilai centroid dengan resolusi sampling berbeda: step = 0.5',
            'inputs': {'temperature': 27.0, 'humidity': 70.0, 'soil_moisture': 70.0, 'ph': 6.5},
            'z_min': 0.0, 'z_max': 100.0, 'step': 0.5
        },
        'Scenario_P_step_0_1': {
            'description': 'Nilai centroid dengan resolusi sampling berbeda: step = 0.1',
            'inputs': {'temperature': 27.0, 'humidity': 70.0, 'soil_moisture': 70.0, 'ph': 6.5},
            'z_min': 0.0, 'z_max': 100.0, 'step': 0.1
        },
    }

    results = {}
    for sc_name, sc_data in scenarios.items():
        res = run_inference(
            sc_data['inputs'],
            sc_data.get('z_min', 0.0),
            sc_data.get('z_max', 100.0),
            sc_data.get('step', 1.0)
        )
        results[sc_name] = {
            'description': sc_data['description'],
            'inputs': sc_data['inputs'],
            'step': sc_data.get('step', 1.0),
            'status': res['status'],
            'score': res['score'],
            'raw_score': res['raw_score'],
            'numerator': res['numerator'],
            'denominator': res['denominator'],
            'category': res['category'],
            'active_rules': res['active_rules'],
            'consequent_alphas': res['consequent_alphas'],
        }

    return results


if __name__ == '__main__':
    all_res = compute_all_scenarios()
    with open('scratch/oracle_scenarios.json', 'w') as f:
        json.dump(all_res, f, indent=2)

    print(f"Generated {len(all_res)} scenarios successfully!")
    for k, v in all_res.items():
        score_str = f"{v['score']} (raw={v['raw_score']:.6f})" if v['score'] is not None else "None"
        num_str = f"{v['numerator']:.4f}" if v['numerator'] is not None else "None"
        den_str = f"{v['denominator']:.4f}" if v['denominator'] is not None else "None"
        print(f"[{k}] {v['description']}: Status={v['status']}, Score={score_str}, Num={num_str}, Den={den_str}, Cat={v['category']}, Rules={v['active_rules']}")
