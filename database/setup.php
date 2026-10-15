<?php
// Запуск только из командной строки: php database/setup.php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/../app/bootstrap.php';
try {
    if ($config['driver'] === 'sqlite' && !is_dir(dirname($config['sqlite_path']))) mkdir(dirname($config['sqlite_path']), 0700, true);
    $db = new Database($config);
    $pdo = $db->connection();
    $exists = $config['driver'] === 'sqlite'
        ? $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")->fetch()
        : $db->query("SHOW TABLES LIKE 'users'")->fetch();
    if (!$exists) $pdo->exec(file_get_contents(__DIR__.($config['driver'] === 'sqlite' ? '/schema.sqlite.sql' : '/schema.sql')));
    if (!$db->query('SELECT id FROM users WHERE login=?',['Conf2027'])->fetch()) {
        $db->query('INSERT INTO users (login,password_hash,full_name,phone,email,role) VALUES (?,?,?,?,?,?)', ['Conf2027',password_hash('Demo77',PASSWORD_DEFAULT),'Администратор портала','8(000)000-00-00','admin@example.test','admin']);
    }
    if (!(int)$db->query('SELECT COUNT(*) FROM rooms')->fetchColumn()) {
        foreach ([['Аудитория «Горизонт»','auditorium',80,'Для лекций, докладов и научных дискуссий. Проектор, экран и микрофоны.'],['Коворкинг «Диалог»','coworking',24,'Для рабочих групп и командных сессий. Гибкая рассадка, Wi-Fi и маркерные доски.'],['Кинозал «Панорама»','cinema',150,'Для масштабных выступлений и показов. Большой экран, мягкие кресла и акустическая система.']] as $room) $db->query('INSERT INTO rooms (name,type,capacity,description) VALUES (?,?,?,?)',$room);
    }
    echo "Готово. Помещения добавлены. Администратор: Conf2027 / Demo77\n";
} catch (Throwable $e) { fwrite(STDERR, "Ошибка настройки: ".$e->getMessage()."\n"); exit(1); }
