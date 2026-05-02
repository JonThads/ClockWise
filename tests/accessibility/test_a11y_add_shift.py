# tests/accessibility/test_a11y_add_shift.py
import allure
import pytest
from tests.accessibility.helpers import run_axe_scan, login_as_admin


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Accessibility — WCAG 2.1 AA")
@allure.story("Add Shift Form")
@allure.title("Add Shift — Empty form passes WCAG scan")
@allure.description(
    "Navigates to the Add Shift Schedule form and scans in "
    "its default empty state. Verifies the time inputs, "
    "text inputs, and optional description textarea pass WCAG."
)
@allure.severity(allure.severity_level.NORMAL)
@allure.tag("a11y", "wcag", "add-shift", "form")
@pytest.mark.a11y
def test_add_shift_default(page, base_url, credentials):
    login_as_admin(page, base_url, credentials)
    page.goto(f"{base_url}add-shift.php", wait_until="domcontentloaded")
    page.wait_for_timeout(500)
    run_axe_scan(page, "Add Shift — Default State")


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Accessibility — WCAG 2.1 AA")
@allure.story("Add Shift Form")
@allure.title("Add Shift — Validation error state passes WCAG scan")
@allure.description(
    "Submits the Add Shift form empty to trigger validation "
    "errors on shift name, start time, and end time fields. "
    "Scans with all three error messages rendered."
)
@allure.severity(allure.severity_level.NORMAL)
@allure.tag("a11y", "wcag", "add-shift", "form", "error-state")
@pytest.mark.a11y
def test_add_shift_validation_error(page, base_url, credentials):
    login_as_admin(page, base_url, credentials)
    page.goto(f"{base_url}add-shift.php", wait_until="domcontentloaded")
    page.get_by_role("button", name="Add Shift").click()
    page.wait_for_timeout(500)
    run_axe_scan(page, "Add Shift — Validation Error State")