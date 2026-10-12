<?php
// Sambungkan ke database (pastikan file koneksi.php ada di folder yang sama)
include 'koneksi.php';

// === BAGIAN PROSES ORDER & REDIRECT KE WHATSAPP (BACKEND) ===
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama              = mysqli_real_escape_string($conn, $_POST['nama']);
    $whatsapp          = mysqli_real_escape_string($conn, $_POST['whatsapp']);
    $alamat            = mysqli_real_escape_string($conn, $_POST['alamat']);
    $level_kepedasan   = mysqli_real_escape_string($conn, $_POST['level_kepedasan']);
    $pilihan_sayur     = mysqli_real_escape_string($conn, $_POST['pilihan_sayur']);
    $jarak_km          = floatval($_POST['jarak_km']);
    $ongkir_input      = intval($_POST['ongkir']);
    
    $detail_json       = $_POST['detail_pesanan'];
    $keranjang         = json_decode($detail_json, true);

    if (empty($keranjang)) {
        echo "<script>alert('Mangkuk seblak Anda masih kosong!'); window.location='index.php';</script>";
        exit;
    }

    $subtotal_topping = 0;
    $rincian_topping_text = "";
    
    foreach ($keranjang as $item) {
        $nama_item = $item['nama'];
        $harga_item = intval($item['harga']);
        $qty_item = intval($item['qty']);
        $subtotal_item = $harga_item * $qty_item;
        
        $subtotal_topping += $subtotal_item;
        $rincian_topping_text .= "- {$nama_item} (Rp " . number_format($harga_item, 0, ',', '.') . ") x {$qty_item} = Rp " . number_format($subtotal_item, 0, ',', '.') . "\n";
    }

    $harga_bumbu = 2000;
    
    $biaya_level = 0;
    if (strpos($level_kepedasan, 'Level 4') !== false) {
        $biaya_level = 1000;
    }

    $subtotal_belanja = $subtotal_topping + $harga_bumbu + $biaya_level;

    $tarif_per_km = 3000;
    $ongkir_asli = $jarak_km * $tarif_per_km;
    
    $diskon_ongkir = ($subtotal_belanja > 50000) ? 6000 : 0;
    $ongkir_final = max(0, $ongkir_asli - $diskon_ongkir);

    $total_pembayaran = intval($_POST['total_harga']);
    $status_pesanan = 'Baru';

    // SIMPAN KE DATABASE TABEL `pesanan`
    $query_simpan = "INSERT INTO pesanan (nama, whatsapp, alamat, level_kepedasan, pilihan_sayur, jarak_km, ongkir, total_harga, status_pesanan, tanggal) 
                     VALUES ('$nama', '$whatsapp', '$alamat', '$level_kepedasan', '$pilihan_sayur', '$jarak_km', '$ongkir_final', '$total_pembayaran', '$status_pesanan', NOW())";
    
    if (mysqli_query($conn, $query_simpan)) {
        $pesanan_id = mysqli_insert_id($conn);

        foreach ($keranjang as $item) {
            $menu_id = intval($item['id']);
            $nama_m = mysqli_real_escape_string($conn, $item['nama']);
            $h_m = intval($item['harga']);
            $q_m = intval($item['qty']);
            
            @mysqli_query($conn, "INSERT INTO detail_pesanan (pesanan_id, menu_id, nama_menu, harga, qty) VALUES ('$pesanan_id', '$menu_id', '$nama_m', '$h_m', '$q_m')");
            @mysqli_query($conn, "UPDATE menu SET stok = stok - $q_m WHERE id = '$menu_id'");
        }
    }

    // Nomor WhatsApp Admin
    $nomor_admin_wa = "6287751678770"; 

    $pesan_wa  = "Halo *Seblak Prasmanan Putra*, saya ingin memesan seblak dengan rincian berikut:\n\n";
    $pesan_wa .= "👤 *DATA PEMESAN*\n";
    $pesan_wa .= "• Nama: *{$nama}*\n";
    $pesan_wa .= "• No. WA: {$whatsapp}\n";
    $pesan_wa .= "• Alamat: {$alamat}\n";
    $pesan_wa .= "• Jarak Pengantaran: {$jarak_km} KM\n\n";
    
    $pesan_wa .= "🛒 *DETAIL TOPPING*\n";
    $pesan_wa .= $rincian_topping_text . "\n";
    
    $pesan_wa .= "🌶️ *TINGKAT KEPEDASAN*\n";
    $pesan_wa .= "• {$level_kepedasan}\n\n";
    
    $pesan_wa .= "🥬 *PILIHAN SAYUR*\n";
    $pesan_wa .= "• {$pilihan_sayur}\n\n";
    
    $pesan_wa .= "💰 *RINCIAN BIAYA*\n";
    $pesan_wa .= "• Subtotal Topping: Rp " . number_format($subtotal_topping, 0, ',', '.') . "\n";
    $pesan_wa .= "• Bumbu Seblak: Rp " . number_format($harga_bumbu, 0, ',', '.') . "\n";
    if ($biaya_level > 0) { $pesan_wa .= "• Tambahan Level 4: Rp " . number_format($biaya_level, 0, ',', '.') . "\n"; }
    $pesan_wa .= "• Ongkir ({$jarak_km} KM): Rp " . number_format($ongkir_final, 0, ',', '.') . "\n";
    if ($diskon_ongkir > 0) { $pesan_wa .= "• (Diskon Ongkir Belanja >50rb Terpakai ✅)\n"; }
    $pesan_wa .= "----------------------------------\n";
    $pesan_wa .= "💵 *TOTAL PEMBAYARAN: Rp " . number_format($total_pembayaran, 0, ',', '.') . "*\n\n";
    $pesan_wa .= "Mohon segera diproses ya Kak. Terima kasih! 🙏";

    $url_whatsapp = "https://api.whatsapp.com/send?phone=" . $nomor_admin_wa . "&text=" . urlencode($pesan_wa);

    echo "<script>
        window.open('" . $url_whatsapp . "', '_blank');
        window.location.href = 'index.php?cek_wa=" . $whatsapp . "&status=sukses';
    </script>";
    exit;
}

