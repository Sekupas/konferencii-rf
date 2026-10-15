<?php
declare(strict_types=1);
final class Auth {
    public function __construct(private Database $db) {}
    public function current(): ?array {
        if (empty($_SESSION['user_id'])) return null;
        return $this->db->query('SELECT id, login, full_name, email, phone, role FROM users WHERE id = ?', [$_SESSION['user_id']])->fetch() ?: null;
    }
    public function register(array $data): array {
        $errors = [];
        if (!preg_match('/^[A-Za-z0-9]{6,50}$/D', $data['login'])) $errors['login'] = 'От 6 до 50 символов: латинские буквы и цифры.';
        if (mb_strlen($data['password']) < 8 || strlen($data['password']) > 72) $errors['password'] = 'Не менее 8 символов и не более 72 байт.';
        if (!preg_match('/^[А-Яа-яЁё]+(?: +[А-Яа-яЁё]+)*$/uD', $data['full_name']) || mb_strlen($data['full_name']) > 150) $errors['full_name'] = 'Введите ФИО кириллицей, разделяя слова пробелами (до 150 символов).';
        if (!preg_match('/^8\([0-9]{3}\)[0-9]{3}-[0-9]{2}-[0-9]{2}$/D', $data['phone'])) $errors['phone'] = 'Формат телефона: 8(999)123-45-67.';
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || strlen($data['email']) > 190) $errors['email'] = 'Введите корректный email (до 190 символов).';
        if ($errors) return $errors;
        if ($this->db->query('SELECT id FROM users WHERE login = ?', [$data['login']])->fetch()) return ['login'=>'Этот логин уже занят. Выберите другой.'];
        try {
            $this->db->query('INSERT INTO users (login,password_hash,full_name,phone,email,role) VALUES (?,?,?,?,?,?)', [$data['login'], password_hash($data['password'], PASSWORD_DEFAULT), $data['full_name'], $data['phone'], $data['email'], 'user']);
        } catch (PDOException $ex) {
            if (in_array((string)$ex->getCode(), ['23000','23505'])) return ['login'=>'Этот логин уже занят. Выберите другой.'];
            throw $ex;
        }
        return [];
    }
    public function login(string $login, string $password): ?string {
        $key = hash('sha256', strtolower($login).'|'.($_SERVER['REMOTE_ADDR'] ?? 'local'));
        $now = time();
        $failures = (int)$this->db->query('SELECT COUNT(*) FROM login_attempts WHERE attempt_key = ? AND attempted_at > ?', [$key, $now-900])->fetchColumn();
        if ($failures >= 8) return 'Слишком много попыток. Повторите через 15 минут.';
        $user = $this->db->query('SELECT * FROM users WHERE login = ?', [$login])->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->db->query('INSERT INTO login_attempts (attempt_key,attempted_at) VALUES (?,?)', [$key,$now]);
            return 'Неверный логин или пароль.';
        }
        $this->db->query('DELETE FROM login_attempts WHERE attempt_key = ? OR attempted_at < ?', [$key,$now-900]);
        session_regenerate_id(true); $_SESSION['user_id'] = (int)$user['id']; $_SESSION['csrf'] = bin2hex(random_bytes(32));
        return null;
    }
    public function logout(): void {
        $_SESSION = [];
        $p = session_get_cookie_params();
        setcookie(session_name(), '', ['expires'=>time()-42000,'path'=>$p['path'],'domain'=>$p['domain'],'secure'=>$p['secure'],'httponly'=>$p['httponly'],'samesite'=>'Lax']);
        session_destroy();
    }
}
