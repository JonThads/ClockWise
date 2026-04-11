# tests/integration/conftest.py
import pytest
import httpx
import os

@pytest.fixture(scope="session")
def base_url():
    return os.getenv("FASTAPI_URL", "http://localhost:8000")

@pytest.fixture(scope="session")
def client(base_url):
    with httpx.Client(base_url=base_url, timeout=10.0) as client:
        yield client