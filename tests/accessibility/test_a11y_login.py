# tests/accessibility/test_a11y_login.py
import allure
import pytest
from tests.accessibility.helpers import run_axe_scan


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Accessibility — WCAG 2.1 AA")
@allure.story("Automated axe-core Scan")
@allure.title("Login page — Zero WCAG violations on page load")
@allure.description(
    "Runs axe-core against the fully rendered login page. "
    "Verifies zero critical or serious WCAG 2.1 AA violations exist."
)
@allure.severity(allure.severity_level.CRITICAL)
@allure.tag("a11y", "wcag", "axe-core", "login", "smoke")
@pytest.mark.a11y
def test_login_axe_scan(page, base_url):
    page.goto(f"{base_url}login.php", wait_until="domcontentloaded")
    page.wait_for_timeout(1000)
    run_axe_scan(page, "Login Page")


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Accessibility — WCAG 2.1 AA")
@allure.story("Automated axe-core Scan")
@allure.title("Login page — Zero violations with error state active")
@allure.description(
    "Submits the login form empty to trigger validation errors, "
    "then re-scans. Verifies ARIA error markup is itself accessible."
)
@allure.severity(allure.severity_level.CRITICAL)
@allure.tag("a11y", "wcag", "axe-core", "login", "error-state")
@pytest.mark.a11y
def test_login_error_state_axe_scan(page, base_url):
    page.goto(f"{base_url}login.php", wait_until="domcontentloaded")
    page.get_by_role("button", name="Log In").click()
    page.wait_for_timeout(1000)
    run_axe_scan(page, "Login Page — Error State")