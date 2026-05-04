<?php
require 'Database/config.php';
try {
    $db = getDB();
    echo "Koneksi berhasil!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}