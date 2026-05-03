import re
import allure
import pytest
from datetime import date, timedelta
from playwright.sync_api import expect
from pathlib import Path


# -----------------------------
# Helper Functions
# -----------------------------
def login(page, base_url, username, password):
    page.goto(f"{base_url}login.php", wait_until="domcontentloaded")
    page.get_by_role("textbox", name="Username").fill(username)
    page.get_by_role("textbox", name="Password").fill(password)
    page.get_by_role("button", name="Log In").click()
    page.wait_for_url(re.compile(r"user-dashboard\.php"), wait_until="domcontentloaded")


def logout(page):
    page.get_by_role("link", name="Log out").click()


def get_test_date() -> date:
    """
    Returns a safe future weekday to use as the test leave date.
    Always picks a date at least 7 days from today to avoid conflicts
    with real submissions, and skips weekends.
    """
    candidate = date.today() + timedelta(days=7)
    while candidate.weekday() >= 5:  # 5 = Saturday, 6 = Sunday
        candidate += timedelta(days=1)
    return candidate


def build_date_label(target_date: date) -> str:
    """
    Builds the accessible date label that matches the calendar's aria-label,
    e.g. "Wednesday, April 9, 2026."
    Uses .day directly instead of strftime flags to stay cross-platform.
    """
    return f"{target_date.strftime('%A, %B')} {target_date.day}, {target_date.year}."


def navigate_to_date_on_calendar(page, base_url, target_date: date):
    """
    Navigates to the correct month/year via URL query params,
    then clicks the target date cell.
    Uses exact=False because the aria-label changes after a leave/DTR is filed
    (e.g. "Monday, March 23, 2026, VL pending. Press Enter to submit DTR or leave.")
    so a partial match on the date portion always works regardless of record state.
    """
    url = f"{base_url}user-dashboard.php?month={target_date.month}&year={target_date.year}"
    page.goto(url, wait_until="domcontentloaded")
    page.wait_for_selector("[role='grid']", state="visible")
    page.get_by_role("gridcell", name=build_date_label(target_date), exact=False).click()


# -----------------------------
# Fixtures
# -----------------------------
@pytest.fixture
def test_leave_date() -> date:
    """Shared test date — computed once so setup and teardown always use the same date."""
    return get_test_date()


@pytest.fixture
def cleanup_leave(page, base_url, credentials, test_leave_date, request):
    """
    Teardown-only fixture factory. Call cleanup_leave("VL"), cleanup_leave("SL"), etc.
    in your test to register the correct teardown for that leave type.
    """
    def _cleanup(leave_type_code: str):
        gridcell_name = build_date_label(test_leave_date).rstrip(".") + f", {leave_type_code}"

        def teardown():
            supervisory = credentials["supervisory"]
            login(page, base_url, supervisory["username"], supervisory["password"])
            page.get_by_role("gridcell", name=gridcell_name).click()
            page.on("dialog", lambda dialog: dialog.accept())
            page.get_by_role("button", name="Cancel Leave Request").click()
            page.wait_for_url(re.compile(r"user-dashboard\.php"), wait_until="domcontentloaded")
            logout(page)

        request.addfinalizer(teardown)

    return _cleanup


# -----------------------------
# File VL
# -----------------------------

@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Leave Management")
@allure.story("Employee can file a leave request")
@allure.title("Supervisory employee can file Vacation Leave (VL)")
@allure.description("Verifies that a Supervisory employee can submit a VL request and that it appears as pending in the assigned approver's approval queue.")
@allure.severity(allure.severity_level.CRITICAL)
@allure.tag("leave", "VL", "supervisory", "regression")

@pytest.mark.e2e
def test_file_vl_submission_success(page, base_url, credentials, test_leave_date, cleanup_leave):
    cleanup_leave("VL")

    supervisory = credentials["supervisory"]
    managerial_1 = credentials["managerial_1"]

    login(page, base_url, supervisory["username"], supervisory["password"])

    print("ACTUAL URL:", page.url)
    expect(page).to_have_url(re.compile(r"user-dashboard\.php"))
    expect(page.locator("#page-heading")).to_contain_text("My Calendar")
    expect(page.locator("header")).to_contain_text("Supervisory")

    navigate_to_date_on_calendar(page, base_url, test_leave_date)
    page.get_by_role("button", name="File Leave Request").click()
    page.get_by_label("Leave Type * (required)").select_option("1")
    page.get_by_role("button", name="Submit Leave").click()

    expect(page.get_by_role("status")).to_contain_text("Vacation Leave submitted")
    page.screenshot(path=f"report/playwright_screenshots/leave_submission/user/vl_submitted_{test_leave_date}.png")

    logout(page)

    login(page, base_url, managerial_1["username"], managerial_1["password"])

    print("ACTUAL URL:", page.url)
    expect(page).to_have_url(re.compile(r"user-dashboard\.php"))

    page.get_by_role("navigation", name="Main navigation").click()
    page.get_by_role("link", name="Approvals").click()

    expect(page.get_by_label("Leave Requests").locator("tbody")).to_contain_text("Khilua Asagi")
    expect(page.get_by_label("Leave Requests").locator("tbody")).to_contain_text("Vacation Leave (VL)")
    expect(page.get_by_label("Leave Requests").locator("tbody")).to_contain_text("Pending")
    page.screenshot(path=f"report/playwright_screenshots/leave_submission/user/vl_submission_check_{test_leave_date}.png")

    logout(page)


