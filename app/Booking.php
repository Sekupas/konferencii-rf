<?php
declare(strict_types=1);
final class Booking {
    public function __construct(private Database $db) {}
    public function rooms(): array { return $this->db->query('SELECT * FROM rooms ORDER BY id')->fetchAll(); }
    public function create(int $userId, array $data): array {
        $errors = [];
        if (!$this->db->query('SELECT id FROM rooms WHERE id = ?', [$data['room_id']])->fetch()) $errors['room_id'] = 'Выберите помещение из списка.';
        $dt = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $data['starts_at']);
        if (!$dt || $dt->format('Y-m-d\TH:i') !== $data['starts_at'] || $dt <= new DateTimeImmutable()) $errors['starts_at'] = 'Укажите будущую дату и время по Москве.';
        if (!in_array($data['payment'], ['cash','sbp'], true)) $errors['payment'] = 'Выберите способ оплаты.';
        if ($errors) return $errors;
        $this->db->query("INSERT INTO bookings (user_id,room_id,starts_at,payment,status) VALUES (?,?,?,?,'new')", [$userId,$data['room_id'],$dt->format('Y-m-d H:i:s'),$data['payment']]);
        return [];
    }
    public function list(?int $userId, string $status = '', string $search = ''): array {
        $sql = 'SELECT b.*, r.name AS room_name, r.type AS room_type, u.full_name, u.login, u.email, u.phone, v.rating, v.body AS review_body FROM bookings b JOIN rooms r ON r.id=b.room_id JOIN users u ON u.id=b.user_id LEFT JOIN reviews v ON v.booking_id=b.id WHERE 1=1';
        $params = [];
        if ($userId !== null) { $sql .= ' AND b.user_id=?'; $params[] = $userId; }
        if (in_array($status,['new','scheduled','completed'],true)) { $sql .= ' AND b.status=?'; $params[]=$status; }
        if ($search !== '') { $sql .= ' AND (u.full_name LIKE ? OR u.login LIKE ? OR r.name LIKE ?)'; array_push($params, '%'.$search.'%', '%'.$search.'%', '%'.$search.'%'); }
        return $this->db->query($sql.' ORDER BY b.created_at DESC,b.id DESC',$params)->fetchAll();
    }
    public function changeStatus(int $id, string $status): ?string {
        if (!in_array($status,['scheduled','completed'],true)) return 'Недопустимый статус.';
        $previous = $status === 'scheduled' ? 'new' : 'scheduled';
        $q = $this->db->query('UPDATE bookings SET status=? WHERE id=? AND status=?',[$status,$id,$previous]);
        return $q->rowCount() ? null : 'Статус уже изменён или переход недоступен. Обновите страницу.';
    }
    public function review(int $userId, int $bookingId, int $rating, string $body): ?string {
        if ($rating < 1 || $rating > 5 || mb_strlen($body) < 10 || mb_strlen($body) > 2000) return 'Выберите оценку от 1 до 5 и напишите отзыв длиной от 10 до 2000 символов.';
        $booking = $this->db->query("SELECT id FROM bookings WHERE id=? AND user_id=? AND status='completed'",[$bookingId,$userId])->fetch();
        if (!$booking) return 'Отзыв доступен только для вашего завершённого мероприятия.';
        try { $this->db->query('INSERT INTO reviews (booking_id,rating,body) VALUES (?,?,?)',[$bookingId,$rating,$body]); }
        catch (PDOException $ex) { if ((string)$ex->getCode() === '23000') return 'Вы уже оставили отзыв об этом мероприятии.'; throw $ex; }
        return null;
    }
    public function stats(?int $userId): array {
        $rows = $this->db->query('SELECT status,COUNT(*) AS total FROM bookings'.($userId === null ? '' : ' WHERE user_id=?').' GROUP BY status', $userId === null ? [] : [$userId])->fetchAll();
        $result = ['new'=>0,'scheduled'=>0,'completed'=>0];
        foreach ($rows as $row) $result[$row['status']] = (int)$row['total'];
        return $result;
    }
}
