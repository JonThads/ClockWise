# tests/accessibility/test_a11y_admin_dashboard.py
import allure
import pytest
from tests.accessibility.helpers import run_axe_scan, login_as_admin


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Accessibility — WCAG 2.1 AA")
@allure.story("Admin Dashboard")
@allure.title("Admin dashboard — Dashboard Overview section passes WCAG")
@allure.description(
    "Logs in as admin and scans the default Dashboard Overview "
    "section including stats cards and recent activity table."
)
@allure.severity(allure.severity_level.CRITICAL)
@allure.tag("a11y", "wcag", "admin-dashboard", "overview", "smoke")
@pytest.mark.a11y
def test_admin_dashboard_overview(page, base_url, credentials):
    login_as_admin(page, base_url, credentials)
    page.wait_for_timeout(500)
    run_axe_scan(page, "Admin Dashboard — Overview")


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Accessibility — WCAG 2.1 AA")
@allure.story("Admin Dashboard")
@allure.title("Admin dashboard — Employee Management section passes WCAG")
@allure.description(
    "Navigates to the Employee Management section and scans "
    "the employee table with Edit and Delete action buttons."
)
@allure.severity(allure.severity_level.CRITICAL)
@allure.tag("a11y", "wcag", "admin-dashboard", "employees")
@pytest.mark.a11y
def test_admin_dashboard_employees(page, base_url, credentials):
    login_as_admin(page, base_url, credentials)
    page.click("a[href='#employees']")
    page.wait_for_selector("#employees.active", state="visible")
    page.wait_for_timeout(500)
    run_axe_scan(page, "Admin Dashboard — Employee Management")


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Accessibility — WCAG 2.1 AA")
@allure.story("Admin Dashboard")
@allure.title("Admin dashboard — Approvals Setup section passes WCAG")
@allure.description(
    "Navigates to Approvals Setup and scans the full employee "
    "assignment table including hierarchy badges, assign "
    "dropdowns, and the assignment summary table."
)
@allure.severity(allure.severity_level.CRITICAL)
@allure.tag("a11y", "wcag", "admin-dashboard", "approvals-setup")
@pytest.mark.a11y
def test_admin_dashboard_approvals_setup(page, base_url, credentials):
    login_as_admin(page, base_url, credentials)
    page.click("a[href='#approvals_setup']")
    page.wait_for_selector("#approvals_setup.active", state="visible")
    page.wait_for_timeout(500)
    run_axe_scan(page, "Admin Dashboard — Approvals Setup")


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Accessibility — WCAG 2.1 AA")
@allure.story("Admin Dashboard")
@allure.title("Admin dashboard — DTR Requests section passes WCAG")
@allure.description(
    "Navigates to the DTR Requests section and scans the "
    "pending DTR approval table with Approve and Decline "
    "action buttons for each request."
)
@allure.severity(allure.severity_level.CRITICAL)
@allure.tag("a11y", "wcag", "admin-dashboard", "dtr-requests")
@pytest.mark.a11y
def test_admin_dashboard_dtr_requests(page, base_url, credentials):
    login_as_admin(page, base_url, credentials)
    page.click("a[href='#dtr']")
    page.wait_for_selector("#dtr.active", state="visible")
    page.wait_for_timeout(500)
    run_axe_scan(page, "Admin Dashboard — DTR Requests")


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Accessibility — WCAG 2.1 AA")
@allure.story("Admin Dashboard")
@allure.title("Admin dashboard — Leave Requests section passes WCAG")
@allure.description(
    "Navigates to the Leave Requests section and scans the "
    "pending leave approval table with Approve and Decline "
    "buttons."
)
@allure.severity(allure.severity_level.CRITICAL)
@allure.tag("a11y", "wcag", "admin-dashboard", "leave-requests")
@pytest.mark.a11y
def test_admin_dashboard_leave_requests(page, base_url, credentials):
    login_as_admin(page, base_url, credentials)
    page.click("a[href='#leave_requests']")
    page.wait_for_selector("#leave_requests.active", state="visible")
    page.wait_for_timeout(500)
    run_axe_scan(page, "Admin Dashboard — Leave Requests")


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Accessibility — WCAG 2.1 AA")
@allure.story("Admin Dashboard")
@allure.title("Admin dashboard — Leave Types section passes WCAG")
@allure.description(
    "Navigates to the Leave Types section and scans the leave "
    "types table with Edit and Delete action buttons."
)
@allure.severity(allure.severity_level.NORMAL)
@allure.tag("a11y", "wcag", "admin-dashboard", "leave-types")
@pytest.mark.a11y
def test_admin_dashboard_leave_types(page, base_url, credentials):
    login_as_admin(page, base_url, credentials)
    page.click("a[href='#leave_types']")
    page.wait_for_selector("#leave_types.active", state="visible")
    page.wait_for_timeout(500)
    run_axe_scan(page, "Admin Dashboard — Leave Types")


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Accessibility — WCAG 2.1 AA")
@allure.story("Admin Dashboard")
@allure.title("Admin dashboard — Shift Schedules section passes WCAG")
@allure.description(
    "Navigates to the Shift Schedules section and scans the "
    "shifts table including the Add Shift button."
)
@allure.severity(allure.severity_level.NORMAL)
@allure.tag("a11y", "wcag", "admin-dashboard", "shifts")
@pytest.mark.a11y
def test_admin_dashboard_shifts(page, base_url, credentials):
    login_as_admin(page, base_url, credentials)
    page.click("a[href='#shifts']")
    page.wait_for_selector("#shifts.active", state="visible")
    page.wait_for_timeout(500)
    run_axe_scan(page, "Admin Dashboard — Shift Schedules")


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Accessibility — WCAG 2.1 AA")
@allure.story("Admin Dashboard")
@allure.title("Admin dashboard — Departments section passes WCAG")
@allure.description(
    "Navigates to the Departments section and scans the "
    "departments table with Edit and Delete buttons."
)
@allure.severity(allure.severity_level.NORMAL)
@allure.tag("a11y", "wcag", "admin-dashboard", "departments")
@pytest.mark.a11y
def test_admin_dashboard_departments(page, base_url, credentials):
    login_as_admin(page, base_url, credentials)
    page.click("a[href='#departments']")
    page.wait_for_selector("#departments.active", state="visible")
    page.wait_for_timeout(500)
    run_axe_scan(page, "Admin Dashboard — Departments")


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Accessibility — WCAG 2.1 AA")
@allure.story("Admin Dashboard")
@allure.title("Admin dashboard — Work Groups section passes WCAG")
@allure.description(
    "Navigates to the Work Groups section and scans the leave "
    "entitlements table with hierarchy badges."
)
@allure.severity(allure.severity_level.NORMAL)
@allure.tag("a11y", "wcag", "admin-dashboard", "work-groups")
@pytest.mark.a11y
def test_admin_dashboard_work_groups(page, base_url, credentials):
    login_as_admin(page, base_url, credentials)
    page.click("a[href='#work_groups']")
    page.wait_for_selector("#work_groups.active", state="visible")
    page.wait_for_timeout(500)
    run_axe_scan(page, "Admin Dashboard — Work Groups")


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Accessibility — WCAG 2.1 AA")
@allure.story("Admin Dashboard")
@allure.title("Admin dashboard — Reports section passes WCAG")
@allure.description(
    "Navigates to the Reports section and scans the coming "
    "soon placeholder card."
)
@allure.severity(allure.severity_level.MINOR)
@allure.tag("a11y", "wcag", "admin-dashboard", "reports")
@pytest.mark.a11y
def test_admin_dashboard_reports(page, base_url, credentials):
    login_as_admin(page, base_url, credentials)
    page.click("a[href='#reports']")
    page.wait_for_selector("#reports.active", state="visible")
    page.wait_for_timeout(500)
    run_axe_scan(page, "Admin Dashboard — Reports")