# tests/integration/fastapi/test_dtr_report.py
import allure
import pytest


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Reports API - DTR")
@allure.story("DTR report returns correct response structure")
@allure.title("FastAPI health check returns status ok")
@allure.description("Verifies that the FastAPI server is running and reachable. This is the first gate — if this fails, all other integration tests will fail too.")
@allure.severity(allure.severity_level.BLOCKER)
@allure.tag("health", "api", "smoke")

@pytest.mark.integration
def test_health_check(client):
    response = client.get("/health")
    assert response.status_code == 200
    assert response.json()["status"] == "ok"


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Reports API - DTR")
@allure.story("DTR report returns correct response structure")
@allure.title("DTR report endpoint returns HTTP 200")
@allure.description("Verifies that the DTR report endpoint responds with HTTP 200 OK when called with no filters applied.")
@allure.severity(allure.severity_level.CRITICAL)
@allure.tag("dtr", "api", "smoke")

@pytest.mark.integration
def test_dtr_report_returns_200(client):
    response = client.get("/reports/dtr/dtr")
    assert response.status_code == 200


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Reports API - DTR")
@allure.story("DTR report returns correct response structure")
@allure.title("DTR report response contains all required top-level keys")
@allure.description("Verifies that the DTR report response contains all expected top-level keys as defined in DTRReportResponse schema.")
@allure.severity(allure.severity_level.CRITICAL)
@allure.tag("dtr", "api", "schema")

@pytest.mark.integration
def test_dtr_report_response_shape(client):
    response = client.get("/reports/dtr/dtr")
    data = response.json()
    assert "stats" in data
    assert "records" in data
    assert "total_records" in data
    assert "page" in data
    assert "limit" in data


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Reports API - DTR")
@allure.story("DTR report returns correct response structure")
@allure.title("DTR stats object contains all four status count fields")
@allure.description("Verifies that the stats object inside the DTR report contains all four required status count fields: total, pending, approved, and declined.")
@allure.severity(allure.severity_level.NORMAL)
@allure.tag("dtr", "api", "schema")

@pytest.mark.integration
def test_dtr_stats_fields(client):
    response = client.get("/reports/dtr/dtr")
    stats = response.json()["stats"]
    assert "total" in stats
    assert "pending" in stats
    assert "approved" in stats
    assert "declined" in stats


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Reports API - DTR")
@allure.story("Admin can retrieve DTR records with filters")
@allure.title("Filtering by status=pending returns only pending DTR records")
@allure.description("Verifies that when filtering the DTR report by status=pending, only records with a pending status are returned. No approved or declined records should be included in the results.")
@allure.severity(allure.severity_level.CRITICAL)
@allure.tag("dtr", "api", "filter", "regression")

@pytest.mark.integration
def test_dtr_filter_by_status_pending(client):
    response = client.get("/reports/dtr/dtr", params={"status": "pending"})
    assert response.status_code == 200
    data = response.json()
    for record in data["records"]:
        assert record["status"] == "pending"


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Reports API - DTR")
@allure.story("Admin can retrieve DTR records with filters")
@allure.title("Filtering by status=approved returns only approved DTR records")
@allure.description("Verifies that when filtering the DTR report by status=approved, only records with an approved status are returned. No pending or declined records should be included in the results.")
@allure.severity(allure.severity_level.CRITICAL)
@allure.tag("dtr", "api", "filter", "regression")

@pytest.mark.integration
def test_dtr_filter_by_status_approved(client):
    response = client.get("/reports/dtr/dtr", params={"status": "approved"})
    assert response.status_code == 200
    data = response.json()
    for record in data["records"]:
        assert record["status"] == "approved"


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Reports API - DTR")
@allure.story("Admin can retrieve DTR records with filters")
@allure.title("Filtering by status=declined returns only declined DTR records")
@allure.description("Verifies that when filtering the DTR report by status=declined, only records with a declined status are returned. No pending or approved records should be included in the results.")
@allure.severity(allure.severity_level.CRITICAL)
@allure.tag("dtr", "api", "filter", "regression")

@pytest.mark.integration
def test_dtr_filter_by_status_declined(client):
    response = client.get("/reports/dtr/dtr", params={"status": "declined"})
    assert response.status_code == 200
    data = response.json()
    for record in data["records"]:
        assert record["status"] == "declined"


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Reports API - DTR")
@allure.story("Admin can retrieve DTR records with filters")
@allure.title("Pagination limit correctly restricts number of records returned")
@allure.description("Verifies that the limit query parameter correctly restricts the number of records returned, and that the limit value is reflected in the response body.")
@allure.severity(allure.severity_level.NORMAL)
@allure.tag("dtr", "api", "pagination")

@pytest.mark.integration
def test_dtr_pagination_limit(client):
    response = client.get("/reports/dtr/dtr", params={"limit": 5, "offset": 0})
    assert response.status_code == 200
    data = response.json()
    assert len(data["records"]) <= 5
    assert data["limit"] == 5


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Reports API - DTR")
@allure.story("Admin can retrieve DTR records with filters")
@allure.title("Date range filter returns only records within specified range")
@allure.description("Verifies that filtering the DTR report by a specific date range (using date_from and date_to) returns only records that fall within that range. Records outside the specified dates should not be included in the results.")
@allure.severity(allure.severity_level.NORMAL)
@allure.tag("dtr", "api", "filter", "date-range")

@pytest.mark.integration
def test_dtr_filter_by_date_range(client):
    response = client.get("/reports/dtr/dtr", params={
        "date_from": "2025-01-01",
        "date_to": "2025-12-31"
    })
    assert response.status_code == 200
    data = response.json()
    for record in data["records"]:
        assert "2025" in record["date"]


@allure.epic("ClockWise DTR & Leave Management System")
@allure.feature("Reports API - DTR")
@allure.story("DTR report returns correct response structure")
@allure.title("Each DTR record contains all required fields from DTRRecordOut schema")
@allure.description("Verifies that each DTR record in the response contains all required fields as defined in the DTRRecordOut schema. This test only runs the assertions if at least one record exists in the response to avoid false negatives when the database is empty.")
@allure.severity(allure.severity_level.NORMAL)
@allure.tag("dtr", "api", "schema")

@pytest.mark.integration
def test_dtr_record_fields(client):
    response = client.get("/reports/dtr/dtr", params={"limit": 1})
    data = response.json()
    if data["records"]:
        record = data["records"][0]
        assert "dtr_id" in record
        assert "emp_id" in record
        assert "employee_name" in record
        assert "department" in record
        assert "work_group" in record
        assert "shift_name" in record
        assert "date" in record
        assert "status" in record
        assert "submitted_at" in record