# src/reports/routers/leave_report.py
from fastapi import APIRouter, Depends, Query
from sqlalchemy.orm import Session
from sqlalchemy import text
from typing import Optional
from database import get_db
from schemas.leave_schema import LeaveReportResponse, EmployeeLeaveBalance, LeaveBalanceItem

router = APIRouter()

@router.get("/leave-balance", response_model=LeaveReportResponse)
def get_leave_balance(
    db: Session = Depends(get_db),
    dept_id:       Optional[int] = Query(None, description="Filter by Department ID"),
    work_group_id: Optional[int] = Query(None, description="Filter by Work Group ID"),
    emp_id:        Optional[int] = Query(None, description="Filter by Employee ID"),
):
    filters = []
    params  = {}

    if dept_id:
        filters.append("e.dept_id = :dept_id")
        params["dept_id"] = dept_id
    if work_group_id:
        filters.append("e.work_group_id = :wg_id")
        params["wg_id"] = work_group_id
    if emp_id:
        filters.append("e.emp_id = :emp_id")
        params["emp_id"] = emp_id

    where = "WHERE " + " AND ".join(filters) if filters else ""

    emp_sql = text(f"""
        SELECT
            e.emp_id,
            CONCAT(e.emp_first_name,' ',e.emp_last_name) AS employee_name,
            d.dept_name,
            wg.work_group_name,
            wgl.leave_type_id,
            lt.leave_type_name,
            lt.leave_type_code,
            wgl.leave_type_quantity AS entitled
        FROM employees e
        JOIN departments      d   ON d.dept_id          = e.dept_id
        JOIN work_groups      wg  ON wg.work_group_id   = e.work_group_id
        JOIN work_group_leaves wgl ON wgl.work_group_name = wg.work_group_name
        JOIN leave_types      lt  ON lt.leave_type_id   = wgl.leave_type_id
        {where}
        ORDER BY d.dept_name, employee_name, lt.leave_type_code
    """)

    used_sql = text(f"""
        SELECT lr.emp_id, lr.leave_type_id, COUNT(*) AS used_days
        FROM leave_records lr
        JOIN employees e ON e.emp_id = lr.emp_id
        {where}
        AND lr.status = 'approved'
        GROUP BY lr.emp_id, lr.leave_type_id
    """)

    rows      = db.execute(emp_sql, params).mappings().all()
    used_rows = db.execute(used_sql, params).mappings().all()

    usage_map = {(r["emp_id"], r["leave_type_id"]): r["used_days"] for r in used_rows}

    emp_map = {}
    for r in rows:
        eid = r["emp_id"]
        if eid not in emp_map:
            emp_map[eid] = {
                "emp_id": eid,
                "employee_name": r["employee_name"],
                "department": r["dept_name"],
                "work_group": r["work_group_name"],
                "balances": []
            }
        entitled  = r["entitled"]
        used      = usage_map.get((eid, r["leave_type_id"]), 0)
        remaining = entitled - used
        emp_map[eid]["balances"].append(LeaveBalanceItem(
            leave_type=r["leave_type_name"],
            leave_code=r["leave_type_code"],
            entitled_days=entitled,
            used_days=used,
            remaining_days=remaining,
            is_overdrawn=remaining < 0
        ))

    employees = [EmployeeLeaveBalance(**v) for v in emp_map.values()]
    return LeaveReportResponse(total_employees=len(employees), employees=employees)