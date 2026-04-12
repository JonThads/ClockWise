# src/reports/main.py

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from routers import dtr_report, leave_report

app = FastAPI(
    title="ClockWise Reports API",
    description="ClockWise Reports for DTR and Leave Data",
    version="1.0.0"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(dtr_report.router, prefix="/reports/dtr", tags=["DTR Reports"])
app.include_router(leave_report.router, prefix="/reports/leave", tags=["Leave Reports"])

@app.get("/health")
def health_check():
    return {"status": "ok", "service": "ClockWise Reports API"}