# src/reports/routers/dtr_report.py
from fastapi import APIRouter, Depends, Query
from sqlalchemy.orm import Session
from sqlalchemy import text
from typing import Optional
from datetime import date
from database import get_db
from schemas.dtr_schema import DTRReportResponse, DTRSummaryStats, DTRRecordOut

router = APIRouter()

@router.get("/dtr", response_model=DTRReportResponse)
def get_dtr_report(
    db: Session = Depends(get_db),
    date_from: Optional[date] = Query(None, description="Start date (YYYY-MM-DD)"),
    date_to:   Optional[date] = Query(None, description="End date (YYYY-MM-DD)"),
    dept_id:   Optional[int]  = Query(None, description="Filter by Department ID"),
    emp_id:    Optional[int]  = Query(None, description="Filter by Employee ID"),
    status:    Optional[str]  = Query(None, description="pending | approved | declined"),
    limit:     int            = Query(20, ge=1, le=100),
    offset:    int            = Query(0, ge=0),
):
    filters = []
    params  = {}

    if date_from:
        filters.append("dr.date >= :date_from")
        params["date_from"] = date_from
    if date_to:
        filters.append("dr.date <= :date_to")
        params["date_to"] = date_to
    if dept_id:
        filters.append("e.dept_id = :dept_id")
        params["dept_id"] = dept_id
    if emp_id:
        filters.append("dr.emp_id = :emp_id")
        params["emp_id"] = emp_id
    if status:
        filters.append("dr.status = :status")
        params["status"] = status

    where = "WHERE " + " AND ".join(filters) if filters else ""

    count_sql = text(f"""
        SELECT
            COUNT(*) AS total,
            SUM(dr.status='pending')  AS pending,
            SUM(dr.status='approved') AS approved,
            SUM(dr.status='declined') AS declined
        FROM dtr_records dr
        JOIN employees e ON e.emp_id = dr.emp_id
        {where}
    """)

    data_sql = text(f"""
        SELECT
            dr.dtr_id,
            dr.emp_id,
            CONCAT(e.emp_first_name,' ',e.emp_last_name) AS employee_name,
            d.dept_name  AS department,
            wg.work_group_name AS work_group,
            ss.shift_sched_name AS shift_name,
            dr.date,
            dr.status,
            dr.submitted_at
        FROM dtr_records dr
        JOIN employees       e  ON e.emp_id          = dr.emp_id
        JOIN departments     d  ON d.dept_id         = e.dept_id
        JOIN work_groups     wg ON wg.work_group_id  = e.work_group_id
        JOIN shift_schedules ss ON ss.shift_sched_id = dr.shift_sched_id
        {where}
        ORDER BY dr.date DESC, dr.submitted_at DESC
        LIMIT :limit OFFSET :offset
    """)

    counts = db.execute(count_sql, params).mappings().one()
    rows   = db.execute(data_sql, {**params, "limit": limit, "offset": offset}).mappings().all()

    stats = DTRSummaryStats(
        total=counts["total"] or 0,
        pending=counts["pending"] or 0,
        approved=counts["approved"] or 0,
        declined=counts["declined"] or 0,
    )

    records = [DTRRecordOut(
        dtr_id=r["dtr_id"],
        emp_id=r["emp_id"],
        employee_name=r["employee_name"],
        department=r["department"],
        work_group=r["work_group"],
        shift_name=r["shift_name"],
        date=r["date"],
        status=r["status"],
        submitted_at=str(r["submitted_at"])
    ) for r in rows]

    return DTRReportResponse(
        stats=stats,
        records=records,
        total_records=counts["total"] or 0,
        page=(offset // limit) + 1,
        limit=limit
    )