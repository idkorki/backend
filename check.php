<?php
$password = 'testtest';
$hash = '$2y$10$FWkSC/kNVZkzVD9/Xq4ZEu9AVU3Pf45jTDSjfuVKUqx0bKoAF5wri';

if (password_verify($password, $hash)) {
    echo "Пароль верный\n";
} else {
    echo "Пароль НЕ совпадает\n";
}


