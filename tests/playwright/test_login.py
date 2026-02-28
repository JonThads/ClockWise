import re
from playwright.sync_api import expect

def test_admin_login(page, base_url):
    page.goto(f"{base_url}/login.php")
    page.fill("input[name='username']", "aaadmin2018")
    page.fill("input[name='password']", "admin123!")
    page.click("button[type='submit']")

    print("ACTUAL URL:", page.url)
    expect(page).to_have_url(re.compile(r"admin-dashboard\.php"))
    expect(page.locator("main h1.page-title")).to_have_text("Admin Dashboard")