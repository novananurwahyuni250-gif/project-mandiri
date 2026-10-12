<?php
include 'koneksi.php';

// Proses Hapus Menu jika tombol hapus diklik
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM menu WHERE id = '$id_hapus'");
    header("Location: admin_menu.php");
    exit();
}

// Ambil semua data menu dari database
$result = mysqli_query($conn, "SELECT * FROM menu ORDER BY kategori ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Kelola Menu Seblak Prasmanan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 text-gray-800">

    <!-- Navbar Admin -->
    <header class="bg-red-600 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-xl font-black tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-gauge-high"></i> PANEL ADMIN SEBLAK
            </h1>
            <a href="index.php" target="_blank" class="bg-white text-red-600 px-3 py-1.5 rounded-lg text-xs font-bold shadow hover:bg-red-50 transition">
                <i class="fa-solid fa-globe"></i> Lihat Website
            </a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8">
        
        <!-- Header Halaman & Tombol Tambah -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-black text-gray-900">Kelola Menu & Topping</h2>
                <p class="text-xs text-gray-500 mt-1">Tambah, edit, atau hapus menu topping yang tampil di halaman pembeli.</p>
            </div>
            <a href="admin_tambah.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-xl text-xs font-bold shadow flex items-center gap-2 transition">
                <i class="fa-solid fa-plus"></i> Tambah Menu Baru
            </a>
        </div>

        <!-- Tabel Data Menu -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600 text-xs uppercase font-bold border-b border-gray-200">
                            <th class="p-4">Gambar</th>
                            <th class="p-4">Nama Menu</th>
                            <th class="p-4">Kategori</th>
                            <th class="p-4">Harga</th>
                            <th class="p-4">Status</th>
                            <th class="p-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-xs">
                        <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="p-4">
                                <img src="<?= htmlspecialchars($row['gambar']) ?>" alt="Menu" class="w-12 h-12 object-cover rounded-lg border shadow-sm">
                            </td>
                            <td class="p-4 font-bold text-gray-900"><?= htmlspecialchars($row['nama_menu']) ?></td>
                            <td class="p-4">
                                <span class="bg-red-100 text-red-700 px-2.5 py-1 rounded-full font-semibold">
                                    <?= htmlspecialchars($row['kategori']) ?>
                                </span>
                            </td>
                            <td class="p-4 font-extrabold text-red-600">Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
                            <td class="p-4">
                                <?php if ($row['status'] == 'tersedia') : ?>
                                    <span class="bg-green-100 text-green-700 px-2.5 py-1 rounded-full font-bold">Tersedia</span>
                                <?php else : ?>
                                    <span class="bg-gray-100 text-gray-500 px-2.5 py-1 rounded-full font-bold">Habis</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 text-center space-x-2">
                                <a href="admin_edit.php?id=<?= $row['id'] ?>" class="inline-block bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded-lg font-bold transition">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </a>
                                <a href="admin_menu.php?hapus=<?= $row['id'] ?>" onclick="return confirm('Yakin ingin menghapus menu ini?')" class="inline-block bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-lg font-bold transition">
                                    <i class="fa-solid fa-trash"></i> Hapus
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</body>
</html>