# -----------------------------
# File SL
# -----------------------------

@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Leave Management")
@allure.story("Employee can file a leave request")
@allure.title("Supervisory employee can file Sick Leave (SL)")
@allure.description("Verifies that a Supervisory employee can submit a SL request and that it appears as pending in the assigned approver's approval queue.")
@allure.severity(allure.severity_level.CRITICAL)
@allure.tag("leave", "SL", "supervisory", "regression")

@pytest.mark.e2e
def test_file_sl_submission_success(page, base_url, credentials, test_leave_date, cleanup_leave):
    cleanup_leave("SL")

    supervisory = credentials["supervisory"]
    managerial_1 = credentials["managerial_1"]

    login(page, base_url, supervisory["username"], supervisory["password"])

    print("ACTUAL URL:", page.url)
    expect(page).to_have_url(re.compile(r"user-dashboard\.php"))
    expect(page.locator("#page-heading")).to_contain_text("My Calendar")
    expect(page.locator("header")).to_contain_text("Supervisory")

    navigate_to_date_on_calendar(page, base_url, test_leave_date)
    page.get_by_role("button", name="File Leave Request").click()
    page.get_by_label("Leave Type * (required)").select_option("2")
    page.get_by_role("button", name="Submit Leave").click()

    expect(page.get_by_role("status")).to_contain_text("Sick Leave submitted")
    page.screenshot(path=f"report/playwright_screenshots/leave_submission/user/sl_submitted_{test_leave_date}.png")

    logout(page)

    login(page, base_url, managerial_1["username"], managerial_1["password"])

    print("ACTUAL URL:", page.url)
    expect(page).to_have_url(re.compile(r"user-dashboard\.php"))

    page.get_by_role("navigation", name="Main navigation").click()
    page.get_by_role("link", name="Approvals").click()

    expect(page.get_by_label("Leave Requests").locator("tbody")).to_contain_text("Khilua Asagi")
    expect(page.get_by_label("Leave Requests").locator("tbody")).to_contain_text("Sick Leave (SL)")
    expect(page.get_by_label("Leave Requests").locator("tbody")).to_contain_text("Pending")
    page.screenshot(path=f"report/playwright_screenshots/leave_submission/user/sl_submission_check_{test_leave_date}.png")

    logout(page)


# -----------------------------
# File EL
# -----------------------------

@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Leave Management")
@allure.story("Employee can file a leave request")
@allure.title("Supervisory employee can file Emergency Leave (EL)")
@allure.description("Verifies that a Supervisory employee can submit a EL request and that it appears as pending in the assigned approver's approval queue.")
@allure.severity(allure.severity_level.NORMAL)
@allure.tag("leave", "EL", "supervisory", "regression")

@pytest.mark.e2e
def test_file_el_submission_success(page, base_url, credentials, test_leave_date, cleanup_leave):
    cleanup_leave("EL")

    supervisory = credentials["supervisory"]
    managerial_1 = credentials["managerial_1"]

    login(page, base_url, supervisory["username"], supervisory["password"])

    print("ACTUAL URL:", page.url)
    expect(page).to_have_url(re.compile(r"user-dashboard\.php"))
    expect(page.locator("#page-heading")).to_contain_text("My Calendar")
    expect(page.locator("header")).to_contain_text("Supervisory")

    navigate_to_date_on_calendar(page, base_url, test_leave_date)
    page.get_by_role("button", name="File Leave Request").click()
    page.get_by_label("Leave Type * (required)").select_option("3")
    page.get_by_role("button", name="Submit Leave").click()

    expect(page.get_by_role("status")).to_contain_text("Emergency Leave submitted")
    page.screenshot(path=f"report/playwright_screenshots/leave_submission/user/el_submitted_{test_leave_date}.png")

    logout(page)

    login(page, base_url, managerial_1["username"], managerial_1["password"])

    print("ACTUAL URL:", page.url)
    expect(page).to_have_url(re.compile(r"user-dashboard\.php"))

    page.get_by_role("navigation", name="Main navigation").click()
    page.get_by_role("link", name="Approvals").click()

    expect(page.get_by_label("Leave Requests").locator("tbody")).to_contain_text("Khilua Asagi")
    expect(page.get_by_label("Leave Requests").locator("tbody")).to_contain_text("Emergency Leave (EL)")
    expect(page.get_by_label("Leave Requests").locator("tbody")).to_contain_text("Pending")
    page.screenshot(path=f"report/playwright_screenshots/leave_submission/user/el_submission_check_{test_leave_date}.png")

    logout(page)


