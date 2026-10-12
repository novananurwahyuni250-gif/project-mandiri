<?php
// Sambungkan ke database
include 'koneksi.php';

// Pastikan halaman diakses melalui metode POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form POST dengan aman
    $nama              = mysqli_real_escape_string($conn, $_POST['nama']);
    $whatsapp          = mysqli_real_escape_string($conn, $_POST['whatsapp']);
    $alamat            = mysqli_real_escape_string($conn, $_POST['alamat']);
    $level_kepedasan   = mysqli_real_escape_string($conn, $_POST['level_kepedasan']);
    $pilihan_sayur     = mysqli_real_escape_string($conn, $_POST['pilihan_sayur']);
    $jarak_km          = floatval($_POST['jarak_km']);
    $ongkir_final      = floatval($_POST['ongkir']);
    $total_pembayaran  = floatval($_POST['total_harga']);
    $status_pesanan    = 'Baru'; // Status awal pesanan masuk

    $detail_json       = $_POST['detail_pesanan'];
    $keranjang         = json_decode($detail_json, true);

    if (empty($keranjang)) {
        echo "<script>alert('Mangkuk seblak Anda masih kosong!'); window.location='index.php';</script>";
        exit;
    }

    // Hitung ulang subtotal topping untuk memastikan akurasi data
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
    $biaya_level = strpos($level_kepedasan, 'Level 4') !== false ? 1000 : 0;
    $subtotal_belanja = $subtotal_topping + $harga_bumbu + $biaya_level;
    $diskon_ongkir = ($subtotal_belanja > 50000) ? 6000 : 0;

    // 1. Simpan data utama ke tabel 'pesanan'
    $query_simpan = "INSERT INTO pesanan (nama, whatsapp, alamat, level_kepedasan, pilihan_sayur, jarak_km, ongkir, total_harga, status_pesanan, tanggal) 
                     VALUES ('$nama', '$whatsapp', '$alamat', '$level_kepedasan', '$pilihan_sayur', '$jarak_km', '$ongkir_final', '$total_pembayaran', '$status_pesanan', NOW())";
    
    if (mysqli_query($conn, $query_simpan)) {
        $pesanan_id = mysqli_insert_id($conn); // Ambil ID pesanan terbaru

        // 2. Simpan ke tabel detail pesanan dan kurangi stok menu di database
        foreach ($keranjang as $item) {
            $menu_id = intval($item['id']);
            $nama_m  = mysqli_real_escape_string($conn, $item['nama']);
            $h_m     = intval($item['harga']);
            $q_m     = intval($item['qty']);
            
            // Simpan ke detail_pesanan (Pastikan tabel detail_pesanan sudah dibuat di database Anda)
            @mysqli_query($conn, "INSERT INTO detail_pesanan (pesanan_id, menu_id, nama_menu, harga, qty) VALUES ('$pesanan_id', '$menu_id', '$nama_m', '$h_m', '$q_m')");
            
            // Kurangi stok menu
            @mysqli_query($conn, "UPDATE menu SET stok = stok - $q_m WHERE id = '$menu_id'");
        }

        // 3. Susun format teks pesan WhatsApp untuk dikirim ke Admin
        $nomor_admin_wa = "6287751678770"; // Ganti dengan nomor WhatsApp Admin Anda

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

        // 4. Buka WhatsApp admin di tab baru, lalu arahkan user kembali ke index.php dengan notifikasi sukses & nomor WA untuk pelacakan
        echo "<script>
            window.open('" . $url_whatsapp . "', '_blank');
            window.location.href = 'index.php?cek_wa=" . $whatsapp . "&status=sukses';
        </script>";
        exit;

    } else {
        echo "<script>alert('Gagal menyimpan pesanan ke database: " . mysqli_error($conn) . "'); window.location='index.php';</script>";
        exit;
    }
} else {
    header("location: index.php");
    exit;
}
?>