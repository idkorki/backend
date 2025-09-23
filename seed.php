<?php

require_once __DIR__ . '/db.php';

$db = getDB();
$db->exec("DELETE FROM users");

$email = 'sanyanovikov6@gmail.com';
$password = password_hash('testtest', PASSWORD_DEFAULT);
$role = 'admin';

$stmt = $db->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
$stmt->execute([$email, $password, $role]);

echo " Админ вставлен: $email\n";

