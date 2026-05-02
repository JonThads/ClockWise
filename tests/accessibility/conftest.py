# tests/accessibility/conftest.py
import pytest
import os
import allure
from playwright.sync_api import sync_playwright


@pytest.fixture(scope="session")
def browser():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True,)
        yield browser
        browser.close()


@pytest.fixture(scope="function")
def page(browser):
    context = browser.new_context(storage_state=None)
    page = context.new_page()
    page.set_viewport_size({"width": 1280, "height": 720})
    yield page
    context.clear_cookies()
    context.close()


@pytest.fixture(scope="session")
def base_url():
    return os.getenv(
        "BASE_URL",
        "http://localhost/ClockWise_Local_Testing/src/"
    )


# Screenshot attachment on every test result
@pytest.hookimpl(tryfirst=True, hookwrapper=True)
def pytest_runtest_makereport(item, call):
    outcome = yield
    rep = outcome.get_result()

    if rep.when == "call":
        page = item.funcargs.get("page")
        if page:
            os.makedirs(
                "report/playwright_screenshots/accessibility",
                exist_ok=True
            )
            status = "passed" if rep.passed else "failed"
            screenshot_path = (
                f"report/playwright_screenshots/accessibility/"
                f"{item.name}_{status}.png"
            )
            try:
                screenshot_bytes = page.screenshot()
                page.screenshot(path=screenshot_path)
                allure.attach(
                    screenshot_bytes,
                    name=f"Screenshot — {status}",
                    attachment_type=allure.attachment_type.PNG
                )
            except Exception:
                pass