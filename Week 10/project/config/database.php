<?php
// config/database.php

define('DB_PATH', __DIR__ . '/../db/database.sqlite');

function db_connect() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            // Ensure db folder exists
            $dbDir = dirname(DB_PATH);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0777, true);
            }
            
            $pdo = new PDO('sqlite:' . DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Enable Foreign Key support in SQLite
            $pdo->exec("PRAGMA foreign_keys = ON;");
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    return $pdo;
}
