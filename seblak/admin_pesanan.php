<?php
session_start();
if (!isset($_SESSION['login_admin'])) {
    header("Location: admin_login.php");
    exit();
}
include 'koneksi.php';

// Proses ubah status pesanan (contoh: dari Baru menjadi Selesai / Diproses)
if (isset($_GET['status']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $status_baru = $_GET['status'];
    mysqli_query($conn, "UPDATE pesanan SET status_pesanan = '$status_baru' WHERE id = '$id'");
    header("Location: admin_pesanan.php");
    exit();
}

// Ambil data pesanan dari database (terbaru di atas)
$result = mysqli_query($conn, "SELECT * FROM pesanan ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pesanan Masuk - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 text-gray-800">

    <!-- Navbar Admin -->
    <header class="bg-red-600 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-xl font-black tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-bell text-yellow-300 animate-bounce"></i> DASHBOARD PESANAN MASUK
            </h1>
            <div class="space-x-3 text-xs">
                <a href="admin_menu.php" class="bg-white/20 hover:bg-white/30 text-white px-3 py-1.5 rounded-lg font-bold transition">Kelola Menu</a>
                <a href="admin_login.php?logout=true" class="bg-red-700 hover:bg-red-800 text-white px-3 py-1.5 rounded-lg font-bold transition">Logout</a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-black text-gray-900">Daftar Pesanan Pelanggan</h2>
                <p class="text-xs text-gray-500 mt-1">Setiap kali pelanggan menekan tombol pesan di website, datanya akan langsung masuk ke tabel ini secara otomatis.</p>
            </div>
            <button onclick="location.reload()" class="bg-white border hover:bg-gray-50 text-gray-700 px-3 py-2 rounded-xl text-xs font-bold shadow-sm transition">
                <i class="fa-solid fa-rotate"></i> Muat Ulang Data
            </button>
        </div>

        <!-- Tabel Pesanan -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600 text-xs uppercase font-bold border-b border-gray-200">
                            <th class="p-4">Waktu / ID</th>
                            <th class="p-4">Pemesan</th>
                            <th class="p-4">Detail Pesanan & Level</th>
                            <th class="p-4">Alamat & Jarak</th>
                            <th class="p-4">Total Biaya</th>
                            <th class="p-4">Status</th>
                            <th class="p-4 text-center">Aksi / WhatsApp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-xs">
                        <?php 
                        if (mysqli_num_rows($result) > 0) :
                            while ($row = mysqli_fetch_assoc($result)) : 
                                $keranjang_item = json_decode($row['detail_pesanan'], true);
                        ?>
                        <tr class="hover:bg-gray-50 transition align-top">
                            <td class="p-4 font-bold text-gray-500">
                                #<?= $row['id'] ?><br>
                                <span class="text-[10px] text-gray-400 font-normal"><?= $row['tanggal'] ?></span>
                            </td>
                            <td class="p-4">
                                <p class="font-bold text-gray-900 text-sm"><?= htmlspecialchars($row['nama']) ?></p>
                                <p class="text-blue-600 font-semibold"><i class="fa-brands fa-whatsapp"></i> <?= htmlspecialchars($row['whatsapp']) ?></p>
                            </td>
                            <td class="p-4">
                                <p class="font-bold text-orange-600 mb-1"><i class="fa-solid fa-fire"></i> <?= htmlspecialchars($row['level_pedas']) ?></p>
                                <p class="text-green-700 font-semibold mb-1">🥬 <?= htmlspecialchars($row['pilihan_sayur']) ?></p>
                                <ul class="list-disc list-inside text-gray-600 space-y-0.5 mt-1 bg-gray-50 p-2 rounded-lg">
                                    <?php if (!empty($keranjang_item)) : foreach ($keranjang_item as $item) : ?>
                                        <li><?= htmlspecialchars($item['nama']) ?> (x<?= $item['qty'] ?>)</li>
                                    <?php endforeach; endif; ?>
                                </ul>
                            </td>
                            <td class="p-4">
                                <p class="text-gray-700 max-w-xs"><?= htmlspecialchars($row['alamat']) ?></p>
                                <span class="inline-block mt-1 bg-blue-50 text-blue-700 px-2 py-0.5 rounded font-semibold text-[10px]">
                                    <i class="fa-solid fa-location-dot"></i> Jarak: <?= $row['jarak_km'] ?> KM
                                </span>
                            </td>
                            <td class="p-4 font-black text-red-600 text-sm">
                                Rp <?= number_format($row['total_harga'], 0, ',', '.') ?>
                                <p class="text-[10px] text-gray-400 font-normal">(Ongkir: Rp <?= number_format($row['ongkir'], 0, ',', '.') ?>)</p>
                            </td>
                            <td class="p-4">
                                <?php if ($row['status_pesanan'] == 'Baru') : ?>
                                    <span class="bg-red-100 text-red-700 px-2.5 py-1 rounded-full font-bold">Baru Masuk 🔥</span>
                                <?php elseif ($row['status_pesanan'] == 'Diproses') : ?>
                                    <span class="bg-yellow-100 text-yellow-800 px-2.5 py-1 rounded-full font-bold">Sedang Dimasak 👩‍🍳</span>
                                <?php else : ?>
                                    <span class="bg-green-100 text-green-700 px-2.5 py-1 rounded-full font-bold">Selesai ✅</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 text-center space-y-2">
                                <!-- Tombol Hubungi Pembeli ke WhatsApp -->
                                <a href="https://api.whatsapp.com/send?phone=<?= preg_replace('/^0/', '62', $row['whatsapp']) ?>&text=Halo%20Kak%20<?= urlencode($row['nama']) ?>,%20pesanan%20seblak%20Anda%20sedang%20kami%20proses%20ya!" target="_blank" class="block bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg font-bold transition shadow-sm">
                                    <i class="fa-brands fa-whatsapp"></i> Chat Pembeli
                                </a>
                                <!-- Tombol Ganti Status -->
                                <div class="flex justify-center gap-1 pt-1">
                                    <a href="admin_pesanan.php?id=<?= $row['id'] ?>&status=Diproses" class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded text-[10px] font-bold">Proses</a>
                                    <a href="admin_pesanan.php?id=<?= $row['id'] ?>&status=Selesai" class="bg-green-500 hover:bg-green-600 text-white px-2 py-1 rounded text-[10px] font-bold">Selesai</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; else : ?>
                        <tr>
                            <td colspan="7" class="p-6 text-center text-gray-400 italic">Belum ada pesanan yang masuk.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>