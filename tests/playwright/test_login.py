import re
from playwright.sync_api import expect

def test_admin_login_success(page, base_url):
    # Navigate to ClockWise Login Page
    page.goto(f"{base_url}/login.php")

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

def test_user_login_success(page, base_url):
    # Navigate to ClockWise Login Page
    page.goto(f"{base_url}/login.php")

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