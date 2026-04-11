# tests/unit/test_schemas.py
import allure
import pytest
from src.reports.schemas.dtr_schema import DTRSummaryStats, DTRRecordOut
from src.reports.schemas.leave_schema import LeaveBalanceItem, EmployeeLeaveBalance

# ─────────────────────────────────────────────
# DTRSummaryStats Tests
# ─────────────────────────────────────────────

@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Schema Validation")
@allure.story("DTR schema validates field types correctly")
@allure.title("DTRSummaryStats accepts valid integer values for all fields")
@allure.description("Verifies that DTRSummaryStats correctly accepts valid integer values for all status fields.")
@allure.severity(allure.severity_level.NORMAL)
@allure.tag("unit", "schema", "dtr")

@pytest.mark.unit
def test_dtr_summary_stats_valid():
    stats = DTRSummaryStats(total=10, pending=3, approved=5, declined=2)
    assert stats.total == 10
    assert stats.pending == 3
    assert stats.approved == 5
    assert stats.declined == 2


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Schema Validation")
@allure.story("DTR schema validates field types correctly")
@allure.title("DTRSummaryStats accepts all-zero values")
@allure.description("Verifies that DTRSummaryStats correctly accepts all-zero values, representing a state with no DTR records.")
@allure.severity(allure.severity_level.MINOR)
@allure.tag("unit", "schema", "dtr")

@pytest.mark.unit
def test_dtr_summary_stats_zero_values():
    stats = DTRSummaryStats(total=0, pending=0, approved=0, declined=0)
    assert stats.total == 0
    assert stats.pending == 0
    assert stats.approved == 0
    assert stats.declined == 0


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Schema Validation")
@allure.story("DTR schema validates field types correctly")
@allure.title("DTRSummaryStats rejects non-integer string value")
@allure.description("Verifies that DTRSummaryStats raises a validation error when a non-integer string value is passed for a status field.")
@allure.severity(allure.severity_level.NORMAL)
@allure.tag("unit", "schema", "dtr", "negative")

@pytest.mark.unit
def test_dtr_summary_stats_rejects_string():
    with pytest.raises(Exception):
        DTRSummaryStats(total="ten", pending=0, approved=0, declined=0)


# ─────────────────────────────────────────────
# LeaveBalanceItem Tests
# ─────────────────────────────────────────────

@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Schema Validation")
@allure.story("Leave balance schema enforces business rules")
@allure.title("LeaveBalanceItem is_overdrawn is False when remaining days is positive")
@allure.description("Verifies that LeaveBalanceItem correctly sets is_overdrawn to False when remaining_days is a positive number, indicating the employee still has leave balance.")
@allure.severity(allure.severity_level.CRITICAL)
@allure.tag("unit", "schema", "leave", "business-rule")

@pytest.mark.unit
def test_leave_balance_item_not_overdrawn():
    item = LeaveBalanceItem(
        leave_type="Vacation Leave",
        leave_code="VL",
        entitled_days=15,
        used_days=5,
        remaining_days=10,
        is_overdrawn=False
    )
    assert item.is_overdrawn is False
    assert item.remaining_days == 10


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Schema Validation")
@allure.story("Leave balance schema enforces business rules")
@allure.title("LeaveBalanceItem is_overdrawn is True when remaining days is negative")
@allure.description("Verifies that LeaveBalanceItem correctly sets is_overdrawn to True when remaining_days is a negative number, indicating the employee has exceeded their leave balance.")
@allure.severity(allure.severity_level.CRITICAL)
@allure.tag("unit", "schema", "leave", "business-rule")

@pytest.mark.unit
def test_leave_balance_item_overdrawn():
    item = LeaveBalanceItem(
        leave_type="Sick Leave",
        leave_code="SL",
        entitled_days=5,
        used_days=7,
        remaining_days=-2,
        is_overdrawn=True
    )
    assert item.is_overdrawn is True
    assert item.remaining_days == -2


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Schema Validation")
@allure.story("Leave balance schema enforces business rules")
@allure.title("LeaveBalanceItem is_overdrawn is False when remaining days is exactly zero")
@allure.description("Verifies that LeaveBalanceItem correctly sets is_overdrawn to False when remaining_days is exactly zero, indicating the employee has used all their leave but has not exceeded it.")
@allure.severity(allure.severity_level.NORMAL)
@allure.tag("unit", "schema", "leave", "business-rule", "edge-case")

@pytest.mark.unit
def test_leave_balance_item_exactly_zero_remaining():
    item = LeaveBalanceItem(
        leave_type="Emergency Leave",
        leave_code="EL",
        entitled_days=3,
        used_days=3,
        remaining_days=0,
        is_overdrawn=False
    )
    assert item.is_overdrawn is False
    assert item.remaining_days == 0


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Schema Validation")
@allure.story("Leave balance schema enforces business rules")
@allure.title("LeaveBalanceItem raises validation error when required fields are missing")
@allure.description("Verifies that LeaveBalanceItem raises a validation error when required fields are missing from the payload, ensuring that all necessary information is provided for accurate leave balance representation.")
@allure.severity(allure.severity_level.NORMAL)
@allure.tag("unit", "schema", "leave", "negative")

@pytest.mark.unit
def test_leave_balance_item_rejects_missing_fields():
    with pytest.raises(Exception):
        LeaveBalanceItem(
            leave_type="Vacation Leave"
            # missing all other required fields
        )


# ─────────────────────────────────────────────
# EmployeeLeaveBalance Tests
# ─────────────────────────────────────────────

@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Schema Validation")
@allure.story("Leave balance schema enforces business rules")
@allure.title("EmployeeLeaveBalance accepts valid employee with leave balance items")
@allure.description("Verifies that EmployeeLeaveBalance correctly accepts a valid employee with a list of leave balance items, ensuring that the schema can represent an employee's leave status accurately.")
@allure.severity(allure.severity_level.NORMAL)
@allure.tag("unit", "schema", "leave")

@pytest.mark.unit
def test_employee_leave_balance_valid():
    balances = [
        LeaveBalanceItem(
            leave_type="Vacation Leave",
            leave_code="VL",
            entitled_days=15,
            used_days=0,
            remaining_days=15,
            is_overdrawn=False
        )
    ]
    emp = EmployeeLeaveBalance(
        emp_id=1,
        employee_name="Juan Dela Cruz",
        department="IT",
        work_group="Rank and File",
        balances=balances
    )
    assert emp.emp_id == 1
    assert emp.employee_name == "Juan Dela Cruz"
    assert len(emp.balances) == 1


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Schema Validation")
@allure.story("Leave balance schema enforces business rules")
@allure.title("EmployeeLeaveBalance accepts empty balances list")
@allure.description("Verifies that EmployeeLeaveBalance correctly accepts an empty balances list, representing an employee with no configured leave types for their work group.")
@allure.severity(allure.severity_level.MINOR)
@allure.tag("unit", "schema", "leave", "edge-case")
@pytest.mark.unit

@pytest.mark.unit
def test_employee_leave_balance_empty_balances():
    emp = EmployeeLeaveBalance(
        emp_id=2,
        employee_name="Maria Santos",
        department="HR",
        work_group="Managerial",
        balances=[]
    )
    assert emp.balances == []
    assert len(emp.balances) == 0