import sys, json, os, sys, traceback

import mysql.connector

from qiskit import QuantumCircuit, transpile
from qiskit_aer import AerSimulator
from qiskit_aer.noise import NoiseModel
from qiskit.transpiler import CouplingMap

os.environ["QISKIT_SUPPRESS_PACKAGING_WARNINGS"] = "Y"
os.environ["QISKIT_IN_PARALLEL"] = "FALSE"

BASE_DIR = os.path.dirname(os.path.abspath(__file__))

noise_model = NoiseModel()

coupling = CouplingMap.from_line(29)

qobject_path = sys.argv[1]
external_id = sys.argv[2] if len(sys.argv) > 2 else None

with open(qobject_path, "r") as f:
    qobject = json.load(f)

def apply_1q_gate(qc, qubits, fn):
    if len(qubits) != 1:
        raise ValueError(f"Gate 1-qubit recebeu {len(qubits)} qubits")
    fn(qubits[0])

def apply_2q_gate(qc, qubits, fn):
    if len(qubits) != 2:
        raise ValueError(f"Gate 2-qubit recebeu {len(qubits)} qubits")
    fn(qubits[0], qubits[1])

def apply_param_1q_gate(qc, qubits, params, fn, n_params):
    if len(qubits) != 1:
        raise ValueError("Gate paramétrico 1-qubit inválido")
    if len(params) != n_params:
        raise ValueError(
            f"Gate paramétrico esperava {n_params} params, recebeu {len(params)}"
        )
    fn(params, qubits[0])

GATE_MAP = {
    "h": lambda qc, q, p: apply_1q_gate(qc, q, lambda a: qc.h(a)),
    "x": lambda qc, q, p: apply_1q_gate(qc, q, lambda a: qc.x(a)),
    "y": lambda qc, q, p: apply_1q_gate(qc, q, lambda a: qc.y(a)),
    "z": lambda qc, q, p: apply_1q_gate(qc, q, lambda a: qc.z(a)),

    "rx": lambda qc, q, p: apply_param_1q_gate(
        qc, q, p, lambda p, a: qc.rx(p[0], a), 1
    ),
    "ry": lambda qc, q, p: apply_param_1q_gate(
        qc, q, p, lambda p, a: qc.ry(p[0], a), 1
    ),
    "rz": lambda qc, q, p: apply_param_1q_gate(
        qc, q, p, lambda p, a: qc.rz(p[0], a), 1
    ),

    "u": lambda qc, q, p: apply_param_1q_gate(
        qc, q, p, lambda p, a: qc.u(p[0], p[1], p[2], a), 3
    ),
    "u3": lambda qc, q, p: apply_param_1q_gate(
        qc, q, p, lambda p, a: qc.u(p[0], p[1], p[2], a), 3
    ),
    "u2": lambda qc, q, p: apply_param_1q_gate(
        qc, q, p, lambda p, a: qc.u2(p[0], p[1], a), 2
    ),
    "u1": lambda qc, q, p: apply_param_1q_gate(
        qc, q, p, lambda p, a: qc.u1(p[0], a), 1
    ),

    "cx": lambda qc, q, p: apply_2q_gate(qc, q, lambda a, b: qc.cx(a, b)),
    "cz": lambda qc, q, p: apply_2q_gate(qc, q, lambda a, b: qc.cz(a, b)),
    "swap": lambda qc, q, p: apply_2q_gate(qc, q, lambda a, b: qc.swap(a, b)),

    "ccx": lambda qc, q, p: (
        len(q) == 3 or (_ for _ in ()).throw(ValueError("ccx precisa de 3 qubits")),
        qc.ccx(q[0], q[1], q[2])
    )[-1],

    "barrier": lambda qc, q, p: qc.barrier(q),
    "reset": lambda qc, q, p: qc.reset(q[0])
}


def save_counts_to_db(external_id, counts):
    conn = mysql.connector.connect(
        host="mariadb",
        port=3306,
        database="tcc_database",
        user="root",
        password="root"
    )
    
    cur = conn.cursor()
    
    query = "UPDATE projects SET qobject_result = %s WHERE external_id = %s"
    cur.execute(query, (json.dumps(counts), external_id))
    
    conn.commit()
    cur.close()
    conn.close()

def build_and_run_circuit(qobj, external_id):
    num_qubits = qobj["qubits"]
    shots = qobj.get("shots", 1024)
    gates = qobj["gates"]
    
    if not external_id:
        raise ValueError("external_id não informado")

    qc = QuantumCircuit(num_qubits, num_qubits)

    has_explicit_measure = False
    
    for gate in gates:
        gtype = gate["type"].lower()
        qubits = gate.get("qubits", [])
        params = gate.get("params", [])

        if gtype == "measure":
            has_explicit_measure = True
            bits = gate.get("bits", qubits)
            qc.measure(qubits, bits)
            continue

        if gtype not in GATE_MAP:
            raise ValueError(f"Gate '{gtype}' não suportada.")

        GATE_MAP[gtype](qc, qubits, params)

    if not has_explicit_measure:
        qc.measure(range(num_qubits), range(num_qubits))

    sim = AerSimulator(
        noise_model=noise_model
    )

    t_qc = transpile(
        qc,
        basis_gates=noise_model.basis_gates,
        coupling_map=coupling,
        optimization_level=0
    )

    job = sim.run(t_qc, shots=shots)
    result = job.result()

    counts = result.get_counts(0)

    save_counts_to_db(external_id, counts)

    del qc, t_qc, job, result

    return {
        "external_id": external_id,
        "status": "DONE",
        "states": len(counts)
    }


if __name__ == "__main__":
    try:
        build_and_run_circuit(qobject, external_id)
        sys.exit(0)
    except Exception:
        traceback.print_exc()
        sys.exit(1)

