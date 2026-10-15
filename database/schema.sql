-- MySQL 8 / MariaDB 10.6+. Импортировать в выбранную пустую базу konferencii.
CREATE TABLE users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 login VARCHAR(50) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 full_name VARCHAR(150) NOT NULL,
 phone VARCHAR(15) NOT NULL,
 email VARCHAR(190) NOT NULL,
 role VARCHAR(10) NOT NULL DEFAULT 'user',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT users_role CHECK (role IN ('user','admin'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE rooms (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 type VARCHAR(30) NOT NULL,
 capacity SMALLINT UNSIGNED NOT NULL,
 description VARCHAR(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE bookings (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 room_id INT UNSIGNED NOT NULL,
 starts_at DATETIME NOT NULL,
 payment VARCHAR(10) NOT NULL,
 status VARCHAR(15) NOT NULL DEFAULT 'new',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES users(id),
 FOREIGN KEY (room_id) REFERENCES rooms(id),
 INDEX bookings_user_created (user_id, created_at),
 INDEX bookings_status_date (status, starts_at),
 CONSTRAINT bookings_payment CHECK (payment IN ('cash','sbp')),
 CONSTRAINT bookings_status CHECK (status IN ('new','scheduled','completed'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE reviews (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 booking_id INT UNSIGNED NOT NULL UNIQUE,
 rating TINYINT UNSIGNED NOT NULL,
 body TEXT NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (booking_id) REFERENCES bookings(id),
 CONSTRAINT reviews_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE login_attempts (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 attempt_key CHAR(64) NOT NULL,
 attempted_at BIGINT NOT NULL,
 INDEX attempts_key_time (attempt_key, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