# -----------------------------
# File NOPAY
# -----------------------------

@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Leave Management")
@allure.story("Employee can file a leave request")
@allure.title("Supervisory employee can file Leave Without Pay (NoPay)")
@allure.description("Verifies that a Supervisory employee can submit a Leave Without Pay (NoPay) request and that it appears as pending in the assigned approver's approval queue.")
@allure.severity(allure.severity_level.NORMAL)
@allure.tag("leave", "NoPay", "supervisory", "regression")

@pytest.mark.e2e
def test_file_NOPAY_submission_success(page, base_url, credentials, test_leave_date, cleanup_leave):
    cleanup_leave("NOPAY")

    supervisory = credentials["supervisory"]
    managerial_1 = credentials["managerial_1"]

    login(page, base_url, supervisory["username"], supervisory["password"])

    print("ACTUAL URL:", page.url)
    expect(page).to_have_url(re.compile(r"user-dashboard\.php"))
    expect(page.locator("#page-heading")).to_contain_text("My Calendar")
    expect(page.locator("header")).to_contain_text("Supervisory")

    navigate_to_date_on_calendar(page, base_url, test_leave_date)
    page.get_by_role("button", name="File Leave Request").click()
    page.get_by_label("Leave Type * (required)").select_option("5")
    page.get_by_role("button", name="Submit Leave").click()

    expect(page.get_by_role("status")).to_contain_text("Leave Without Pay submitted")
    page.screenshot(path=f"report/playwright_screenshots/leave_submission/user/nopay_submitted_{test_leave_date}.png")

    logout(page)

    login(page, base_url, managerial_1["username"], managerial_1["password"])

    print("ACTUAL URL:", page.url)
    expect(page).to_have_url(re.compile(r"user-dashboard\.php"))

    page.get_by_role("navigation", name="Main navigation").click()
    page.get_by_role("link", name="Approvals").click()

    expect(page.get_by_label("Leave Requests").locator("tbody")).to_contain_text("Khilua Asagi")
    expect(page.get_by_label("Leave Requests").locator("tbody")).to_contain_text("Leave Without Pay (NoPay)")
    expect(page.get_by_label("Leave Requests").locator("tbody")).to_contain_text("Pending")
    page.screenshot(path=f"report/playwright_screenshots/leave_submission/user/nopay_submission_check_{test_leave_date}.png")

    logout(page)


# -----------------------------
# File Study Leave (EDU)
# -----------------------------

@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Leave Management")
@allure.story("Employee can file a leave request")
@allure.title("Supervisory employee can file Study Leave (EDU)")
@allure.description("Verifies that a Supervisory employee can submit a Study Leave (EDU) request and that it appears as pending in the assigned approver's approval queue.")
@allure.severity(allure.severity_level.NORMAL)
@allure.tag("leave", "EDU", "supervisory", "regression")

@pytest.mark.e2e
def test_file_EDU_submission_success(page, base_url, credentials, test_leave_date, cleanup_leave):
    cleanup_leave("EDU")

    supervisory = credentials["supervisory"]
    managerial_1 = credentials["managerial_1"]

    login(page, base_url, supervisory["username"], supervisory["password"])

    print("ACTUAL URL:", page.url)
    expect(page).to_have_url(re.compile(r"user-dashboard\.php"))
    expect(page.locator("#page-heading")).to_contain_text("My Calendar")
    expect(page.locator("header")).to_contain_text("Supervisory")

    navigate_to_date_on_calendar(page, base_url, test_leave_date)
    page.get_by_role("button", name="File Leave Request").click()
    page.get_by_label("Leave Type * (required)").select_option("6")
    page.get_by_role("button", name="Submit Leave").click()

    expect(page.get_by_role("status")).to_contain_text("Study Leave submitted")
    page.screenshot(path=f"report/playwright_screenshots/leave_submission/user/edu_submitted_{test_leave_date}.png")

    logout(page)

    login(page, base_url, managerial_1["username"], managerial_1["password"])

    print("ACTUAL URL:", page.url)
    expect(page).to_have_url(re.compile(r"user-dashboard\.php"))

    page.get_by_role("navigation", name="Main navigation").click()
    page.get_by_role("link", name="Approvals").click()

    expect(page.get_by_label("Leave Requests").locator("tbody")).to_contain_text("Khilua Asagi")
    expect(page.get_by_label("Leave Requests").locator("tbody")).to_contain_text("Study Leave (EDU)")
    expect(page.get_by_label("Leave Requests").locator("tbody")).to_contain_text("Pending")
    page.screenshot(path=f"report/playwright_screenshots/leave_submission/user/edu_submission_check_{test_leave_date}.png")

    logout(page)