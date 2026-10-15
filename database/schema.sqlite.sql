CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, login TEXT COLLATE NOCASE NOT NULL UNIQUE, password_hash TEXT NOT NULL, full_name TEXT NOT NULL, phone TEXT NOT NULL, email TEXT NOT NULL, role TEXT NOT NULL DEFAULT 'user' CHECK(role IN ('user','admin')), created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE rooms (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, type TEXT NOT NULL, capacity INTEGER NOT NULL, description TEXT NOT NULL);
CREATE TABLE bookings (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL REFERENCES users(id), room_id INTEGER NOT NULL REFERENCES rooms(id), starts_at TEXT NOT NULL, payment TEXT NOT NULL CHECK(payment IN ('cash','sbp')), status TEXT NOT NULL DEFAULT 'new' CHECK(status IN ('new','scheduled','completed')), created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP);
CREATE INDEX bookings_user_created ON bookings(user_id,created_at);
CREATE INDEX bookings_status_date ON bookings(status,starts_at);
CREATE TABLE reviews (id INTEGER PRIMARY KEY AUTOINCREMENT, booking_id INTEGER NOT NULL UNIQUE REFERENCES bookings(id), rating INTEGER NOT NULL CHECK(rating BETWEEN 1 AND 5), body TEXT NOT NULL, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE login_attempts (id INTEGER PRIMARY KEY AUTOINCREMENT, attempt_key TEXT NOT NULL, attempted_at INTEGER NOT NULL);
CREATE INDEX attempts_key_time ON login_attempts(attempt_key,attempted_at);