// === PENGECEKAN STATUS PESANAN (MELALUI URL GET) ===
$status_pesanan_terakhir = "";
$data_pesanan_detail = null;
if (isset($_GET['cek_wa'])) {
    $wa_Cari = mysqli_real_escape_string($conn, $_GET['cek_wa']);
    $query_cek = mysqli_query($conn, "SELECT * FROM pesanan WHERE whatsapp = '$wa_Cari' ORDER BY id DESC LIMIT 1");
    if ($query_cek && mysqli_num_rows($query_cek) > 0) {
        $data_p = mysqli_fetch_assoc($query_cek);
        $status_pesanan_terakhir = $data_p['status_pesanan'];
        $data_pesanan_detail = $data_p;
    }
}

// === BAGIAN TAMPILAN UTAMA (FRONTEND) ===
isset($_GET['kategori']) ? $kategori = $_GET['kategori'] : $kategori = 'Semua';

if ($kategori == 'Semua') {
    $query = "SELECT * FROM menu WHERE status = 'tersedia'";
} else {
    $query = "SELECT * FROM menu WHERE kategori = '$kategori' AND status = 'tersedia'";
}
$result = mysqli_query($conn,$query);
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seblak Prasmanan Putra - Pilih Topping Suka-suka</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AOS CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        @keyframes bounceCart {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); background-color: #fee2e2; }
        }
        .animate-cart {
            animation: bounceCart 0.4s ease-in-out;
        }
    </style>
