import re
import allure
from playwright.sync_api import expect
import pytest


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Authentication")
@allure.story("User can log in with valid credentials")
@allure.description("Verifies that an Admin User can log in and is redirected to the Admin Dashboard with correct UI elements visible.")
@allure.severity(allure.severity_level.CRITICAL)
@allure.tag("login", "admin", "smoke")

@pytest.mark.e2e
def test_admin_login_success(page, base_url):
    # Navigate to ClockWise Login Page
    page.goto(f"{base_url}login.php")

    # Perform Login
    page.get_by_role("textbox", name="Username").fill("aaadmin2018")
    page.get_by_role("textbox", name="Password").fill("admin123!")
    page.get_by_role("button", name="Log In").click()

    # Assertions after Login
    print("ACTUAL URL:", page.url)
    expect(page).to_have_url(re.compile(r"admin-dashboard\.php"))
    expect(page.locator("#page-heading")).to_contain_text("Dashboard Overview")
    expect(page.locator("header")).to_contain_text("Administrator")
    expect(page.locator("#dashboard-heading")).to_contain_text("Dashboard Overview")
    expect(page.get_by_role("paragraph")).to_contain_text("ClockWise")
    expect(page.get_by_role("navigation", name="Main navigation")).to_be_visible()


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Authentication")
@allure.story("User can log in with valid credentials")
@allure.title("Rank and File user login redirects to User Dashboard")
@allure.description("Verifies that a Rank and File employee can log in and sees the calendar, shift codes, leave codes, and leave balances.")
@allure.severity(allure.severity_level.CRITICAL)
@allure.tag("login", "rank-and-file", "smoke")

@pytest.mark.e2e
def test_user_rnf_login_success(page, base_url):
    # Navigate to ClockWise Login Page
    page.goto(f"{base_url}login.php")

    # Perform Login
    page.get_by_role("textbox", name="Username").fill("jklo1980")
    page.get_by_role("textbox", name="Password").fill("TestPass123!")
    page.get_by_role("button", name="Log In").click()

    # Assertions after Login
    print("ACTUAL URL:", page.url)
    expect(page).to_have_url(re.compile(r"user-dashboard\.php"))
    expect(page.locator("#page-heading")).to_contain_text("My Calendar")
    expect(page.locator("header")).to_contain_text("Rank and File")
    expect(page.get_by_label("Main navigation").get_by_role("paragraph")).to_contain_text("ClockWise")
    expect(page.get_by_label("Calendar legend")).to_contain_text("Shift Codes")
    expect(page.get_by_label("Calendar legend")).to_contain_text("Leave Codes")
    expect(page.get_by_label("Calendar legend")).to_contain_text("Your Leave Balances")


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Authentication")
@allure.story("User can log in with valid credentials")
@allure.title("Supervisory user login redirects to User Dashboard")
@allure.description("Verifies that a Supervisory employee can log in and sees the calendar, shift codes, leave codes, and leave balances.")
@allure.severity(allure.severity_level.CRITICAL)
@allure.tag("login", "supervisory", "smoke")

@pytest.mark.e2e
def test_user_supervisory_login_success(page, base_url):
    # Navigate to ClockWise Login Page
    page.goto(f"{base_url}login.php")

    # Perform Login
    page.get_by_role("textbox", name="Username").fill("kmasagi1993")
    page.get_by_role("textbox", name="Password").fill("qwebuh456")
    page.get_by_role("button", name="Log In").click()

    # Assertions after Login
    print("ACTUAL URL:", page.url)
    expect(page).to_have_url(re.compile(r"user-dashboard\.php"))
    expect(page.locator("#page-heading")).to_contain_text("My Calendar")
    expect(page.locator("header")).to_contain_text("Supervisory")
    expect(page.get_by_label("Main navigation").get_by_role("paragraph")).to_contain_text("ClockWise")
    expect(page.get_by_label("Calendar legend")).to_contain_text("Shift Codes")
    expect(page.get_by_label("Calendar legend")).to_contain_text("Leave Codes")
    expect(page.get_by_label("Calendar legend")).to_contain_text("Your Leave Balances")


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Authentication")
@allure.story("User can log in with valid credentials")
@allure.title("Managerial user login redirects to User Dashboard")
@allure.description("Verifies that a Managerial employee can log in and sees the calendar, shift codes, leave codes, and leave balances.")
@allure.severity(allure.severity_level.CRITICAL)
@allure.tag("login", "managerial", "smoke")

