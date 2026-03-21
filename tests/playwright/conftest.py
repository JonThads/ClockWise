import pytest
from playwright.sync_api import sync_playwright
import os
import json

# -----------------------------
# Browser & Page Fixtures
# -----------------------------
@pytest.fixture(scope="session")
def browser():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False)
        yield browser
        browser.close()

@pytest.fixture(scope="function")
def page(browser):
    context = browser.new_context()
    page = context.new_page()
    yield page
    context.close()

@pytest.fixture(scope="session")
def base_url():
    return os.getenv("BASE_URL", "http://localhost/ClockWise_Local_Testing/src/")

# -----------------------------
# Credentials
# -----------------------------
@pytest.fixture(scope="session")
def credentials():
    credentials_path = os.path.join(
        os.path.dirname(__file__),   # tests/playwright/
        "..",                        # tests/
        "..",                        # project root
        "src", "config", "credentials.json"
    )
    with open(os.path.normpath(credentials_path), "r") as f:
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
            os.makedirs("report/playwright_screenshots", exist_ok=True)
            status = "passed" if rep.passed else "failed"
            screenshot_path = f"report/playwright_screenshots/{item.name}_{status}.png"
            page.screenshot(path=screenshot_path)
            print(f"\nScreenshot saved: {screenshot_path}")