# tests/accessibility/test_a11y_edit_shift.py
import allure
import pytest
from tests.accessibility.helpers import run_axe_scan, login_as_admin


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Accessibility — WCAG 2.1 AA")
@allure.story("Edit Shift Form")
@allure.title("Edit Shift — Pre-populated form passes WCAG scan")
@allure.description(
    "Navigates to the Edit Shift Schedule form for "
    "shift_sched_id=1 (Morning Shift). Scans the fully "
    "pre-populated form with name, code, and time inputs filled."
)
@allure.severity(allure.severity_level.NORMAL)
@allure.tag("a11y", "wcag", "edit-shift", "form")
@pytest.mark.a11y
def test_edit_shift_prepopulated(page, base_url, credentials):
    login_as_admin(page, base_url, credentials)
    page.goto(
        f"{base_url}edit-shift-schedule.php?id=1",
        wait_until="domcontentloaded"
    )
    page.wait_for_timeout(500)
    run_axe_scan(page, "Edit Shift — Pre-populated Form (shift_id=1)")


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Accessibility — WCAG 2.1 AA")
@allure.story("Edit Shift Form")
@allure.title("Edit Shift — Validation error state passes WCAG scan")
@allure.description(
    "Clears the required Shift Name field and submits to "
    "trigger a validation error. Scans with aria-invalid "
    "and error paragraph rendered in the DOM."
)
@allure.severity(allure.severity_level.NORMAL)
@allure.tag("a11y", "wcag", "edit-shift", "form", "error-state")
@pytest.mark.a11y
def test_edit_shift_validation_error(page, base_url, credentials):
    login_as_admin(page, base_url, credentials)
    page.goto(
        f"{base_url}edit-shift-schedule.php?id=1",
        wait_until="domcontentloaded"
    )
    page.fill("#shift_sched_name", "")
    page.get_by_role("button", name="Update Shift Schedule").click()
    page.wait_for_timeout(500)
    run_axe_scan(page, "Edit Shift — Validation Error State")