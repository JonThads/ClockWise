import re
from playwright.sync_api import expect

def test_admin_login_success(page, base_url):
    # Navigate to ClockWise Login Page
    page.goto(f"{base_url}login.php")

    # Perform Login
    page.get_by_role("textbox", name="Username").fill("aaadmin2018")
    page.get_by_role("textbox", name="Password").fill("admin123!")
    page.get_by_role("button", name="Login").click()

    # Assertions after Login
    print("ACTUAL URL:", page.url)
    expect(page).to_have_url(re.compile(r"admin-dashboard\.php"))
    expect(page.get_by_role("main")).to_contain_text("Admin Dashboard")
    expect(page.locator("#dashboard")).to_contain_text("Dashboard Overview")
    expect(page.get_by_role("main")).to_contain_text("Administrator")
    expect(page.get_by_role("complementary")).to_contain_text("ClockWise")

def test_user_login_success(page, base_url):
    # Navigate to ClockWise Login Page
    page.goto(f"{base_url}login.php")

    # Perform Login
    page.get_by_role("textbox", name="Username").fill("aggo2026")
    page.get_by_role("textbox", name="Password").fill("TestPass123!")
    page.get_by_role("button", name="Login").click()

    # Assertions after Login
    print("ACTUAL URL:", page.url)
    expect(page).to_have_url(re.compile(r"user-dashboard\.php"))
    expect(page.get_by_role("main")).to_contain_text("My Calendar")
    expect(page.get_by_role("complementary")).to_contain_text("ClockWise")
    expect(page.locator("#calendar")).to_contain_text("Shift Codes:")
    expect(page.locator("#calendar")).to_contain_text("Leave Codes:")