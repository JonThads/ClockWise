# src/reports/schemas/dtr_schema.py

from pydantic import BaseModel
from typing import Optional
from datetime import date

class DTRRecordOut(BaseModel):
    dtr_id: int
    emp_id: int
    employee_name: str
    department: str
    work_group: str
    shift_name: str
    date: date
    status: str
    submitted_at: str

    class Config:
        from_attributes = True

class DTRSummaryStats(BaseModel):
    total: int
    pending: int
    approved: int
    declined: int

class DTRReportResponse(BaseModel):
    stats: DTRSummaryStats
    records: list[DTRRecordOut]
    total_records: int
    page: int
    limit: int