# tests/conftest.py
import pytest
import json
from pathlib import Path

@pytest.fixture(scope="session")
def credentials():
    credentials_path = Path("/app/config/credentials.json")
    with open(credentials_path, "r") as f:
        return json.load(f)