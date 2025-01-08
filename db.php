<?php
$host = 'localhost'; // Ganti dengan host MySQL Anda
$username = 'root';  // Ganti dengan username MySQL Anda
$password = '';      // Ganti dengan password MySQL Anda
$database = 'qrcode_db'; // Nama database yang sudah dibuat

// Membuat koneksi ke database
$conn = new mysqli($host, $username, $password, $database);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
