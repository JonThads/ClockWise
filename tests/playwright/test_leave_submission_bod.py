import re
import os
import pytest
import pymysql
from pathlib import Path
from datetime import date, timedelta
from playwright.sync_api import expect
from dotenv import load_dotenv

load_dotenv(dotenv_path=Path(__file__).resolve().parent.parent.parent / ".env")


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
def cleanup_auto_approved_leave(credentials, test_leave_date, request):
    """
    Teardown fixture for auto-approved leave submissions (e.g. Board of Directors group).
    Since auto-approved leaves are inserted with status='approved' immediately,
    there is no cancel button in the UI — so teardown deletes the record directly
    from the DB using the test_leave_date fixture so the date is never hardcoded.

    Usage in test:
        def test_something(page, ..., cleanup_auto_approved_leave):
            cleanup_auto_approved_leave("board")   ← pass the credentials key
    """
    def _cleanup(credentials_key: str):
        username = credentials[credentials_key]["username"]
        target_date_str = test_leave_date.strftime("%Y-%m-%d")  # ← uses fixture, never hardcoded

        def teardown():
            conn = pymysql.connect(
                host=os.getenv("DB_HOST"),
                port=int(os.getenv("DB_PORT", 3306)),
                user=os.getenv("DB_USERNAME"),
                password=os.getenv("DB_PASSWORD", ""),
                database=os.getenv("DB_DATABASE")
            )
            try:
                cursor = conn.cursor()
                cursor.execute(
                    """
                    DELETE FROM leave_records
                    WHERE emp_id = (
                        SELECT emp_id FROM employees WHERE emp_username = %s
                    )
                    AND date = %s
                    """,
                    (username, target_date_str)
                )
                conn.commit()
            finally:
                conn.close()

        request.addfinalizer(teardown)

    return _cleanup


# -----------------------------
# File VL
# -----------------------------
def test_file_vl_submission_board_success(page, base_url, credentials, test_leave_date, cleanup_auto_approved_leave):
    cleanup_auto_approved_leave("board")

    board = credentials["board"]

    login(page, base_url, board["username"], board["password"])

    print("ACTUAL URL:", page.url)
    expect(page).to_have_url(re.compile(r"user-dashboard\.php"))
    expect(page.locator("#page-heading")).to_contain_text("My Calendar")
    expect(page.locator("header")).to_contain_text("Board of Directors")
    expect(page.get_by_label("Calendar legend")).to_contain_text("✓ Your submissions are auto-approved.")

    navigate_to_date_on_calendar(page, base_url, test_leave_date)
    expect(page.get_by_role("note")).to_contain_text("✓ You are in the Board of Directors group. Your DTR and leave submissions are automatically approved.")
    page.get_by_role("button", name="File Leave Request").click()
    page.get_by_label("Leave Type * (required)").select_option("1")
    page.get_by_role("button", name="Submit Leave").click()

    expect(page.get_by_role("status")).to_contain_text("Vacation Leave submitted and automatically approved")
    page.screenshot(path=f"report/playwright_screenshots/leave_submission/board/vl_submitted_board_{test_leave_date}.png")

    logout(page)


# -----------------------------
# File SL
# -----------------------------
def test_file_sl_submission_board_success(page, base_url, credentials, test_leave_date, cleanup_auto_approved_leave):
    cleanup_auto_approved_leave("board")

    board = credentials["board"]

    login(page, base_url, board["username"], board["password"])

    print("ACTUAL URL:", page.url)
    expect(page).to_have_url(re.compile(r"user-dashboard\.php"))
    expect(page.locator("#page-heading")).to_contain_text("My Calendar")
    expect(page.locator("header")).to_contain_text("Board of Directors")
    expect(page.get_by_label("Calendar legend")).to_contain_text("✓ Your submissions are auto-approved.")

    navigate_to_date_on_calendar(page, base_url, test_leave_date)
    expect(page.get_by_role("note")).to_contain_text("✓ You are in the Board of Directors group. Your DTR and leave submissions are automatically approved.")
    page.get_by_role("button", name="File Leave Request").click()
    page.get_by_label("Leave Type * (required)").select_option("2")
    page.get_by_role("button", name="Submit Leave").click()

    expect(page.get_by_role("status")).to_contain_text("Sick Leave submitted and automatically approved")
    page.screenshot(path=f"report/playwright_screenshots/leave_submission/board/sl_submitted_board_{test_leave_date}.png")

    logout(page)


# -----------------------------
# File EL
# -----------------------------
def test_file_el_submission_board_success(page, base_url, credentials, test_leave_date, cleanup_auto_approved_leave):
    cleanup_auto_approved_leave("board")

    board = credentials["board"]

    login(page, base_url, board["username"], board["password"])

    print("ACTUAL URL:", page.url)
    expect(page).to_have_url(re.compile(r"user-dashboard\.php"))
    expect(page.locator("#page-heading")).to_contain_text("My Calendar")
    expect(page.locator("header")).to_contain_text("Board of Directors")
    expect(page.get_by_label("Calendar legend")).to_contain_text("✓ Your submissions are auto-approved.")

    navigate_to_date_on_calendar(page, base_url, test_leave_date)
    expect(page.get_by_role("note")).to_contain_text("✓ You are in the Board of Directors group. Your DTR and leave submissions are automatically approved.")
    page.get_by_role("button", name="File Leave Request").click()
    page.get_by_label("Leave Type * (required)").select_option("3")
    page.get_by_role("button", name="Submit Leave").click()

    expect(page.get_by_role("status")).to_contain_text("Emergency Leave submitted and automatically approved")
    page.screenshot(path=f"report/playwright_screenshots/leave_submission/board/el_submitted_board_{test_leave_date}.png")

    logout(page)