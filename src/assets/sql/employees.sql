INSERT INTO employees (
    emp_first_name,
    emp_last_name,
    emp_email,
    emp_username,
    dept_id,
    work_group_id,
    shift_sched_id,
    created_at
) VALUES
('Alice', 'Santos', 'alice.santos@example.com', 'asantos', 1, 1, 1, NOW()),
('Brian', 'Lopez', 'brian.lopez@example.com', 'blopez', 2, 1, 2, NOW()),
('Carla', 'Reyes', 'carla.reyes@example.com', 'creyes', 1, 2, 1, NOW()),
('David', 'Cruz', 'david.cruz@example.com', 'dcruz', 3, 2, 3, NOW()),
('Ella', 'Garcia', 'ella.garcia@example.com', 'egarcia', 2, 3, 2, NOW()),
('Francis', 'Torres', 'francis.torres@example.com', 'ftorres', 1, 3, 1, NOW()),
('Grace', 'Navarro', 'grace.navarro@example.com', 'gnavarro', 3, 1, 3, NOW()),
('Henry', 'Mendoza', 'henry.mendoza@example.com', 'hmendoza', 2, 2, 2, NOW()),
('Isabel', 'Flores', 'isabel.flores@example.com', 'iflores', 1, 1, 1, NOW()),
('James', 'Ramos', 'james.ramos@example.com', 'jramos', 3, 3, 3, NOW());