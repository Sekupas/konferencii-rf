<?php
/** php tests/smoke.php — изолированная проверка логики в SQLite :memory:. */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
session_start();
require __DIR__.'/../app/bootstrap.php';
$checks=0;
function check(bool $ok, string $label): void { global $checks; $checks++; if (!$ok) throw new RuntimeException($label); }
try {
    $db = new Database(['driver'=>'sqlite','sqlite_path'=>':memory:']);
    $db->connection()->exec(file_get_contents(__DIR__.'/../database/schema.sqlite.sql'));
    $db->query('INSERT INTO rooms (name,type,capacity,description) VALUES (?,?,?,?)',['Зал','auditorium',80,'Описание']);
    $auth = new Auth($db); $booking = new Booking($db);
    $data = ['login'=>'User2027','password'=>'Password2027','full_name'=>'Иванов Иван','phone'=>'8(999)123-45-67','email'=>'ivan@example.test'];
    check($auth->register($data)===[], 'Регистрация');
    check(isset($auth->register(array_replace($data,['login'=>'user2027']))['login']), 'Уникальность логина без регистра');
    check(count($auth->register(['login'=>'абв','password'=>'123','full_name'=>'Test','phone'=>'bad','email'=>'bad']))===5, 'Ошибки полей');
    check($auth->login('User2027','wrong')!==null, 'Неверный пароль');
    check($auth->login('User2027','Password2027')===null, 'Вход');
    $id=(int)$auth->current()['id'];
    check($auth->current()['role']==='user','Роль пользователя');
    $request=['room_id'=>'1','starts_at'=>(new DateTimeImmutable('+1 day'))->format('Y-m-d\TH:i'),'payment'=>'sbp'];
    check($booking->create($id,$request)===[],'Новая заявка');
    check(isset($booking->create($id,array_replace($request,['starts_at'=>'2020-01-01T12:00']))['starts_at']),'Дата в прошлом');
    check(isset($booking->create($id,array_replace($request,['room_id'=>'999']))['room_id']),'Несуществующее помещение');
    check(isset($booking->create($id,array_replace($request,['payment'=>'bad']))['payment']),'Недопустимая оплата');
    $rows=$booking->list($id);
    check(count($rows)===1 && $rows[0]['status']==='new','Сохранение заявки');
    $bookingId=(int)$rows[0]['id'];
    check($booking->list(999)===[],'Изоляция чужих заявок');
    check($booking->review($id,$bookingId,5,'Хорошее мероприятие')!==null,'Отзыв до завершения запрещён');
    check($booking->changeStatus($bookingId,'completed')!==null,'Нельзя пропустить назначение');
    check($booking->changeStatus($bookingId,'scheduled')===null,'Назначение');
    check($booking->changeStatus($bookingId,'completed')===null,'Завершение');
    check($booking->review(999,$bookingId,5,'Хорошее мероприятие')!==null,'Чужой отзыв запрещён');
    check($booking->review($id,$bookingId,6,'Хорошее мероприятие')!==null,'Неверная оценка');
    check($booking->review($id,$bookingId,5,'Хорошее мероприятие')===null,'Сохранение отзыва');
    check($booking->review($id,$bookingId,5,'Повторный отзыв')!==null,'Повторный отзыв запрещён');
    check(count($booking->list(null,'completed','Иванов'))===1,'Поиск и статус');
    check($booking->stats($id)===['new'=>0,'scheduled'=>0,'completed'=>1],'Счётчики');
    check(e('<script>')==='&lt;script&gt;','HTML-экранирование');
    for($i=0;$i<8;$i++) $auth->login('Unknown2027','bad');
    check(str_contains($auth->login('Unknown2027','bad'),'15 минут'),'Ограничение входа');
    $auth->logout(); check($auth->current()===null,'Выход');
    echo "PASS: $checks проверок регистрации, входа, заявок, отзывов, поиска и защиты.\n";
} catch (Throwable $e) { fwrite(STDERR,'FAIL: '.$e->getMessage()."\n"); exit(1); }
