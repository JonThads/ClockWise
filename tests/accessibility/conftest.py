# tests/accessibility/conftest.py
import pytest
import os
import json
from playwright.async_api import async_playwright

@pytest.fixture(scope="session")
def base_url():
    return os.getenv("BASE_URL", "http://localhost:8080")

@pytest.fixture(scope="session")
def credentials():
    credentials_path = os.path.join(
        os.path.dirname(__file__),
        "..", "..",
        "src", "config", "credentials.json"
    )
    with open(os.path.normpath(credentials_path), "r") as f:
        return json.load(f)

@pytest.fixture
async def page():
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        context = await browser.new_context(storage_state=None)
        page = await context.new_page()
        yield page
        await context.clear_cookies()
        await context.close()
        await browser.close()