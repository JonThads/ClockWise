# tests/accessibility/test_a11y_edit_department.py
import allure
import pytest
from tests.accessibility.helpers import run_axe_scan, login_as_admin


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Accessibility — WCAG 2.1 AA")
@allure.story("Edit Department Form")
@allure.title("Edit Department — Pre-populated form passes WCAG scan")
@allure.description(
    "Navigates to the Edit Department form for dept_id=1 (HR). "
    "Scans the pre-populated form with name and description filled."
)
@allure.severity(allure.severity_level.NORMAL)
@allure.tag("a11y", "wcag", "edit-department", "form")
@pytest.mark.a11y
async def test_edit_department_prepopulated(page, base_url, credentials):
    await login_as_admin(page, base_url, credentials)
    await page.goto(f"{base_url}edit-department.php?id=1", wait_until="domcontentloaded")
    await page.wait_for_timeout(500)
    await run_axe_scan(page, "Edit Department — Pre-populated Form (dept_id=1)")


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Accessibility — WCAG 2.1 AA")
@allure.story("Edit Department Form")
@allure.title("Edit Department — Validation error state passes WCAG scan")
@allure.description(
    "Clears the required Department Name field and submits "
    "to trigger a validation error. Scans with error "
    "markup rendered in the DOM."
)
@allure.severity(allure.severity_level.NORMAL)
@allure.tag("a11y", "wcag", "edit-department", "form", "error-state")
@pytest.mark.a11y
async def test_edit_department_validation_error(page, base_url, credentials):
    await login_as_admin(page, base_url, credentials)
    await page.goto(f"{base_url}edit-department.php?id=1", wait_until="domcontentloaded")
    await page.fill("#dept_name", "")
    await page.get_by_role("button", name="Update Department").click()
    await page.wait_for_timeout(500)
    await run_axe_scan(page, "Edit Department — Validation Error State")