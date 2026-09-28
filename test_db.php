<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3307;charset=utf8mb4', 'root', '');
    echo "SUCCESSFULLY CONNECTED TO MARIADB ON PORT 3307!\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
