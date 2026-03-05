import pytest
from playwright.sync_api import sync_playwright
import os

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
# Hook: Screenshot on Failure
# -----------------------------
@pytest.hookimpl(tryfirst=True, hookwrapper=True)
def pytest_runtest_makereport(item, call):
    outcome = yield
    rep = outcome.get_result()
    
    # Only act on failed tests
    if rep.when == "call":
        page = item.funcargs.get("page")
        if page:
            # Ensure screenshots folder exists
            os.makedirs("playwright_screenshots", exist_ok=True)

            # Add test status (passed/failed) to filename
            status = "passed" if rep.passed else "failed"
            screenshot_path = f"playwright_screenshots/{item.name}_{status}.png"

            # Take screenshot
            page.screenshot(path=screenshot_path)
            print(f"\nScreenshot saved: {screenshot_path}")