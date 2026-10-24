import importlib.util
import json
import pathlib
import sys

root = pathlib.Path(__file__).resolve().parents[3]
spec = importlib.util.spec_from_file_location("simulator", root / "iot-simulator" / "simulator.py")
simulator = importlib.util.module_from_spec(spec)
spec.loader.exec_module(simulator)
config = json.load(sys.stdin)
payload = simulator.generate_sensor_payload(config["plant_id"])
payload["fuzzy_score"] = 0
responses = []
for _ in range(2):
    ok, status, body = simulator.send_reading(config["url"], payload, token=config["token"], device_id=config["device_id"])
    result = json.loads(body)
    responses.append({"ok": ok, "status": status, "id": result.get("data", {}).get("id"), "score": result.get("data", {}).get("fuzzy_score"), "source": result.get("data", {}).get("source")})
assert [r["status"] for r in responses] == [201, 200], responses
assert responses[0]["id"] == responses[1]["id"], responses
assert all(r["score"] == 0 and r["source"] == "simulator" for r in responses), responses
assert payload["event_id"], "event_id missing"
print(json.dumps({"responses": responses, "stable_event_id": True}))
