CREATE TABLE user_roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE
);

INSERT INTO user_roles (role_name) VALUES ('Admin');
INSERT INTO user_roles (role_name) VALUES ('User');