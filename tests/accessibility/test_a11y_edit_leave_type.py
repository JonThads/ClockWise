# tests/accessibility/test_a11y_edit_leave_type.py
import allure
import pytest
from tests.accessibility.helpers import run_axe_scan, login_as_admin


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Accessibility — WCAG 2.1 AA")
@allure.story("Edit Leave Type Form")
@allure.title("Edit Leave Type — Pre-populated form passes WCAG scan")
@allure.description(
    "Navigates to the Edit Leave Type form for leave_type_id=1 "
    "(Vacation Leave). Scans the pre-populated form with "
    "leave type name and code fields filled."
)
@allure.severity(allure.severity_level.NORMAL)
@allure.tag("a11y", "wcag", "edit-leave-type", "form")
@pytest.mark.a11y
def test_edit_leave_type_prepopulated(page, base_url, credentials):
    login_as_admin(page, base_url, credentials)
    page.goto(
        f"{base_url}edit-leave-type.php?id=1",
        wait_until="domcontentloaded"
    )
    page.wait_for_timeout(500)
    run_axe_scan(page, "Edit Leave Type — Pre-populated Form (VL)")


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Accessibility — WCAG 2.1 AA")
@allure.story("Edit Leave Type Form")
@allure.title("Edit Leave Type — Validation error state passes WCAG scan")
@allure.description(
    "Clears both required fields and submits to trigger "
    "validation errors on leave type name and code. Scans "
    "with both error paragraphs rendered in the DOM."
)
@allure.severity(allure.severity_level.NORMAL)
@allure.tag("a11y", "wcag", "edit-leave-type", "form", "error-state")
@pytest.mark.a11y
def test_edit_leave_type_validation_error(page, base_url, credentials):
    login_as_admin(page, base_url, credentials)
    page.goto(
        f"{base_url}edit-leave-type.php?id=1",
        wait_until="domcontentloaded"
    )
    page.fill("#leave_type_name", "")
    page.fill("#leave_type_code", "")
    page.get_by_role("button", name="Update Leave Type").click()
    page.wait_for_timeout(500)
    run_axe_scan(page, "Edit Leave Type — Validation Error State")