<?php
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_menu  = htmlspecialchars($_POST['nama_menu']);
    $kategori   = htmlspecialchars($_POST['kategori']);
    $harga      = htmlspecialchars($_POST['harga']);
    $deskripsi  = htmlspecialchars($_POST['deskripsi']);
    $status     = htmlspecialchars($_POST['status']);
    
    // Untuk gambar, Anda bisa memasukkan URL gambar atau link folder (misal: img/namafile.jpg)
    $gambar     = htmlspecialchars($_POST['gambar']);

    $query = "INSERT INTO menu (nama_menu, kategori, harga, deskripsi, status, gambar) VALUES ('$nama_menu', '$kategori', '$harga', '$deskripsi', '$status', '$gambar')";
    
    if (mysqli_query($conn, $query)) {
        header("Location: admin_menu.php");
        exit();
    } else {
        $error = "Gagal menambah menu: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Tambah Menu Baru</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 text-gray-800">

    <main class="max-w-2xl mx-auto px-4 py-10">
        <div class="bg-white p-8 rounded-2xl shadow-md border border-gray-200">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-black text-gray-900"><i class="fa-solid fa-plus-circle text-red-600"></i> Tambah Topping / Menu Baru</h2>
                <a href="admin_menu.php" class="text-xs text-gray-500 hover:text-red-600 font-bold"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
            </div>

            <?php if (isset($error)) : ?>
                <div class="bg-red-100 text-red-700 p-3 rounded-lg text-xs mb-4 font-bold"><?= $error ?></div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Nama Menu / Topping</label>
                    <input type="text" name="nama_menu" required class="w-full border rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-red-500 outline-none" placeholder="Contoh: Kerupuk Mawar Merah">
                </div>
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Kategori</label>
                    <select name="kategori" required class="w-full border rounded-xl px-3 py-2.5 bg-white focus:ring-2 focus:ring-red-500 outline-none">
                        <option value="Kerupuk">Kerupuk</option>
                        <option value="Seafood">Seafood</option>
                        <option value="Mie">Mie</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Harga (Rp)</label>
                    <input type="number" name="harga" required class="w-full border rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-red-500 outline-none" placeholder="Contoh: 2000">
                </div>
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Link / URL Gambar</label>
                    <input type="text" name="gambar" required class="w-full border rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-red-500 outline-none" placeholder="https://images.unsplash.com/... atau folder gambar">
                </div>
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Deskripsi Singkat</label>
                    <textarea name="deskripsi" rows="2" class="w-full border rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-red-500 outline-none" placeholder="Keterangan singkat menu"></textarea>
                </div>
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Status Ketersediaan</label>
                    <select name="status" class="w-full border rounded-xl px-3 py-2.5 bg-white focus:ring-2 focus:ring-red-500 outline-none">
                        <option value="tersedia">Tersedia</option>
                        <option value="habis">Habis</option>
                    </select>
                </div>

                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-xl transition shadow-lg shadow-red-100 text-sm mt-4">
                    Simpan Menu Baru
                </button>
            </form>
        </div>
    </main>
</body>
</html>