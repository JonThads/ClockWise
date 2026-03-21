import re
import pytest
from datetime import date, timedelta
from playwright.sync_api import expect


# -----------------------------
# Helper Functions
# -----------------------------
def login(page, base_url, username, password):
    page.goto(f"{base_url}login.php", wait_until="domcontentloaded")
    page.get_by_role("textbox", name="Username").fill(username)
    page.get_by_role("textbox", name="Password").fill(password)
    page.get_by_role("button", name="Log In").click()
    page.wait_for_url(re.compile(r"user-dashboard\.php"), wait_until="domcontentloaded")


def close_modal(page):
    page.keyboard.press("Escape")
    page.wait_for_selector("#dtrModal", state="hidden")


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


# -----------------------------
# File VL
# -----------------------------
def test_no_vl_balance(page, base_url, credentials, test_leave_date):
    no_leave = credentials["no_leave"]
    login(page, base_url, no_leave["username"], no_leave["password"])

    print("ACTUAL URL:", page.url)
    expect(page).to_have_url(re.compile(r"user-dashboard\.php"))
    expect(page.locator("#page-heading")).to_contain_text("My Calendar")
    expect(page.locator("header")).to_contain_text("Employee NoLeaves")
    expect(page.locator("header")).to_contain_text("Managerial")
    expect(page.get_by_label("Calendar legend")).to_contain_text("VL 0 / 15")

    # ── Open the modal and navigate to the Leave form ──────────────────────────
    navigate_to_date_on_calendar(page, base_url, test_leave_date)
    page.get_by_role("button", name="File Leave Request").click()

    # ── Assert the VL option is disabled because balance is 0 ─────────────────
    vl_option = page.locator("#leave_type_id option").filter(has_text="VL")
    expect(vl_option).to_be_disabled()

    close_modal(page)
    logout(page)


# -----------------------------
# File SL
# -----------------------------
def test_no_sl_balance(page, base_url, credentials, test_leave_date):
    no_leave = credentials["no_leave"]
    login(page, base_url, no_leave["username"], no_leave["password"])

    print("ACTUAL URL:", page.url)
    expect(page).to_have_url(re.compile(r"user-dashboard\.php"))
    expect(page.locator("#page-heading")).to_contain_text("My Calendar")
    expect(page.locator("header")).to_contain_text("Employee NoLeaves")
    expect(page.locator("header")).to_contain_text("Managerial")
    expect(page.get_by_label("Calendar legend")).to_contain_text("SL 0 / 5")

    # ── Open the modal and navigate to the Leave form ──────────────────────────
    navigate_to_date_on_calendar(page, base_url, test_leave_date)
    page.get_by_role("button", name="File Leave Request").click()

    # ── Assert the SL option is disabled because balance is 0 ─────────────────
    sl_option = page.locator("#leave_type_id option").filter(has_text="SL")
    expect(sl_option).to_be_disabled()

    close_modal(page)
    logout(page)


# -----------------------------
# File EL
# -----------------------------
def test_no_el_balance(page, base_url, credentials, test_leave_date):
    no_leave = credentials["no_leave"]
    login(page, base_url, no_leave["username"], no_leave["password"])

    print("ACTUAL URL:", page.url)
    expect(page).to_have_url(re.compile(r"user-dashboard\.php"))
    expect(page.locator("#page-heading")).to_contain_text("My Calendar")
    expect(page.locator("header")).to_contain_text("Employee NoLeaves")
    expect(page.locator("header")).to_contain_text("Managerial")
    expect(page.get_by_label("Calendar legend")).to_contain_text("EL 0 / 3")

    # ── Open the modal and navigate to the Leave form ──────────────────────────
    navigate_to_date_on_calendar(page, base_url, test_leave_date)
    page.get_by_role("button", name="File Leave Request").click()

    # ── Assert the EL option is disabled because balance is 0 ─────────────────
    el_option = page.locator("#leave_type_id option").filter(has_text="EL")
    expect(el_option).to_be_disabled()

    close_modal(page)
    logout(page)


# -----------------------------
# File NoPay
# -----------------------------
def test_no_nopay_balance(page, base_url, credentials, test_leave_date):
    no_leave = credentials["no_leave"]
    login(page, base_url, no_leave["username"], no_leave["password"])

    print("ACTUAL URL:", page.url)
    expect(page).to_have_url(re.compile(r"user-dashboard\.php"))
    expect(page.locator("#page-heading")).to_contain_text("My Calendar")
    expect(page.locator("header")).to_contain_text("Employee NoLeaves")
    expect(page.locator("header")).to_contain_text("Managerial")
    expect(page.get_by_label("Calendar legend")).to_contain_text("NoPay 0 / 15")

    # ── Open the modal and navigate to the Leave form ──────────────────────────
    navigate_to_date_on_calendar(page, base_url, test_leave_date)
    page.get_by_role("button", name="File Leave Request").click()

    # ── Assert the NoPay option is disabled because balance is 0 ─────────────────
    nopay_option = page.locator("#leave_type_id option").filter(has_text="NoPay")
    expect(nopay_option).to_be_disabled()

    close_modal(page)
    logout(page)


# -----------------------------
# File EDU
# -----------------------------
def test_no_edu_balance(page, base_url, credentials, test_leave_date):
    no_leave = credentials["no_leave"]
    login(page, base_url, no_leave["username"], no_leave["password"])

    print("ACTUAL URL:", page.url)
    expect(page).to_have_url(re.compile(r"user-dashboard\.php"))
    expect(page.locator("#page-heading")).to_contain_text("My Calendar")
    expect(page.locator("header")).to_contain_text("Employee NoLeaves")
    expect(page.locator("header")).to_contain_text("Managerial")
    expect(page.get_by_label("Calendar legend")).to_contain_text("EDU 0 / 15")

    # ── Open the modal and navigate to the Leave form ──────────────────────────
    navigate_to_date_on_calendar(page, base_url, test_leave_date)
    page.get_by_role("button", name="File Leave Request").click()

    # ── Assert the EDU option is disabled because balance is 0 ─────────────────
    edu_option = page.locator("#leave_type_id option").filter(has_text="EDU")
    expect(edu_option).to_be_disabled()

    close_modal(page)
    logout(page)