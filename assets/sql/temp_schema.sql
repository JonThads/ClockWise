CREATE TABLE shift_schedules (
    shift_sched_id INT AUTO_INCREMENT PRIMARY KEY,
    shift_sched_name VARCHAR(50) NOT NULL UNIQUE,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE leave_types (
    leave_type_id INT AUTO_INCREMENT PRIMARY KEY,
    leave_type_name VARCHAR(50) NOT NULL UNIQUE,
    leave_type_code VARCHAR(5) NOT NULL UNIQUE
);

CREATE TABLE departments (
    dept_id INT AUTO_INCREMENT PRIMARY KEY,
    dept_name VARCHAR(50) NOT NULL UNIQUE,
    dept_desc VARCHAR(128) NOT NULL
);

CREATE TABLE work_groups (
    work_group_id INT AUTO_INCREMENT PRIMARY KEY,
    work_group_name VARCHAR(50) NOT NULL,
    leave_type_id INT NOT NULL,
    leave_type_quantity INT NOT NULL,
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(leave_type_id)
);

CREATE TABLE employees (
    emp_id INT AUTO_INCREMENT PRIMARY KEY,
    emp_first_name VARCHAR(128) NOT NULL,
    emp_last_name VARCHAR(128) NOT NULL,
    emp_email VARCHAR(128) NOT NULL,
    emp_username VARCHAR(128) NOT NULL UNIQUE,
    emp_password VARCHAR(256) NOT NULL,

    dept_id INT NOT NULL,
    work_group_id INT NOT NULL,
    shift_sched_id INT NOT NULL,
    approver_id INT NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (dept_id) REFERENCES departments(dept_id),
    FOREIGN KEY (work_group_id) REFERENCES work_groups(work_group_id),
    FOREIGN KEY (shift_sched_id) REFERENCES shift_schedules(shift_sched_id)
);

CREATE TABLE leave_records (
    leave_rec_id INT AUTO_INCREMENT PRIMARY KEY,
    emp_id INT NOT NULL,
    leave_type_id INT NOT NULL,
    date DATE NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (emp_id) REFERENCES employees(emp_id),
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(leave_type_id)
);