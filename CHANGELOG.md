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