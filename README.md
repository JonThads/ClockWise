# ⏰ ClockWise - DTR & Leave Management System

ClockWise is a web-based application designed to streamline **Daily Time Record (DTR)** Tracking and **Leave Management** for stakeholders.  
It provides HR Teams and Managers with an efficient way to monitor attendance, manage shift schedules, and process leave requests — all in one centralized platform.

---

## 🚀 Features

- **Employee Management**
  - Add, update, and manage employee records
  - Assign departments, work groups, and shift schedules

- **Shift Scheduling**
  - Define multiple shift types (Morning, Afternoon, Night, Flexible)
  - Dynamic shift codes (`M`, `A`, `N`, `F`) with start and end times
  - Auto-generated legends for easy reference

- **Daily Time Record (DTR)**
  - Track employee clock-in and clock-out times
  - Generate attendance reports
  - (Under Development) Handle overtime and undertime calculations

- **Leave Management**
  - Support for multiple leave types (Vacation, Sick, Emergency, Birthday, Study, No Pay)
  - Leave request submission and approval workflow
  - Leave balance tracking per employee

- **User Dashboard**
  - Personalized view of schedules, attendance, and leave status
  - HR/Admin dashboard for monitoring organization-wide data

---

## ♿ Accessibility

ClockWise follows **WCAG 2.1 Level AA** guidelines where feasible. A full accessibility report, implementation notes, and testing checklist are available in **ACCESSIBILITY.md**.

---

## 🛠️ Tech Stack

- **Backend:** PHP (PDO for database interaction)
- **Frontend:** HTML, CSS, JavaScript
- **Database:** MySQL (with normalized tables for employees, departments, work groups, shift schedules, and leaves)
- **Server:** XAMPP / Apache (For Local Deployment and Testing)
- **Environment:** Docker
- **CI/CD:** GitHub Actions
- **Tests:** Unit / Integration / E2E / Accessibility
- **Accessibility:** Axe-Core
- **Reports:** Python / FastAPI / Allure Reports

---

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

"Kapag may alitaptap, tumingin sa mga ulap."