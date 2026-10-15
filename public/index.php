<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
try {
    $db = new Database($config); $auth = new Auth($db); $service = new Booking($db);
    $rooms = $service->rooms(); $user = $auth->current();
} catch (Throwable $exception) {
    error_log($exception->getMessage()); http_response_code(503);
    echo '<!doctype html><html lang="ru"><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="assets/style.css"><title>Настройка сайта</title><main class="container section"><div class="panel"><h1>Осталось подключить базу данных</h1><p>Укажите параметры в app/config.php и выполните php database/setup.php из папки проекта. Подробная инструкция находится в README.md.</p></div></main></html>'; exit;
}
$page = is_string($_GET['page'] ?? null) ? $_GET['page'] : 'home';
$pages = ['home','login','register','booking','dashboard','admin'];
if (!in_array($page,$pages,true)) { http_response_code(404); $page='404'; }
if (in_array($page,['booking','dashboard','admin'],true) && !$user) redirect('login');
if ($page === 'admin' && $user['role'] !== 'admin') { http_response_code(403); $page='403'; }
if ($user && in_array($page,['login','register'],true)) redirect($user['role']==='admin' ? 'admin' : 'dashboard');
$errors=[]; $globalError='';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals(csrf(), post('csrf'))) { http_response_code(419); $globalError='Срок действия формы истёк. Обновите страницу и повторите отправку.'; }
    else {
        try {
            $action = post('action');
            if ($action==='register' && $page==='register' && !$user) {
                $errors=$auth->register(['login'=>post('login'),'password'=>rawPost('password'),'full_name'=>post('full_name'),'phone'=>post('phone'),'email'=>post('email')]);
                if (!$errors) { $_SESSION['flash']='Аккаунт создан. Теперь войдите с вашим логином и паролем.'; redirect('login'); }
            } elseif ($action==='login' && $page==='login' && !$user) {
                $globalError=$auth->login(post('login'),rawPost('password')) ?? '';
                if (!$globalError) { $user=$auth->current(); redirect($user['role']==='admin' ? 'admin' : 'dashboard'); }
            } elseif ($action==='logout' && $user) { $auth->logout(); redirect('home'); }
            elseif ($action==='booking' && $page==='booking' && $user) {
                $errors=$service->create((int)$user['id'], ['room_id'=>post('room_id'),'starts_at'=>post('starts_at'),'payment'=>post('payment')]);
                if (!$errors) { $_SESSION['flash']='Заявка отправлена. Администратор рассмотрит её и обновит статус.'; redirect('dashboard'); }
            } elseif ($action==='status' && $page==='admin' && $user && $user['role']==='admin') {
                $globalError=$service->changeStatus((int)post('booking_id'),post('status')) ?? '';
                if (!$globalError) { $_SESSION['flash']='Статус заявки обновлён.'; redirect('admin'); }
            } elseif ($action==='review' && $page==='dashboard' && $user) {
                $globalError=$service->review((int)$user['id'],(int)post('booking_id'),(int)post('rating'),post('body')) ?? '';
                if (!$globalError) { $_SESSION['flash']='Спасибо! Ваш отзыв сохранён.'; redirect('dashboard'); }
            } else { http_response_code(403); $globalError='Это действие недоступно.'; }
        } catch (Throwable $exception) { error_log($exception->getMessage()); $globalError='Не удалось сохранить данные. Попробуйте ещё раз.'; }
    }
}
$flash=$_SESSION['flash'] ?? ''; unset($_SESSION['flash']);
$titles=['home'=>'Место для ваших идей','login'=>'Вход','register'=>'Регистрация','booking'=>'Новая заявка','dashboard'=>'Мои заявки','admin'=>'Панель администратора','403'=>'Нет доступа','404'=>'Страница не найдена'];
function field(string $name,string $label,string $type='text',string $placeholder='',string $hint='',string $attributes=''): void {
    global $errors;
    $value=$type==='password' ? '' : post($name);
    echo '<div class="field"><label for="'.e($name).'">'.e($label).'</label><div class="input-wrap"><input id="'.e($name).'" name="'.e($name).'" type="'.e($type).'" value="'.e($value).'" placeholder="'.e($placeholder).'" required '.($type==='password' ? 'data-password ' : '').$attributes.' aria-describedby="'.e($name).'-hint"'.(isset($errors[$name]) ? ' aria-invalid="true"' : '').'>';
    if ($type==='password') echo '<button type="button" class="password-toggle" data-toggle-password="'.e($name).'" aria-label="Показать пароль" aria-pressed="false">Показать</button>';
    echo '</div><small id="'.e($name).'-hint" class="'.(isset($errors[$name]) ? 'field-error' : 'hint').'">'.e($errors[$name] ?? $hint).'</small></div>';
}
require __DIR__.'/../app/views.php';
