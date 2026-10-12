<?php
session_start();
// Password sederhana untuk admin (bisa Anda ubah sesuai keinginan)
$password_admin = "admin123"; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($_POST['password'] === $password_admin) {
        $_SESSION['login_admin'] = true;
        header("Location: admin_pesanan.php");
        exit();
    } else {
        $error = "Password admin salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Seblak Prasmanan</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-red-50 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-2xl shadow-md border border-red-100 w-96">
        <h2 class="text-xl font-black text-red-600 mb-2 text-center">LOGIN ADMIN</h2>
        <p class="text-xs text-gray-500 mb-6 text-center">Masuk untuk memantau pesanan masuk secara real-time.</p>

        <?php if (isset($error)) : ?>
            <div class="bg-red-100 text-red-700 p-2.5 rounded-lg text-xs mb-4 font-bold text-center"><?= $error ?></div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-4 text-xs">
            <div>
                <label class="block font-bold text-gray-700 mb-1">Password Admin</label>
                <input type="password" name="password" required class="w-full border rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-red-500 outline-none" placeholder="Masukkan password...">
            </div>
            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-xl transition shadow-lg shadow-red-100 text-sm">
                Masuk ke Dashboard
            </button>
        </form>
        <div class="mt-4 text-center">
            <a href="index.php" class="text-xs text-gray-400 hover:text-red-600 font-semibold">&larr; Kembali ke Website Pembeli</a>
        </div>
    </div>
</body>
</html>