<?php
session_start();
if (!isset($_SESSION['login_admin'])) {
    header("Location: admin_login.php");
    exit();
}
include 'koneksi.php';

// 1. Proses Update Status Pesanan (Sedang Dibikin, Sedang Diantar, Selesai)
if (isset($_GET['ubah_status']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $status_baru = $_GET['ubah_status'];
    mysqli_query($conn, "UPDATE pesanan SET status_pesanan = '$status_baru' WHERE id = '$id'");
    header("Location: admin_dashboard.php?tab=pesanan");
    exit();
}

// 2. Proses Edit Menu / Stok
if (isset($_POST['update_menu'])) {
    $id = $_POST['id_menu'];
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];
    $status = $_POST['status'];
    mysqli_query($conn, "UPDATE menu SET harga = '$harga', stok = '$stok', status = '$status' WHERE id = '$id'");
    header("Location: admin_dashboard.php?tab=stok");
    exit();
}

// 3. Proses Tambah Menu Baru (Untuk restock / menu baru)
if (isset($_POST['tambah_menu'])) {
    $nama_menu = htmlspecialchars($_POST['nama_menu']);
    $kategori = htmlspecialchars($_POST['kategori']);
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];
    $gambar = htmlspecialchars($_POST['gambar']);
    $deskripsi = htmlspecialchars($_POST['deskripsi']);
    
    mysqli_query($conn, "INSERT INTO menu (nama_menu, kategori, harga, stok, gambar, deskripsi, status) VALUES ('$nama_menu', '$kategori', '$harga', '$stok', '$gambar', '$deskripsi', 'tersedia')");
    header("Location: admin_dashboard.php?tab=stok");
    exit();
}

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'pesanan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Seblak Prasmanan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js untuk Grafik Penjualan -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 text-gray-800 flex h-screen overflow-hidden">

    <!-- Sidebar Navigasi -->
    <aside class="w-64 bg-red-700 text-white flex flex-col justify-between hidden md:flex">
        <div>
            <div class="p-6 text-xl font-black tracking-wider flex items-center gap-2 border-b border-red-600">
                <i class="fa-solid fa-fire text-yellow-300"></i> ADMIN SEBLAK
            </div>
            <nav class="p-4 space-y-2 text-xs font-bold">
                <a href="?tab=pesanan" class="flex items-center gap-3 px-4 py-3 rounded-xl transition <?= $tab == 'pesanan' ? 'bg-red-800 text-white shadow' : 'text-red-100 hover:bg-red-600' ?>">
                    <i class="fa-solid fa-basket-shopping text-sm"></i> 1. Pesanan Masuk
                </a>
                <a href="?tab=stok" class="flex items-center gap-3 px-4 py-3 rounded-xl transition <?= $tab == 'stok' ? 'bg-red-800 text-white shadow' : 'text-red-100 hover:bg-red-600' ?>">
                    <i class="fa-solid fa-boxes-stacked text-sm"></i> 2. Cek & Kelola Stok
                </a>
                <a href="?tab=tambah" class="flex items-center gap-3 px-4 py-3 rounded-xl transition <?= $tab == 'tambah' ? 'bg-red-800 text-white shadow' : 'text-red-100 hover:bg-red-600' ?>">
                    <i class="fa-solid fa-plus-circle text-sm"></i> 3. Tambah Menu / Stok Habis
                </a>
                <a href="?tab=grafik" class="flex items-center gap-3 px-4 py-3 rounded-xl transition <?= $tab == 'grafik' ? 'bg-red-800 text-white shadow' : 'text-red-100 hover:bg-red-600' ?>">
                    <i class="fa-solid fa-chart-line text-sm"></i> 5. Grafik Penjualan
                </a>
                <a href="?tab=kurir" class="flex items-center gap-3 px-4 py-3 rounded-xl transition <?= $tab == 'kurir' ? 'bg-red-800 text-white shadow' : 'text-red-100 hover:bg-red-600' ?>">
                    <i class="fa-solid fa-map-location-dot text-sm"></i> 6. Posisi Pengantar
                </a>
            </nav>
        </div>
        <div class="p-4 border-t border-red-600">
            <a href="admin_login.php?logout=true" class="flex items-center gap-2 text-xs text-red-200 hover:text-white font-bold px-4 py-2">
                <i class="fa-solid fa-right-from-bracket"></i> Keluar (Logout)
            </a>
        </div>
    </aside>

    <!-- Konten Utama -->
    <main class="flex-1 overflow-y-auto p-8">
        
        <!-- HEADER KONTEN -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-black text-gray-950 uppercase tracking-wide">
                <?php 
                    if($tab=='pesanan') echo 'Manajemen Pesanan Pelanggan';
                    elseif($tab=='stok') echo 'Ketersediaan & Manajemen Stok Topping';
                    elseif($tab=='tambah') echo 'Tambah Menu / Topping Baru';
                    elseif($tab=='grafik') echo 'Grafik Laporan Penjualan';
                    elseif($tab=='kurir') echo 'Pantau Posisi Pengantar / Kurir';
                ?>
            </h1>
            <a href="index.php" target="_blank" class="bg-white border text-gray-700 px-4 py-2 rounded-xl text-xs font-bold shadow-sm hover:bg-gray-50 transition">
                <i class="fa-solid fa-globe"></i> Lihat Website Pembeli
            </a>
        </div>

        <!-- TAB 1: PESANAN (Sedang dibikin, Selesai, Sedang diantar) -->
        <?php if ($tab == 'pesanan') : ?>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600 uppercase font-bold border-b border-gray-200">
                            <th class="p-4">ID / Waktu</th>
                            <th class="p-4">Pemesan</th>
                            <th class="p-4">Rincian Seblak</th>
                            <th class="p-4">Alamat & Jarak</th>
                            <th class="p-4">Total</th>
                            <th class="p-4">Status Pesanan</th>
                            <th class="p-4 text-center">Aksi / Update Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php 
                        $res_pesanan = mysqli_query($conn, "SELECT * FROM pesanan ORDER BY id DESC");
                        if (!$res_pesanan) {
                            echo "<tr><td colspan='7' class='p-6 text-center text-red-600 font-bold'>Tabel 'pesanan' belum dibuat di database phpMyAdmin!</td></tr>";
                        } else {
                            if (mysqli_num_rows($res_pesanan) > 0) :
                                while ($p = mysqli_fetch_assoc($res_pesanan)) :
                                    $items = json_decode($p['detail_pesanan'], true);
                        ?>
                        <tr class="hover:bg-gray-50 transition align-top">
                            <td class="p-4 font-bold text-gray-500">#<?= $p['id'] ?><br><span class="text-[10px] text-gray-400"><?= $p['tanggal'] ?></span></td>
                            <td class="p-4">
                                <p class="font-bold text-gray-900 text-sm"><?= htmlspecialchars($p['nama']) ?></p>
                                <a href="https://api.whatsapp.com/send?phone=<?= preg_replace('/^0/', '62', $p['whatsapp']) ?>" target="_blank" class="text-blue-600 font-semibold"><i class="fa-brands fa-whatsapp"></i> <?= $p['whatsapp'] ?></a>
                            </td>
                            <td class="p-4">
                                <p class="font-bold text-orange-600 mb-1"><i class="fa-solid fa-fire"></i> <?= htmlspecialchars($p['level_pedas']) ?></p>
                                <p class="text-green-700 font-semibold mb-1">🥬 <?= htmlspecialchars($p['pilihan_sayur']) ?></p>
                                <ul class="list-disc list-inside text-gray-600 bg-gray-50 p-2 rounded">
                                    <?php if(!empty($items)) foreach($items as $i) echo "<li>" . htmlspecialchars($i['nama']) . " (x{$i['qty']})</li>"; ?>
                                </ul>
                            </td>
                            <td class="p-4">
                                <p class="text-gray-700 max-w-xs"><?= htmlspecialchars($p['alamat']) ?></p>
                                <span class="inline-block mt-1 bg-blue-50 text-blue-700 px-2 py-0.5 rounded font-semibold text-[10px]">Jarak: <?= $p['jarak_km'] ?> KM</span>
                            </td>
                            <td class="p-4 font-black text-red-600 text-sm">Rp <?= number_format($p['total_harga'], 0, ',', '.') ?></td>
                            <td class="p-4">
                                <?php 
                                $status = isset($p['status_pesanan']) ? $p['status_pesanan'] : 'Baru';
                                if($status == 'Baru') echo '<span class="bg-red-100 text-red-700 px-2.5 py-1 rounded-full font-bold">Baru Masuk</span>';
                                elseif($status == 'Sedang Dibikin') echo '<span class="bg-yellow-100 text-yellow-800 px-2.5 py-1 rounded-full font-bold">Sedang Dibikin 👩‍🍳</span>';
                                elseif($status == 'Sedang Diantar') echo '<span class="bg-blue-100 text-blue-800 px-2.5 py-1 rounded-full font-bold">Sedang Diantar 🛵</span>';
                                else echo '<span class="bg-green-100 text-green-700 px-2.5 py-1 rounded-full font-bold">Selesai ✅</span>'; 
                                ?>
                            </td>
                            <td class="p-4 text-center space-y-1">
                                <a href="?tab=pesanan&id=<?= $p['id'] ?>&ubah_status=Sedang Dibikin" class="block bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded font-bold">Sedang Dibikin</a>
                                <a href="?tab=pesanan&id=<?= $p['id'] ?>&ubah_status=Sedang Diantar" class="block bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded font-bold">Sedang Diantar</a>
                                <a href="?tab=pesanan&id=<?= $p['id'] ?>&ubah_status=Selesai" class="block bg-green-500 hover:bg-green-600 text-white px-2 py-1 rounded font-bold">Selesai</a>
                            </td>
                        </tr>
                        <?php 
                                endwhile; 
                            else : 
                        ?>
                        <tr><td colspan="7" class="p-6 text-center text-gray-400 italic">Belum ada pesanan masuk.</td></tr>
                        <?php 
                            endif; 
                        } 
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- TAB 2 & 4: CEK & KELOLA STOK TERPISAH (KERUPUK, SEAFOOD, MIE) -->
        <?php if ($tab == 'stok') : 
            $kategori_list = ['Kerupuk', 'Seafood', 'Mie'];
        ?>
        <div class="space-y-8">
            <?php foreach ($kategori_list as $kat) : ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-base font-black text-gray-900 mb-4 flex items-center gap-2 border-b pb-2">
                        <i class="fa-solid fa-bowl-food text-red-600"></i> Kategori: <?= $kat ?>
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <?php 
                        $res_menu = mysqli_query($conn, "SELECT * FROM menu WHERE kategori = '$kat'");
                        if ($res_menu && mysqli_num_rows($res_menu) > 0) :
                            while ($m = mysqli_fetch_assoc($res_menu)) :
                                $stok_val = isset($m['stok']) ? $m['stok'] : 0;
                        ?>
                        <div class="border rounded-xl p-4 bg-gray-50 flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="font-bold text-gray-900 text-sm"><?= htmlspecialchars($m['nama_menu']) ?></h3>
                                    <span class="bg-red-100 text-red-700 text-[10px] px-2 py-0.5 rounded font-bold"><?= $m['kategori'] ?></span>
                                </div>
                                <p class="text-xs text-gray-500 mb-1">Harga: Rp <?= number_format($m['harga'], 0, ',', '.') ?></p>
                                <p class="text-xs font-bold mb-3">Stok Tersisa: <span class="<?= $stok_val <= 5 ? 'text-red-600' : 'text-blue-600' ?>"><?= $stok_val ?> pcs</span></p>
                            </div>
                            
                            <!-- Form Edit Cepat -->
                            <form action="" method="POST" class="space-y-2 border-t pt-3">
                                <input type="hidden" name="id_menu" value="<?= $m['id'] ?>">
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-[10px] text-gray-500">Harga</label>
                                        <input type="number" name="harga" value="<?= $m['harga'] ?>" class="w-full border rounded px-2 py-1 text-xs">
                                    </div>
                                    <div>
                                        <label class="text-[10px] text-gray-500">Stok</label>
                                        <input type="number" name="stok" value="<?= $stok_val ?>" class="w-full border rounded px-2 py-1 text-xs">
                                    </div>
                                </div>
                                <div>
                                    <label class="text-[10px] text-gray-500">Status</label>
                                    <select name="status" class="w-full border rounded px-2 py-1 text-xs bg-white">
                                        <option value="tersedia" <?= (isset($m['status']) && $m['status']=='tersedia')?'selected':'' ?>>Tersedia</option>
                                        <option value="habis" <?= (isset($m['status']) && $m['status']=='habis')?'selected':'' ?>>Habis</option>
                                    </select>
                                </div>
                                <button type="submit" name="update_menu" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-1.5 rounded text-xs font-bold transition">Simpan Perubahan</button>
                            </form>
                        </div>
                        <?php 
                            endwhile; 
                        else :
                        ?>
                            <p class="text-xs text-gray-400 italic col-span-3">Belum ada menu dalam kategori ini.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- TAB 3: TAMBAH MENU YANG SUDAH HABIS STOK / BARU -->
        <?php if ($tab == 'tambah') : ?>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 max-w-xl mx-auto">
            <h2 class="text-lg font-black text-gray-900 mb-4">Tambah Menu / Restock Topping</h2>
            <form action="" method="POST" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold mb-1">Nama Menu / Topping</label>
                    <input type="text" name="nama_menu" required class="w-full border rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-red-500 outline-none" placeholder="Contoh: Kerupuk Udang / Sosis Bakar">
                </div>
                <div>
                    <label class="block font-bold mb-1">Kategori</label>
                    <select name="kategori" required class="w-full border rounded-xl px-3 py-2.5 bg-white">
                        <option value="Kerupuk">Kerupuk</option>
                        <option value="Seafood">Seafood</option>
                        <option value="Mie">Mie</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold mb-1">Harga (Rp)</label>
                        <input type="number" name="harga" required class="w-full border rounded-xl px-3 py-2.5" placeholder="2000">
                    </div>
                    <div>
                        <label class="block font-bold mb-1">Jumlah Stok Awal</label>
                        <input type="number" name="stok" required class="w-full border rounded-xl px-3 py-2.5" placeholder="50">
                    </div>
                </div>
                <div>
                    <label class="block font-bold mb-1">Link Gambar / Foto</label>
                    <input type="text" name="gambar" required class="w-full border rounded-xl px-3 py-2.5" placeholder="https://images.unsplash.com/...">
                </div>
                <div>
                    <label class="block font-bold mb-1">Deskripsi Singkat</label>
                    <textarea name="deskripsi" rows="2" class="w-full border rounded-xl px-3 py-2.5" placeholder="Keterangan menu..."></textarea>
                </div>
                <button type="submit" name="tambah_menu" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-xl transition shadow-lg text-sm">Tambahkan ke Menu</button>
            </form>
        </div>
        <?php endif; ?>

        <!-- TAB 5: GRAFIK PENJUALAN -->
        <?php if ($tab == 'grafik') : ?>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-base font-black text-gray-900 mb-4">Grafik Tren Penjualan Harian</h2>
            <div class="w-full h-80">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
        <script>
            const ctx = document.getElementById('salesChart').getContext('2d');
            const salesChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'],
                    datasets: [{
                        label: 'Total Pendapatan (Rp)',
                        data: [45000, 62000, 58000, 75000, 90000, 125000, 150000],
                        borderColor: '#dc2626',
                        backgroundColor: 'rgba(220, 38, 38, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        </script>
        <?php endif; ?>

        <!-- TAB 6: MELIHAT POSISI PENGANTAR (KURIR) -->
        <?php if ($tab == 'kurir') : ?>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-base font-black text-gray-900 mb-2">Live Tracking Posisi Pengantar Pesanan</h2>
            <p class="text-xs text-gray-500 mb-4">Berikut adalah titik koordinat real-time kurir/pengantar yang sedang mengantar pesanan ke alamat pembeli.</p>
            
            <div class="w-full h-96 bg-gray-200 rounded-xl relative overflow-hidden flex items-center justify-center border">
                <div class="absolute inset-0 bg-blue-50 flex flex-col items-center justify-center text-center p-6">
                    <i class="fa-solid fa-motorcycle text-5xl text-red-600 animate-bounce mb-3"></i>
                    <h3 class="font-bold text-gray-800 text-sm">Kurir Sedang Menuju Lokasi Tujuan</h3>
                    <p class="text-xs text-gray-600 mt-1">Status: <span class="text-blue-600 font-bold">Dalam Perjalanan Pengantaran</span></p>
                    <p class="text-[11px] text-gray-400 mt-3 bg-white px-3 py-1 rounded-full border shadow-sm">Koordinat Terakhir Kurir: -6.691200, 108.557100</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </main>
</body>
</html>