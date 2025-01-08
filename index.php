<?php
session_start();
require_once 'db.php';  // Menghubungkan ke database MySQL
require_once 'phpqrcode/qrlib.php';  // Pastikan path sesuai dengan pustaka phpqrcode

// Fungsi untuk menghasilkan string acak
function generate_random_string($length = 15)
{
    return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
}

// Menangani input URL dan validasi
$input_url = "";
$error_message = "";
$qr_codes = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['url']) && !empty($_POST['url'])) {
    $input_url = $_POST['url'];

    // Menyimpan QR Codes yang di-generate ke dalam array untuk ditampilkan
    for ($i = 0; $i < 2; $i++) {
        // Menghasilkan URL unik untuk setiap QR Code
        $unique_url = $input_url . '?' . generate_random_string();
        $expire_at = date('Y-m-d H:i:s', time() + 300); // Kadaluarsa setelah 5 menit

        // Menyimpan URL yang dihasilkan ke database
        $stmt = $conn->prepare("INSERT INTO qrcodes (url, scanned, expire_at) VALUES (?, 0, ?)");
        $stmt->bind_param("ss", $unique_url, $expire_at);  // Bind URL dan waktu kadaluarsa
        $stmt->execute();
        $stmt->close();

        // Menyimpan URL ke dalam array agar dapat ditampilkan
        $qr_codes[] = $unique_url;
    }

    // Menyimpan hasil QR codes ke dalam sesi
    $_SESSION['qr_codes'] = $qr_codes;

    // Redirect untuk menghindari pengulangan proses setelah refresh
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $error_message = "URL tidak boleh kosong.";
}

// Memastikan QR Codes ditampilkan setelah form diproses
if (isset($_SESSION['qr_codes'])) {
    $qr_codes = $_SESSION['qr_codes'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate QR Code</title>

    <style>
        /* Reset default margin dan padding */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Body style */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f9;
            color: #333;
            line-height: 1.6;
            padding: 20px;
            text-align: center;
        }

        /* Container untuk form dan QR code */
        .container {
            max-width: 900px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
        }

        /* Judul utama */
        h1 {
            font-size: 32px;
            color: #333;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        /* Form Style */
        form {
            margin-bottom: 30px;
        }

        form label {
            font-size: 18px;
            color: #555;
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
        }

        form input[type="text"] {
            width: 100%;
            padding: 12px 15px;
            margin: 10px 0;
            border: 2px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
            outline: none;
        }

        form input[type="text"]:focus {
            border-color: #4caf50;
        }

        form button {
            background-color: #4caf50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        form button:hover {
            background-color: #45a049;
        }

        /* Error message */
        p[style="color: red;"] {
            color: red;
            font-size: 16px;
            margin-top: 10px;
        }

        /* Section untuk QR Codes */
        h2 {
            font-size: 24px;
            color: #333;
            margin-top: 20px;
            margin-bottom: 15px;
        }

        /* Menyusun QR Code secara horizontal */
        .qr-codes-container {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 20px; /* Menambahkan jarak antar QR Code */
            margin-top: 20px;
        }

        /* Gaya untuk masing-masing QR Code */
        .qr-code {
            display: inline-block;
            text-align: center;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            width: 200px; /* Mengatur lebar agar QR Code terlihat rapi */
        }

        .qr-code:hover {
            transform: scale(1.05);
        }

        .qr-code img {
            width: 3cm; /* Ukuran 3 cm per QR Code */
            height: 3cm;
            margin-bottom: 10px;
        }

        /* Tombol Print */
        button[onclick="printPage()"] {
            background-color: #2196F3;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
            transition: background-color 0.3s;
        }

        button[onclick="printPage()"]:hover {
            background-color: #0b7dda;
        }

        /* CSS untuk tampilan saat mencetak */
        @media print {
            /* Mengatur margin halaman untuk cetak */
            @page {
                margin-top: 0;
                margin-bottom: 0;
            }

            body {
                margin: 0;
                padding: 0;
            }

            /* Hanya menampilkan QR Code yang akan dicetak */
            body * {
                visibility: hidden;
            }

            .print-header, .print-header * {
                visibility: visible;
            }

            .qr-codes-container, .qr-codes-container * {
                visibility: visible;
            }

            /* Menampilkan elemen QR Code dalam format yang lebih besar */
            .qr-codes-container {
                display: block;
                margin-top: 0;
            }

            .qr-code {
                display: inline-block;
                margin: 10px;
                width: 3cm; /* Menentukan ukuran QR Code saat dicetak */
                height: 3cm;
            }

            .qr-code img {
                width: 3cm;
                height: 3cm;
            }

            /* Mengatur margin agar QR Code terlihat rapat di halaman */
            .container {
                padding: 0;
                margin: 0;
            }

            /* Menghilangkan URL saat di-print */
            .qr-code p {
                display: none;
            }

            /* Mencegah tombol print tampil di print */
            button {
                display: none;
            }
        }

        /* Responsiveness */
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            h1 {
                font-size: 28px;
            }

            form input[type="text"] {
                padding: 10px;
                font-size: 14px;
            }

            form button {
                padding: 10px 15px;
                font-size: 14px;
            }

            .qr-code img {
                width: 120px;
                height: 120px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Generate QR Code</h1>
        <form method="post" action="">
            <label for="url">Masukan URL QR Codes:</label>
            <input type="text" id="url" name="url" value="<?php echo htmlspecialchars($input_url); ?>" required>
            <button type="submit">Generate QR Codes</button>
        </form>

        <!-- Tombol Print -->
        <button onclick="printPage()">Print QR Codes</button>

        <?php if (!empty($error_message)): ?>
            <p style="color: red;"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <?php if (!empty($qr_codes)): ?>
            <div class="print-header">
                <h2>Generated QR Codes</h2>
                <p>Scan any of these QR codes. The URL will expire after being scanned or in 5 minutes.</p>
            </div>

            <div class="qr-codes-container">
                <?php foreach ($qr_codes as $url): ?>
                    <div class="qr-code">
                        <p>QR Code URL: <?php echo $url; ?></p>
                        <?php
                        // Generate QR Code Image
                        $tempFile = 'temp_qr_' . md5($url) . '.png';
                        QRcode::png($url, $tempFile);
                        ?>
                        <img src="<?php echo $tempFile; ?>" alt="QR Code">
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Fungsi untuk mencetak halaman
        function printPage() {
            window.print();
        }
    </script>
</body>

</html>
