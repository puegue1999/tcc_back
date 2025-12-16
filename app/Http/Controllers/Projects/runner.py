import sys, json
from qiskit import QuantumCircuit, transpile
from qiskit_aer import AerSimulator

GATE_MAP = {
    "h": lambda qc, q, p: qc.h(q[0]),
    "x": lambda qc, q, p: qc.x(q[0]),
    "y": lambda qc, q, p: qc.y(q[0]),
    "z": lambda qc, q, p: qc.z(q[0]),
    "rx": lambda qc, q, p: qc.rx(p[0], q[0]),
    "ry": lambda qc, q, p: qc.ry(p[0], q[0]),
    "rz": lambda qc, q, p: qc.rz(p[0], q[0]),
    "u": lambda qc, q, p: qc.u(p[0], p[1], p[2], q[0]),
    "u3": lambda qc, q, p: qc.u(p[0], p[1], p[2], q[0]),
    "u2": lambda qc, q, p: qc.u2(p[0], p[1], q[0]),
    "u1": lambda qc, q, p: qc.u1(p[0], q[0]),
    "cx": lambda qc, q, p: qc.cx(q[0], q[1]),
    "cz": lambda qc, q, p: qc.cz(q[0], q[1]),
    "swap": lambda qc, q, p: qc.swap(q[0], q[1]),
    "ccx": lambda qc, q, p: qc.ccx(q[0], q[1], q[2]),
    "cswap": lambda qc, q, p: qc.cswap(q[0], q[1], q[2]),
    "barrier": lambda qc, q, p: qc.barrier(q),
    "reset": lambda qc, q, p: qc.reset(q[0])
}

def build_and_run_circuit(qobj):
    num_qubits = qobj["qubits"]
    shots = qobj.get("shots", 1024)
    gates = qobj["gates"]

    qc = QuantumCircuit(num_qubits, num_qubits)

    has_explicit_measure = False\
    
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

    sim = AerSimulator()
    t_qc = transpile(qc, sim)

    job = sim.run(t_qc, shots=shots)
    result = job.result()

    return result.get_counts(0)


if __name__ == "__main__":
    qobj = json.load(sys.stdin)
    print(json.dumps(build_and_run_circuit(qobj)))