</head>
<body class="bg-red-50 text-gray-800">

    <!-- Header / Navbar -->
    <header class="bg-white shadow-md sticky top-0 z-50 border-b-4 border-red-600" data-aos="fade-down" data-aos-duration="600">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-xl md:text-2xl font-black text-red-600 tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-fire text-orange-500"></i> SEBLAK PRASMANAN PUTRA
            </h1>
            <div class="flex items-center gap-4">
                <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs md:text-sm font-semibold hidden md:inline-block">
                    <i class="fa-solid fa-motorcycle"></i> Ongkir: Rp 3.000 / KM
                </span>
            </div>
        </div>
    </header>

    <!-- NOTIFIKASI SUKSES SETELAH CHECKOUT -->
    <?php if (isset($_GET['status']) &&$_GET['status'] == 'sukses') : ?>
        <div class="max-w-7xl mx-auto px-4 mt-4">
            <div class="bg-green-100 border border-green-300 text-green-800 p-4 rounded-xl text-xs md:text-sm font-bold text-center shadow">
                🎉 Pesanan Anda berhasil dicatat dan dikirim ke WhatsApp Admin! Pantau status pesanan dan posisi kurir di bagian bawah halaman.
            </div>
        </div>
    <?php endif; ?>

    <!-- Filter Kategori (Di Atas Menu) -->
    <div class="sticky top-[73px] z-40 bg-red-50/95 backdrop-blur-sm py-3 border-b border-red-100 shadow-sm mt-4">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex flex-wrap gap-2">
                <a href="index.php?kategori=Semua" class="px-4 py-2 rounded-lg text-sm font-bold transition transform hover:scale-105 <?= $kategori == 'Semua' ? 'bg-red-600 text-white shadow' : 'bg-white text-red-700 border border-red-200 hover:bg-red-50' ?>">Semua</a>
                <a href="index.php?kategori=Kerupuk" class="px-4 py-2 rounded-lg text-sm font-bold transition transform hover:scale-105 <?= $kategori == 'Kerupuk' ? 'bg-red-600 text-white shadow' : 'bg-white text-red-700 border border-red-200 hover:bg-red-50' ?>">Kerupuk</a>
                <a href="index.php?kategori=Seafood" class="px-4 py-2 rounded-lg text-sm font-bold transition transform hover:scale-105 <?= $kategori == 'Seafood' ? 'bg-red-600 text-white shadow' : 'bg-white text-red-700 border border-red-200 hover:bg-red-50' ?>">Seafood</a>
                <a href="index.php?kategori=Mie" class="px-4 py-2 rounded-lg text-sm font-bold transition transform hover:scale-105 <?= $kategori == 'Mie' ? 'bg-red-600 text-white shadow' : 'bg-white text-red-700 border border-red-200 hover:bg-red-50' ?>">Mie</a>
            </div>
        </div>
    </div>

    <main class="max-w-7xl mx-auto px-4 py-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- BAGIAN KIRI & TENGAH: PILIHAN TOPPING -->
        <div class="lg:col-span-2">
            <div class="bg-red-600 text-white p-4 rounded-xl mb-6 shadow-sm" data-aos="fade-up" data-aos-duration="800">
                <h2 class="text-lg font-bold"><i class="fa-solid fa-bowl-food"></i> Ambil Topping & Menu Favoritmu!</h2>
                <p class="text-xs text-red-100 mt-1">Pilih aneka kerupuk, seafood, mie, dan level kepedasan sesukamu. (Belanja di atas Rp 50.000 otomatis dapat potongan ongkir Rp 6.000!).</p>
            </div>

            <!-- List Menu Card -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php 
                $delay = 100;
                while ($row = mysqli_fetch_assoc($result)) : 
                ?>
                <div class="bg-white rounded-xl shadow-sm border border-red-100 flex flex-col justify-between hover:shadow-md hover:-translate-y-1 transition duration-300 overflow-hidden" 
                     data-aos="fade-up" data-aos-duration="800" data-aos-delay="<?= $delay ?>">
                    <div>
                        <div class="relative h-40 w-full bg-gray-100 overflow-hidden">
                            <img src="<?= htmlspecialchars($row['gambar']) ?>" alt="<?= htmlspecialchars($row['nama_menu']) ?>" class="w-full h-full object-cover">
                            <span class="absolute top-2 left-2 bg-red-600 text-white text-xs font-bold px-2 py-1 rounded shadow">
                                <?= htmlspecialchars($row['kategori']) ?>
                            </span>
                        </div>
                        <div class="p-4">
                            <div class="flex justify-between items-start">
                                <h3 class="text-base font-bold text-gray-900"><?= htmlspecialchars($row['nama_menu']) ?></h3>
                                <span class="text-base font-black text-red-600">Rp <?= number_format($row['harga'], 0, ',', '.') ?></span>
                            </div>
                            <p class="text-gray-500 text-xs mt-1 line-clamp-2"><?= htmlspecialchars($row['deskripsi']) ?></p>
                        </div>
                    </div>
                    <div class="p-4 pt-0">
                        <button onclick="tambahKeKeranjang('<?= $row['id'] ?>', '<?= addslashes($row['nama_menu']) ?>', <?=$row['harga'] ?>)" 
                                class="w-full bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-lg text-xs font-bold transition transform active:scale-95 flex items-center justify-center gap-1 shadow">
                            <i class="fa-solid fa-plus"></i> Ambil Topping
                        </button>
                    </div>
                </div>
                <?php 
                $delay += 100; 
                endwhile; 
                ?>
            </div>
        </div>

        <!-- BAGIAN KANAN: KERANJANG & CHECKOUT -->
        <div id="mangkuk-seblak-section" class="bg-white p-6 rounded-xl shadow-md border border-red-100 h-fit sticky top-36 transition-all duration-300" data-aos="fade-left" data-aos-duration="900">
            <h2 id="mangkuk-title" class="text-xl font-black mb-4 flex items-center justify-between text-red-600">
                <span class="flex items-center gap-2"><i class="fa-solid fa-basket-shopping"></i> Mangkuk Seblak Kamu</span>
                <span id="badge-total-item" class="bg-red-600 text-white text-xs px-2.5 py-1 rounded-full font-bold hidden">0</span>
            </h2>

            <!-- Level Kepedasan -->
            <div class="mb-3 bg-orange-50 p-3 rounded-lg border border-orange-200">
                <label class="block text-xs font-bold text-orange-800 mb-1"><i class="fa-solid fa-fire-flame-curved"></i> LEVEL KEPEDASAN</label>
                <select name="level_pedas" id="level-pedas" onchange="hitungTotal()" class="w-full border border-orange-300 rounded-lg px-2 py-1.5 text-xs bg-white font-medium focus:ring-2 focus:ring-red-500 outline-none">
                    <option value="Level 0 (Tidak Pedas)">Level 0 - Tidak Pedas</option>
                    <option value="Level 1 (Sedang)">Level 1 - Sedang</option>
                    <option value="Level 2 (Pedas Nikmat)">Level 2 - Pedas Nikmat</option>
                    <option value="Level 3 (Pedas Nampol)">Level 3 - Pedas Nampol 🔥</option>
                    <option value="Level 4 (Super Pedas)">Level 4 - Super Pedas (+Rp 1.000) 🥵🔥</option>
                </select>
            </div>

            <!-- List Item di Keranjang -->
            <div id="list-keranjang" class="divide-y max-h-48 overflow-y-auto mb-4 text-sm pr-1">
                <p class="text-gray-400 text-center py-4 text-xs italic">Mangkuk masih kosong. Silakan pilih topping di sebelah kiri!</p>
            </div>

            <!-- Form Checkout -->
            <form action="index.php" method="POST" id="form-checkout">
                <input type="hidden" name="detail_pesanan" id="input-detail-pesanan">
                <input type="hidden" name="level_kepedasan" id="input-level-pedas">
                <input type="hidden" name="pilihan_sayur" id="input-pilihan-sayur">
                <input type="hidden" name="total_harga" id="input-total-harga">
                <input type="hidden" name="ongkir" id="input-ongkir">
                <input type="hidden" name="jarak_km" id="input-jarak-km" value="3">

                <div class="space-y-3 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Nama Pemesan</label>
                        <input type="text" name="nama" required class="w-full border rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-red-500 outline-none" placeholder="Nama Anda">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">No. WhatsApp</label>
                        <input type="text" name="whatsapp" required class="w-full border rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-red-500 outline-none" placeholder="08xxxxxxxxxx">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Alamat Pengantaran / Patokan</label>
                        <textarea name="alamat" required rows="2" class="w-full border rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-red-500 outline-none" placeholder="Alamat lengkap tujuan pengantaran"></textarea>
                    </div>
                    
                    <!-- GPS -->
                    <div class="bg-blue-50 p-3 rounded-lg border border-blue-200">
                        <div class="flex justify-between items-center text-xs">
                            <span class="font-bold text-blue-900"><i class="fa-solid fa-location-crosshairs"></i> Jarak Otomatis (GPS):</span>
                            <span id="status-gps" class="text-blue-700 font-semibold">Mendeteksi lokasi...</span>
                        </div>
                        <div class="mt-1 font-extrabold text-blue-900 text-sm">
                            <span id="jarak-val">3</span> KM
                        </div>
                        <button type="button" onclick="deteksiLokasi()" class="mt-2 text-[10px] bg-blue-600 hover:bg-blue-700 text-white px-2.5 py-1 rounded font-bold transition">
                            <i class="fa-solid fa-crosshairs"></i> Deteksi Ulang Lokasi Saya
                        </button>
                    </div>
                </div>

                <!-- Pilihan Sayur -->
                <div class="mb-4 bg-green-50 p-3 rounded-lg border border-green-200">
                    <label class="block text-xs font-bold text-green-800 mb-2"><i class="fa-solid fa-seedling"></i> PILIHAN SAYUR (Tambahan)</label>
                    <div class="space-y-2 text-xs font-medium text-gray-700">
                        <label class="flex justify-between items-center cursor-pointer">
                            <span class="flex items-center gap-1.5"><input type="checkbox" name="sayur[]" value="Kol / Saisim" data-harga="1000" class="accent-green-600 sayur-checkbox" onchange="hitungTotal()"> Kol / Saisim</span>
                            <span class="font-bold text-green-700">Rp 1.000</span>
                        </label>
                        <label class="flex justify-between items-center cursor-pointer">
                            <span class="flex items-center gap-1.5"><input type="checkbox" name="sayur[]" value="Sawi Putih" data-harga="2000" class="accent-green-600 sayur-checkbox" onchange="hitungTotal()"> Sawi Putih</span>
                            <span class="font-bold text-green-700">Rp 2.000</span>
                        </label>
                        <label class="flex justify-between items-center cursor-pointer">
                            <span class="flex items-center gap-1.5"><input type="checkbox" name="sayur[]" value="Pokcoy" data-harga="2000" class="accent-green-600 sayur-checkbox" onchange="hitungTotal()"> Pokcoy</span>
                            <span class="font-bold text-green-700">Rp 2.000</span>
                        </label>
                    </div>
                </div>

                <!-- Rincian Biaya -->
                <div class="border-t pt-3 space-y-1.5 text-xs mb-4">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal Topping:</span>
                        <span id="subtotal-topping-text" class="font-semibold">Rp 0</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Biaya Bumbu Seblak:</span>
                        <span class="font-semibold">Rp 2.000</span>
                    </div>
                    
                    <!-- Baris Tambahan Level 4 -->
                    <div id="info-biaya-level" class="flex justify-between text-orange-600 font-bold hidden">
                        <span>Tambahan Level 4:</span>
                        <span id="biaya-level-text">+ Rp 1.000</span>
                    </div>

                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal Sayur:</span>
                        <span id="subtotal-sayur-text" class="font-semibold">Rp 0</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Ongkir (<span id="jarak-text">3</span> KM x 3rb):</span>
                        <span id="ongkir-asli-text" class="font-semibold">Rp 9.000</span>
                    </div>
                    
                    <!-- Diskon Ongkir -->
                    <div id="info-diskon-ongkir" class="flex justify-between text-green-600 font-bold hidden">
                        <span>Diskon Gratis Ongkir (> 50rb):</span>
                        <span id="diskon-ongkir-text">- Rp 6.000</span>
                    </div>
                    
                    <div class="flex justify-between font-extrabold text-sm text-gray-900 border-t pt-2">
                        <span>Total Pembayaran:</span>
                        <span id="total-text" class="text-red-600 text-base">Rp 0</span>
                    </div>
                </div>

                <button type="submit" onclick="siapkanDataCheckout()" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-xl transition transform active:scale-95 flex items-center justify-center gap-2 shadow-lg shadow-green-100 text-sm">
                    <i class="fa-brands fa-whatsapp text-lg"></i> Pesan Seblak via WhatsApp
                </button>
            </form>
        </div>

    </main>

    <!-- KOTAK CEK STATUS & LACAK DRIVER (DIPINDAHKAN KE BAGIAN BAWAH HALAMAN) -->
    <div class="max-w-7xl mx-auto px-4 mt-8 mb-16">
        <div class="bg-white p-6 rounded-xl shadow-md border border-orange-200">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex items-center gap-3 w-full md:w-auto">
                    <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-xl font-bold shrink-0">
                        <i class="fa-solid fa-motorcycle"></i>
                    </div>
                    <div>
                        <h3 class="text-sm md:text-base font-bold text-gray-900">Cek Status Pesanan & Lacak Driver</h3>
                        <p class="text-xs text-gray-500">Masukkan nomor WhatsApp Anda untuk melihat progress masakan dan posisi kurir.</p>
                    </div>
                </div>
                
                <form action="index.php" method="GET" class="flex gap-2 w-full md:w-auto">
                    <input type="text" name="cek_wa" placeholder="No. WhatsApp Anda..." value="<?= isset($_GET['cek_wa']) ? htmlspecialchars($_GET['cek_wa']) : '' ?>" class="border rounded-lg px-3 py-2 text-xs md:text-sm outline-none focus:ring-2 focus:ring-red-500 w-full md:w-64" required>
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-5 py-2 rounded-lg text-xs md:text-sm font-bold transition whitespace-nowrap shadow">
                        <i class="fa-solid fa-magnifying-glass"></i> Lacak
                    </button>
                </form>
            </div>

            <!-- HASIL PELACAKAN PESANAN -->
            <?php if (isset($_GET['cek_wa'])) : ?>
                <div class="mt-5 pt-4 border-t space-y-4">
                    <?php if ($status_pesanan_terakhir) : ?>
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center text-xs md:text-sm bg-gray-50 p-3 rounded-lg border gap-2">
                            <div>
                                <span class="font-bold text-gray-700">Pemesan:</span> <?= htmlspecialchars($data_pesanan_detail['nama']) ?> | 
                                <span class="font-bold text-gray-700">Alamat:</span> <?= htmlspecialchars($data_pesanan_detail['alamat']) ?>
                            </div>
                            <span class="px-3 py-1 rounded-full font-extrabold uppercase text-xs 
                                <?php 
                                    if($status_pesanan_terakhir == 'Baru') echo 'bg-red-100 text-red-700';
                                    elseif($status_pesanan_terakhir == 'Sedang Dibikin') echo 'bg-yellow-100 text-yellow-800';
                                    elseif($status_pesanan_terakhir == 'Sedang Diantar') echo 'bg-blue-100 text-blue-800';
                                    elseif($status_pesanan_terakhir == 'Selesai') echo 'bg-green-100 text-green-700';
                                    else echo 'bg-gray-100 text-gray-700';
                                ?>">
                                Status: <?= $status_pesanan_terakhir ?>
                            </span>
                        </div>

                        <!-- VISUALISASI PROGRESS / STATUS KURIR -->
                        <div class="space-y-3">
                            <p class="text-xs font-bold text-gray-700"><i class="fa-solid fa-route text-red-600"></i> Progress Perjalanan Seblak Kamu:</p>
                            
                            <?php if ($status_pesanan_terakhir == 'Baru') : ?>
                                <div class="bg-red-50 border border-red-200 p-4 rounded-xl text-xs text-red-700 flex items-center gap-3">
                                    <i class="fa-solid fa-receipt text-xl animate-pulse"></i>
                                    <div>
                                        <p class="font-bold">Pesanan Baru Diterima Sistem!</p>
                                        <p>Pesanan Anda sudah masuk ke sistem kasir dan segera diteruskan ke dapur.</p>
                                    </div>
                                </div>
                            <?php elseif ($status_pesanan_terakhir == 'Sedang Dibikin') : ?>
                                <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-xl text-xs text-yellow-800 flex items-center gap-3">
                                    <i class="fa-solid fa-kitchen-set text-xl animate-spin"></i>
                                    <div>
                                        <p class="font-bold">Seblak Sedang Dimasak oleh Koki 👩‍🍳</p>
                                        <p>Topping pilihanmu sedang direbus, dibumbui dengan kuah pedas gurih di dapur.</p>
                                    </div>
                                </div>
                            <?php elseif ($status_pesanan_terakhir == 'Sedang Diantar') : ?>
                                <div class="bg-blue-50 border border-blue-200 p-4 rounded-xl space-y-3">
                                    <div class="flex items-center justify-between text-xs font-bold text-blue-900">
                                        <span class="flex items-center gap-2"><i class="fa-solid fa-motorcycle text-base animate-bounce"></i> Driver Sedang Mengantar Pesanan ke Lokasimu!</span>
                                        <span class="bg-blue-600 text-white px-2 py-0.5 rounded text-[10px] animate-pulse">Live Tracking</span>
                                    </div>
                                    <!-- Progress Bar Animasi Driver -->
                                    <div class="w-full bg-blue-200 h-3 rounded-full overflow-hidden relative">
                                        <div class="bg-blue-600 h-full w-3/4 rounded-full animate-pulse"></div>
                                    </div>
                                    <div class="flex justify-between text-[11px] text-blue-700 font-medium">
                                        <span>🏪 Warung Seblak Putra</span>
                                        <span>📍 Menuju Alamat Tujuanmu (<?= $data_pesanan_detail['jarak_km'] ?> KM)</span>
                                    </div>
                                </div>
                            <?php elseif ($status_pesanan_terakhir == 'Selesai') : ?>
                                <div class="bg-green-50 border border-green-200 p-4 rounded-xl text-xs text-green-800 flex items-center gap-3">
                                    <i class="fa-solid fa-circle-check text-xl"></i>
                                    <div>
                                        <p class="font-bold">Pesanan Telah Selesai / Diterima ✅</p>
                                        <p>Terima kasih telah memesan di Seblak Prasmanan Putra. Selamat menikmati hidanganmu!</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else : ?>
                        <div class="text-xs text-center text-gray-500 py-4 italic">
                            ❌ Tidak ditemukan riwayat pesanan dengan nomor WhatsApp tersebut. Pastikan nomor yang dimasukkan sesuai saat checkout.
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Floating Cart Mobile -->
    <div class="fixed bottom-6 right-6 lg:hidden z-40">
        <button onclick="scrollToMangkuk()" class="bg-red-600 hover:bg-red-700 text-white p-4 rounded-full shadow-2xl flex items-center justify-center relative transform active:scale-90 transition">
            <i class="fa-solid fa-basket-shopping text-xl"></i>
            <span id="floating-badge" class="absolute -top-1 -right-1 bg-yellow-400 text-red-900 text-xs font-black w-6 h-6 rounded-full flex items-center justify-center border-2 border-white shadow hidden">0</span>
        </button>
    </div>

    <!-- AOS JS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ once: true, offset: 50 });

        let keranjang = [];
        const tarifPerKm = 3000;
        const hargaBumbu = 2000;

        const latWarung = -6.6907352; 
        const lonWarung = 108.5560086; 

        let jarakKmGlobal = 3; 

        window.onload = function() {
            deteksiLokasi();
        };

        function deteksiLokasi() {
            let statusGps = document.getElementById('status-gps');
            if (navigator.geolocation) {
                statusGps.innerText = "Mencari posisi GPS...";
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        let latPembeli = position.coords.latitude;
                        let lonPembeli = position.coords.longitude;
                        
                        let jarak = hitungHaversine(latWarung, lonWarung, latPembeli, lonPembeli);
                        jarakKmGlobal = Math.max(1, parseFloat(jarak.toFixed(1)));
                        
                        document.getElementById('jarak-val').innerText = jarakKmGlobal;
                        document.getElementById('input-jarak-km').value = jarakKmGlobal;
                        statusGps.innerText = "Lokasi terdeteksi ✅";
                        statusGps.className = "text-green-700 font-semibold";
                        
                        hitungTotal();
                    },
                    function(error) {
                        statusGps.innerText = "Gagal (Default 3 KM digunakan)";
                        statusGps.className = "text-red-600 font-semibold";
                        jarakKmGlobal = 3;
                        hitungTotal();
                    },
                    { timeout: 10000, enableHighAccuracy: true }
                );
            } else {
                statusGps.innerText = "GPS tidak didukung browser ini";
                hitungTotal();
            }
        }

        function hitungHaversine(lat1, lon1, lat2, lon2) {
            function toRad(x) { return x * Math.PI / 180; }
            let R = 6371; 
            let dLat = toRad(lat2 - lat1);
            let dLon = toRad(lon2 - lon1);
            let a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                    Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * 
                    Math.sin(dLon / 2) * Math.sin(dLon / 2);
            let c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c;
        }

        function tambahKeKeranjang(id, nama, harga) {
            let item = keranjang.find(p => p.id === id);
            if (item) {
                item.qty += 1;
            } else {
                keranjang.push({ id, nama, harga, qty: 1 });
            }
            updateTampilanKeranjang();
            triggerCartAnimation();
        }

        function ubahQty(id, delta) {
            let item = keranjang.find(p => p.id === id);
            if (item) {
                item.qty += delta;
                if (item.qty <= 0) {
                    keranjang = keranjang.filter(p => p.id !== id);
                }
            }
            updateTampilanKeranjang();
        }

        function updateTampilanKeranjang() {
            let container = document.getElementById('list-keranjang');
            container.innerHTML = '';

            let totalQty = keranjang.reduce((sum, item) => sum + item.qty, 0);
            let badge = document.getElementById('badge-total-item');
            let floatingBadge = document.getElementById('floating-badge');

            if (totalQty > 0) {
                badge.innerText = totalQty;
                badge.classList.remove('hidden');
                floatingBadge.innerText = totalQty;
                floatingBadge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
                floatingBadge.classList.add('hidden');
            }

            if (keranjang.length === 0) {
                container.innerHTML = `<p class="text-gray-400 text-center py-4 text-xs italic">Mangkuk masih kosong. Silakan pilih topping di sebelah kiri!</p>`;
                hitungTotal();
                return;
            }

            let html = '';
            keranjang.forEach(item => {
                html += `
                    <div class="py-2 flex justify-between items-center text-xs">
                        <div>
                            <p class="font-bold text-gray-800">${item.nama}</p>
                            <p class="text-gray-500">Rp ${item.harga.toLocaleString()} x ${item.qty}</p>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button type="button" onclick="ubahQty('${item.id}', -1)" class="bg-gray-100 hover:bg-gray-200 px-2 py-1 rounded font-bold">-</button>
                            <span class="font-bold w-4 text-center">${item.qty}</span>
                            <button type="button" onclick="ubahQty('${item.id}', 1)" class="bg-gray-100 hover:bg-gray-200 px-2 py-1 rounded font-bold">+</button>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
            hitungTotal();
        }

        function triggerCartAnimation() {
            let mangkukCard = document.getElementById('mangkuk-seblak-section');
            mangkukCard.classList.add('animate-cart');
            setTimeout(() => {
                mangkukCard.classList.remove('animate-cart');
            }, 400);
        }

        function scrollToMangkuk() {
            let mangkukCard = document.getElementById('mangkuk-seblak-section');
            mangkukCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function hitungTotal() {
            let subtotalTopping = keranjang.reduce((sum, item) => sum + (item.harga * item.qty), 0);
            
            let subtotalSayur = 0;
            let checkboxesSayur = document.querySelectorAll('.sayur-checkbox:checked');
            checkboxesSayur.forEach((cb) => {
                subtotalSayur += parseInt(cb.getAttribute('data-harga'));
            });

            let levelPedasElement = document.getElementById('level-pedas');
            let biayaLevelPedas = 0;
            let infoBiayaLevel = document.getElementById('info-biaya-level');
            
            if (levelPedasElement && levelPedasElement.value.includes('Level 4')) {
                biayaLevelPedas = 1000;
                infoBiayaLevel.classList.is('hidden') ? infoBiayaLevel.classList.remove('hidden') : null;
            } else {
                infoBiayaLevel.classList.add('hidden');
            }

            let subtotalBelanja = subtotalTopping > 0 ? (subtotalTopping + hargaBumbu + biayaLevelPedas + subtotalSayur) : 0;
            
            let jarak = jarakKmGlobal;
            document.getElementById('jarak-val').innerText = jarak;
            document.getElementById('jarak-text').innerText = jarak;

            let ongkirAsli = jarak * tarifPerKm;
            let diskonOngkir = 0;

            if (subtotalBelanja > 50000 && subtotalTopping > 0) {
                diskonOngkir = 6000;
                document.getElementById('info-diskon-ongkir').classList.remove('hidden');
            } else {
                document.getElementById('info-diskon-ongkir').classList.add('hidden');
            }

            let ongkirFinal = Math.max(0, ongkirAsli - diskonOngkir);
            let total = subtotalBelanja + (subtotalTopping > 0 ? ongkirFinal : 0);

            document.getElementById('subtotal-topping-text').innerText = 'Rp ' + subtotalTopping.toLocaleString();
            document.getElementById('subtotal-sayur-text').innerText = 'Rp ' + subtotalSayur.toLocaleString();
            document.getElementById('ongkir-asli-text').innerText = 'Rp ' + ongkirAsli.toLocaleString();
            document.getElementById('total-text').innerText = 'Rp ' + total.toLocaleString();

            document.getElementById('input-detail-pesanan').value = JSON.stringify(keranjang);
            document.getElementById('input-total-harga').value = total;
            document.getElementById('input-ongkir').value = ongkirFinal;
        }

        function siapkanDataCheckout() {
            let levelPedas = document.getElementById('level-pedas').value;
            document.getElementById('input-level-pedas').value = levelPedas;

            let checkboxes = document.querySelectorAll('.sayur-checkbox:checked');
            let listSayur = [];
            checkboxes.forEach((cb) => {
                listSayur.push(cb.value + ' (Rp ' + parseInt(cb.getAttribute('data-harga')).toLocaleString() + ')');
            });
            document.getElementById('input-pilihan-sayur').value = listSayur.length > 0 ? listSayur.join(', ') : 'Tanpa Sayur';
        }
    </script>
</body>
</html>