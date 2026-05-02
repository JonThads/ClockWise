# tests/accessibility/test_a11y_edit_employee.py
import allure
import pytest
from tests.accessibility.helpers import run_axe_scan, login_as_admin


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Accessibility — WCAG 2.1 AA")
@allure.story("Edit Employee Form")
@allure.title("Edit Employee — Pre-populated form passes WCAG scan")
@allure.description(
    "Navigates to the Edit Employee form for employee ID 17 "
    "(Jool Lo — Rank and File). Scans the fully pre-populated "
    "form with all dropdowns and inputs filled."
)
@allure.severity(allure.severity_level.CRITICAL)
@allure.tag("a11y", "wcag", "edit-employee", "form")
@pytest.mark.a11y
def test_edit_employee_prepopulated(page, base_url, credentials):
    login_as_admin(page, base_url, credentials)
    page.goto(
        f"{base_url}edit-employee.php?id=17",
        wait_until="domcontentloaded"
    )
    page.wait_for_timeout(500)
    run_axe_scan(page, "Edit Employee — Pre-populated Form (emp_id=17)")


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Accessibility — WCAG 2.1 AA")
@allure.story("Edit Employee Form")
@allure.title("Edit Employee — Validation error state passes WCAG scan")
@allure.description(
    "Clears the required First Name field and submits to "
    "trigger a validation error. Scans with aria-invalid "
    "and field-error paragraph rendered."
)
@allure.severity(allure.severity_level.CRITICAL)
@allure.tag("a11y", "wcag", "edit-employee", "form", "error-state")
@pytest.mark.a11y
def test_edit_employee_validation_error(page, base_url, credentials):
    login_as_admin(page, base_url, credentials)
    page.goto(
        f"{base_url}edit-employee.php?id=17",
        wait_until="domcontentloaded"
    )
    page.fill("#first_name", "")
    page.get_by_role("button", name="Update Employee").click()
    page.wait_for_timeout(500)
    run_axe_scan(page, "Edit Employee — Validation Error State")