CREATE TABLE work_groups (
    work_group_id INT AUTO_INCREMENT PRIMARY KEY,
    work_group_name VARCHAR(50) NOT NULL
);

INSERT INTO work_groups (work_group_name)
VALUES
	('Rank and File'),
    ('Supervisory'),
    ('Managerial'),
    ('Executive'),
    ('Administrative'),
    ('Board of Directors');