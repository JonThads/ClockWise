# tests/integration/fastapi/test_leave_report.py
import allure
import pytest


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Reports API - Leave")
@allure.story("Admin can retrieve employee leave balances")
@allure.title("Leave balance endpoint returns HTTP 200")
@allure.description("Verifies that the leave balance endpoint responds with HTTP 200 OK when called with no filters applied.")
@allure.severity(allure.severity_level.BLOCKER)
@allure.tag("leave", "api", "smoke")

@pytest.mark.integration
def test_leave_balance_returns_200(client):
    response = client.get("/reports/leave/leave-balance")
    assert response.status_code == 200


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Reports API - Leave")
@allure.story("Admin can retrieve employee leave balances")
@allure.title("Leave balance response contains all required top-level keys")
@allure.description("Verifies that the leave balance response contains all expected top-level keys as defined in LeaveReportResponse schema.")
@allure.severity(allure.severity_level.CRITICAL)
@allure.tag("leave", "api", "schema")

@pytest.mark.integration
def test_leave_balance_response_shape(client):
    response = client.get("/reports/leave/leave-balance")
    data = response.json()
    assert "total_employees" in data
    assert "employees" in data


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Reports API - Leave")
@allure.story("Admin can retrieve employee leave balances")
@allure.title("Each employee object contains all required fields")
@allure.description("Verifies that each employee object in the response contains all required fields as defined in the EmployeeLeaveBalance schema. Only runs the assertion if at least one employee exists.")
@allure.severity(allure.severity_level.CRITICAL)
@allure.tag("leave", "api", "schema")

@pytest.mark.integration
def test_leave_balance_employee_fields(client):
    response = client.get("/reports/leave/leave-balance")
    data = response.json()
    if data["employees"]:
        emp = data["employees"][0]
        assert "emp_id" in emp
        assert "employee_name" in emp
        assert "department" in emp
        assert "work_group" in emp
        assert "balances" in emp


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Reports API - Leave")
@allure.story("Admin can retrieve employee leave balances")
@allure.title("Each leave balance item contains all required fields")
@allure.description("Verifies that each leave balance item within an employee record contains all required fields as defined in the LeaveBalanceItem schema. Only runs the assertion if at least one employee with balances exists.")
@allure.severity(allure.severity_level.CRITICAL)
@allure.tag("leave", "api", "schema")

@pytest.mark.integration
def test_leave_balance_item_fields(client):
    response = client.get("/reports/leave/leave-balance")
    data = response.json()
    if data["employees"]:
        balance = data["employees"][0]["balances"][0]
        assert "leave_type" in balance
        assert "leave_code" in balance
        assert "entitled_days" in balance
        assert "used_days" in balance
        assert "remaining_days" in balance
        assert "is_overdrawn" in balance


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Reports API - Leave")
@allure.story("Leave balance calculates remaining days correctly")
@allure.title("Remaining days equals entitled days minus used days for all employees")
@allure.description("Verifies the mathematical correctness of remaining_days across all employees and all leave types in the response.")
@allure.severity(allure.severity_level.CRITICAL)
@allure.tag("leave", "api", "calculation", "regression")

@pytest.mark.integration
def test_leave_balance_remaining_days_calculation(client):
    response = client.get("/reports/leave/leave-balance")
    data = response.json()
    for emp in data["employees"]:
        for bal in emp["balances"]:
            expected_remaining = bal["entitled_days"] - bal["used_days"]
            assert bal["remaining_days"] == expected_remaining


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Reports API - Leave")
@allure.story("Leave balance calculates remaining days correctly")
@allure.title("is_overdrawn flag is True only when remaining days is negative")
@allure.description("Verifies that the is_overdrawn flag is correctly set based on remaining_days. It must be True only when remaining_days is negative, and False in all other cases.")
@allure.severity(allure.severity_level.CRITICAL)
@allure.tag("leave", "api", "calculation", "regression")

@pytest.mark.integration
def test_leave_balance_is_overdrawn_flag(client):
    response = client.get("/reports/leave/leave-balance")
    data = response.json()
    for emp in data["employees"]:
        for bal in emp["balances"]:
            if bal["remaining_days"] < 0:
                assert bal["is_overdrawn"] is True
            else:
                assert bal["is_overdrawn"] is False


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Reports API - Leave")
@allure.story("Admin can retrieve employee leave balances")
@allure.title("total_employees count matches the length of the employees list")
@allure.description("Verifies that the total_employees count in the response accurately reflects the actual number of employee objects returned in the employees list.")
@allure.severity(allure.severity_level.NORMAL)
@allure.tag("leave", "api", "schema")

@pytest.mark.integration
def test_leave_balance_total_employees_matches_list(client):
    response = client.get("/reports/leave/leave-balance")
    data = response.json()
    assert data["total_employees"] == len(data["employees"])


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Reports API - Leave")
@allure.story("Admin can retrieve employee leave balances")
@allure.title("Filtering by emp_id returns exactly one matching employee")
@allure.description("Verifies that when filtering the leave balance report by emp_id, the response contains exactly one employee whose emp_id matches the requested value. This confirms that the filter correctly scopes results to a single employee.")
@allure.severity(allure.severity_level.NORMAL)
@allure.tag("leave", "api", "filter")

@pytest.mark.integration
def test_leave_balance_filter_by_emp_id(client):
    response = client.get("/reports/leave/leave-balance")
    data = response.json()
    if data["employees"]:
        emp_id = data["employees"][0]["emp_id"]
        filtered = client.get(
            "/reports/leave/leave-balance",
            params={"emp_id": emp_id}
        )
        assert filtered.status_code == 200
        filtered_data = filtered.json()
        assert filtered_data["total_employees"] == 1
        assert filtered_data["employees"][0]["emp_id"] == emp_id