@pytest.mark.e2e
def test_user_managerial_login_success(page, base_url):
    # Navigate to ClockWise Login Page
    page.goto(f"{base_url}login.php")

    # Perform Login
    page.get_by_role("textbox", name="Username").fill("aggo2026")
    page.get_by_role("textbox", name="Password").fill("TestPass123!")
    page.get_by_role("button", name="Log In").click()

    # Assertions after Login
    print("ACTUAL URL:", page.url)
    expect(page).to_have_url(re.compile(r"user-dashboard\.php"))
    expect(page.locator("#page-heading")).to_contain_text("My Calendar")
    expect(page.locator("header")).to_contain_text("Managerial")
    expect(page.get_by_label("Main navigation").get_by_role("paragraph")).to_contain_text("ClockWise")
    expect(page.get_by_label("Calendar legend")).to_contain_text("Shift Codes")
    expect(page.get_by_label("Calendar legend")).to_contain_text("Leave Codes")
    expect(page.get_by_label("Calendar legend")).to_contain_text("Your Leave Balances")


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Authentication")
@allure.story("User can log in with valid credentials")
@allure.title("Executive user login is auto-approved and redirects to User Dashboard")
@allure.description("Verifies that an Executive employee can log in and sees the calendar, shift codes, leave codes, and leave balances.")
@allure.severity(allure.severity_level.CRITICAL)
@allure.tag("login", "executive", "smoke", "auto-approved")

@pytest.mark.e2e
def test_user_executive_login_success(page, base_url):
    # Navigate to ClockWise Login Page
    page.goto(f"{base_url}login.php")

    # Perform Login
    page.get_by_role("textbox", name="Username").fill("kmja1983")
    page.get_by_role("textbox", name="Password").fill("TestPass123!")
    page.get_by_role("button", name="Log In").click()

    # Assertions after Login
    print("ACTUAL URL:", page.url)
    expect(page).to_have_url(re.compile(r"user-dashboard\.php"))
    expect(page.locator("#page-heading")).to_contain_text("My Calendar")
    expect(page.locator("header")).to_contain_text("Executive")
    expect(page.get_by_label("Main navigation").get_by_role("paragraph")).to_contain_text("ClockWise")
    expect(page.get_by_label("Calendar legend")).to_contain_text("Shift Codes")
    expect(page.get_by_label("Calendar legend")).to_contain_text("Leave Codes")
    expect(page.get_by_label("Calendar legend")).to_contain_text("Your Leave Balances")


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Authentication")
@allure.story("User can log in with valid credentials")
@allure.title("Administrative user login is auto-approved and redirects to User Dashboard")
@allure.description("Verifies that an Administrative employee can log in and sees the calendar, shift codes, leave codes, and leave balances.")
@allure.severity(allure.severity_level.CRITICAL)
@allure.tag("login", "administrative", "smoke", "auto-approved")

@pytest.mark.e2e
def test_user_administrative_login_success(page, base_url):
    # Navigate to ClockWise Login Page
    page.goto(f"{base_url}login.php")

    # Perform Login
    page.get_by_role("textbox", name="Username").fill("admin1990")
    page.get_by_role("textbox", name="Password").fill("iop465bhu")
    page.get_by_role("button", name="Log In").click()

    # Assertions after Login
    print("ACTUAL URL:", page.url)
    expect(page).to_have_url(re.compile(r"user-dashboard\.php"))
    expect(page.locator("#page-heading")).to_contain_text("My Calendar")
    expect(page.locator("header")).to_contain_text("Administrative")
    expect(page.get_by_label("Main navigation").get_by_role("paragraph")).to_contain_text("ClockWise")
    expect(page.get_by_label("Calendar legend")).to_contain_text("Shift Codes")
    expect(page.get_by_label("Calendar legend")).to_contain_text("Leave Codes")
    expect(page.get_by_label("Calendar legend")).to_contain_text("Your Leave Balances")


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Authentication")
@allure.story("User can log in with valid credentials")
@allure.title("Board of Directors user login is auto-approved and redirects to User Dashboard")
@allure.description("Verifies that a Board of Director can log in and sees the calendar, shift codes, leave codes, and leave balances.")
@allure.severity(allure.severity_level.CRITICAL)
@allure.tag("login", "board", "smoke", "auto-approved")

@pytest.mark.e2e
def test_user_board_login_success(page, base_url):
    # Navigate to ClockWise Login Page
    page.goto(f"{base_url}login.php")

    # Perform Login
    page.get_by_role("textbox", name="Username").fill("jgmontes1990")
    page.get_by_role("textbox", name="Password").fill("iop465bhu")
    page.get_by_role("button", name="Log In").click()

    # Assertions after Login
    print("ACTUAL URL:", page.url)
    expect(page).to_have_url(re.compile(r"user-dashboard\.php"))
    expect(page.locator("#page-heading")).to_contain_text("My Calendar")
    expect(page.locator("header")).to_contain_text("Board of Directors")
    expect(page.get_by_label("Main navigation").get_by_role("paragraph")).to_contain_text("ClockWise")
    expect(page.get_by_label("Calendar legend")).to_contain_text("Shift Codes")
    expect(page.get_by_label("Calendar legend")).to_contain_text("Leave Codes")
    expect(page.get_by_label("Calendar legend")).to_contain_text("Your Leave Balances")