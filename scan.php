<?php
// Cek apakah ada parameter 'url' di query string
if (isset($_GET['url'])) {
    // Ambil parameter 'url' dari query string
    $unique_url = $_GET['url'];

    // Koneksi ke database (sesuaikan dengan pengaturan Anda)
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "qrcode_db";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Cek apakah URL ditemukan di database
    $stmt = $conn->prepare("SELECT * FROM qrcodes WHERE url = ?");
    $stmt->bind_param("s", $unique_url);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // URL ditemukan, periksa statusnya
        $row = $result->fetch_assoc();

        // Mengecek apakah QR Code sudah dipindai
        if ($row['scanned'] == '1') {
            // QR Code sudah dipindai sebelumnya
            echo "QR Code ini sudah dipindai sebelumnya dan kedaluwarsa.";
        } else {
            // URL masih valid, lakukan update status menjadi 'scanned'
            $stmt_update = $conn->prepare("UPDATE qrcodes SET scanned = '1' WHERE url = ?");
            $stmt_update->bind_param("s", $unique_url);
            $stmt_update->execute();

            // Arahkan pengguna ke URL yang telah di-generate
            header("Location: " . $unique_url); 
            exit();
        }
    } else {
        // QR Code tidak ditemukan di database
        echo "URL QR Code tidak ditemukan.";
    }

    // Menutup koneksi database
    $conn->close();
} else {
    echo "URL tidak valid.";
}
?>
