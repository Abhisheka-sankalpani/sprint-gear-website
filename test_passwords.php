<?php
$passwords = [
    '', 'root', 'admin', '123456', '1234', 'mysql', 'password', 'Root@123',
    'Admin@123', 'Password123!', 'root1234', '12345678', 'sprintgear', 'xampp',
    'root123', 'admin123', 'Pass@123', 'Secret123!'
];
foreach ($passwords as $p) {
    try {
        $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', $p);
        echo "FOUND MYSQL PASSWORD: '$p'\n";
        exit(0);
    } catch (PDOException $e) {
        // continue
    }
}
echo "NO PASSWORD MATCHED\n";
