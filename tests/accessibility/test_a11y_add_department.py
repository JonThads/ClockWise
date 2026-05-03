# tests/accessibility/test_a11y_add_department.py
import allure
import pytest
from tests.accessibility.helpers import run_axe_scan, login_as_admin


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Accessibility — WCAG 2.1 AA")
@allure.story("Add Department Form")
@allure.title("Add Department — Empty form passes WCAG scan")
@allure.description(
    "Navigates to the Add Department form and scans in its "
    "default empty state. Verifies all inputs, optional "
    "fields, and breadcrumb navigation pass WCAG 2.1 AA."
)
@allure.severity(allure.severity_level.NORMAL)
@allure.tag("a11y", "wcag", "add-department", "form")
@pytest.mark.a11y
async def test_add_department_default(page, base_url, credentials):
    await login_as_admin(page, base_url, credentials)
    await page.goto(f"{base_url}add-department.php", wait_until="domcontentloaded")
    await page.wait_for_timeout(500)
    await run_axe_scan(page, "Add Department — Default State")


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Accessibility — WCAG 2.1 AA")
@allure.story("Add Department Form")
@allure.title("Add Department — Validation error state passes WCAG scan")
@allure.description(
    "Submits the Add Department form empty to trigger the "
    "required department name error. Scans with error "
    "markup rendered."
)
@allure.severity(allure.severity_level.NORMAL)
@allure.tag("a11y", "wcag", "add-department", "form", "error-state")
@pytest.mark.a11y
async def test_add_department_validation_error(page, base_url, credentials):
    await login_as_admin(page, base_url, credentials)
    await page.goto(f"{base_url}add-department.php", wait_until="domcontentloaded")
    await page.get_by_role("button", name="Add Department").click()
    await page.wait_for_timeout(500)
    await run_axe_scan(page, "Add Department — Validation Error State")