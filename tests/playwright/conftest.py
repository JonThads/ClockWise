import pytest
from playwright.sync_api import sync_playwright
import os
import json
from pathlib import Path

# -----------------------------
# Browser & Page Fixtures
# -----------------------------
@pytest.fixture(scope="session")
def browser():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        yield browser
        browser.close()

@pytest.fixture(scope="function")
def page(browser):
    context = browser.new_context(
        # Ensure no cookies/storage are shared between tests
        storage_state=None,
        ignore_https_errors=True,
    )
    page = context.new_page()
    yield page
    context.clear_cookies()
    context.close()

@pytest.fixture(scope="session")
def base_url():
    return os.getenv("BASE_URL", "http://localhost:8080")

# -----------------------------
# Credentials
# -----------------------------
@pytest.fixture(scope="session")
def credentials():
    credentials_path = Path("/app/config/credentials.json")

    with open(credentials_path, "r") as f:
        return json.load(f)

# -----------------------------
# Hook: Screenshot
# -----------------------------
@pytest.hookimpl(tryfirst=True, hookwrapper=True)
def pytest_runtest_makereport(item, call):
    outcome = yield
    rep = outcome.get_result()

    if rep.when == "call":
        page = item.funcargs.get("page")
        if page:
            os.makedirs("playwright_screenshots", exist_ok=True)
            status = "passed" if rep.passed else "failed"
            screenshot_path = f"report/playwright_screenshots/{item.name}_{status}.png"
            page.screenshot(path=screenshot_path)
            print(f"\nScreenshot saved: {screenshot_path}")