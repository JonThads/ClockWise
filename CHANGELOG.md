## v1.2.7 - 2026-04-03

### Added
- [Tests] Unit and Integration Tests for ClockWise FastAPI Reports
- [Tests] Unit and Integration Report HTML Files
- [Tests] Added Allure Reports
- [Tests] Added Epic, Features, Stories, Titles, Severities, and Tags for Allure Reports
- [pip] Added pydantic, fastapi, allure-reports
- pytest.ini for strict markers
- Folder Organization Restructuring to differentiate Unit, Integration, and E2E Tests

## v1.2.6 - 2026-03-22

### Fixed
- BDay leave not appearing in dropdown
- NoPay balance indicator missing in dropdown
- NoPay accepts submission even with zero remaining balance

### Added
- docker-compose.yml for FastAPI Reports
- Dockerfile.reports
- Modified Base URL to be "app/"

## v1.2.5 - 2026-03-21

### Added
- DTR and Leave Submission FastAPI Reports v1.0.0

## v1.2.4 - 2026-03-21

### Added
- Added No Leave Balance and Executive User Leave Submission Tests

## v1.2.3 - 2026-03-14

### Added
- Ability for users to cancel existing pending DTR and Leave requests
- Automated Leave Submission test cases
- Screenshots captured for every Leave Submission test
- `credentials.json` file

### Refactored
- `conftest.py` refactoring
- Changelog.md semantic change using markdown

### Tests
- Added Leave Submission automated tests
- Implemented screenshot capture for debugging failed tests


## v1.1.2

### Added
#### Roles
- Role-based access level functionality during Employee Account creation
- Created `user_roles` database table

#### Login
- Connected Login Page to the ClockWise database
- Implemented login redirection based on the user's assigned role

### Changed
#### Session
- Moved `session_start()` initialization to the configuration file


## v1.1.1

### Added
- Add New Employees functionality


## v1.1.0

### Added
#### Admin Dashboard
- Employee Management module

#### User Dashboard
- Shift Schedules management
- Leave Types configuration

### Documentation
- Added `CHANGELOG`
- Added `LICENSE`
- Added `README`