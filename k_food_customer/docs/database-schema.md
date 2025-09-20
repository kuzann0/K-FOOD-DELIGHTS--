# KfoodDelights Database Schema

## users Table

```
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    profile_picture VARCHAR(255) DEFAULT 'default.jpg',
    account_status VARCHAR(20) DEFAULT 'active',
    role_id INT NOT NULL,
    login_attempts INT DEFAULT 0,
    last_login TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

- `user_id`: Primary key, unique user identifier
- `username`: Unique username
- `password`: Hashed password
- `email`: Unique email address
- `account_status`: 'active', 'inactive', or 'banned'
- `role_id`: User role (1=Customer, 2=Admin, 3=Crew, etc.)
- `login_attempts`: For security lockout
- `last_login`: Timestamp of last successful login

See `/sql/create_users_table.sql` for full details.
