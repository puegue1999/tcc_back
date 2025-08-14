import sys, json
from qiskit import QuantumCircuit, transpile
from qiskit_aer import AerSimulator

def build_and_run_circuit(qobj):
    num_qubits = qobj["qubits"]
    gates = qobj["gates"]
    shots = qobj["shots"]

    qc = QuantumCircuit(num_qubits, num_qubits)
    for gate in gates:
        q = gate["qubits"]
        if gate["type"].lower() == "h":
            qc.h(q[0])
        elif gate["type"].lower() == "cx":
            qc.cx(q[0], q[1])
    qc.measure(range(num_qubits), range(num_qubits))

    sim = AerSimulator()
    qc = transpile(qc, sim)
    job = sim.run(qc, shots=shots)
    result = job.result()
    return result.get_counts(qc)

if __name__ == "__main__":
    qobj = json.load(sys.stdin)
    print(json.dumps(build_and_run_circuit(qobj)))
