# tests/accessibility/test_a11y_add_employee.py
import allure
import pytest
from tests.accessibility.helpers import run_axe_scan, login_as_admin


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Accessibility — WCAG 2.1 AA")
@allure.story("Add Employee Form")
@allure.title("Add Employee — Empty form passes WCAG scan")
@allure.description(
    "Navigates to the Add Employee form and scans in its "
    "default empty state. Verifies all fieldsets, labels, "
    "inputs, dropdowns, and breadcrumb navigation pass WCAG."
)
@allure.severity(allure.severity_level.CRITICAL)
@allure.tag("a11y", "wcag", "add-employee", "form")
@pytest.mark.a11y
def test_add_employee_default(page, base_url, credentials):
    login_as_admin(page, base_url, credentials)
    page.goto(f"{base_url}add-employee.php", wait_until="domcontentloaded")
    page.wait_for_timeout(500)
    run_axe_scan(page, "Add Employee — Default State")


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Accessibility — WCAG 2.1 AA")
@allure.story("Add Employee Form")
@allure.title("Add Employee — Validation error state passes WCAG scan")
@allure.description(
    "Submits the Add Employee form empty to trigger all "
    "validation errors. Scans the page with aria-invalid "
    "and field-error elements rendered in the DOM."
)
@allure.severity(allure.severity_level.CRITICAL)
@allure.tag("a11y", "wcag", "add-employee", "form", "error-state")
@pytest.mark.a11y
def test_add_employee_validation_errors(page, base_url, credentials):
    login_as_admin(page, base_url, credentials)
    page.goto(f"{base_url}add-employee.php", wait_until="domcontentloaded")
    page.get_by_role("button", name="Add Employee").click()
    page.wait_for_timeout(500)
    run_axe_scan(page, "Add Employee — Validation Error State")