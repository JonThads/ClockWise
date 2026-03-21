# src/reports/schemas/leave_schema.py

from pydantic import BaseModel
from typing import Optional

class LeaveBalanceItem(BaseModel):
    leave_type: str
    leave_code: str
    entitled_days: int
    used_days: int
    remaining_days: int
    is_overdrawn: bool

class EmployeeLeaveBalance(BaseModel):
    emp_id: int
    employee_name: str
    department: str
    work_group: str
    balances: list[LeaveBalanceItem]

class LeaveReportResponse(BaseModel):
    total_employees: int
    employees: list[EmployeeLeaveBalance]