<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: /pages/dashboard.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (fullname, username, password) VALUES (?, ?, ?)");
        $stmt->execute([$fullname, $username, $password]);
        header('Location: login.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Username sudah digunakan.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="Shortcut Icon" href="assets/img/favicon.ico">
    <title>Daftar Akun – Pencatatan SEP</title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            background: linear-gradient(to top right, #4f46e5, #9333ea);
        }
        .glass {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.25);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center text-gray-800">

<div class="w-full max-w-md px-4">
    <div class="glass p-8 rounded-2xl shadow-lg w-full text-white animate-fade-in">
        <h2 class="text-2xl font-bold text-center mb-6">Buat Akun Baru</h2>

        <?php if ($error): ?>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Mendaftar',
                    text: '<?= $error ?>'
                });
            </script>
        <?php endif; ?>

        <form method="POST" autocomplete="off" class="space-y-5">
            <!-- Full Name -->
            <div>
                <label class="block text-sm font-medium mb-1">Nama Lengkap</label>
                <input type="text" name="fullname" required
                       class="w-full px-4 py-2 rounded-lg bg-white/70 focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-800 shadow-inner">
            </div>

            <!-- Username -->
            <div>
                <label class="block text-sm font-medium mb-1">Username</label>
                <input type="text" name="username" required
                       class="w-full px-4 py-2 rounded-lg bg-white/70 focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-800 shadow-inner">
            </div>

            <!-- Password -->
            <div>
                <label class="block text-sm font-medium mb-1">Password</label>
                <input type="password" name="password" required
                       class="w-full px-4 py-2 rounded-lg bg-white/70 focus:outline-none focus:ring-2 focus:ring-purple-500 text-gray-800 shadow-inner">
            </div>

            <!-- Submit Button -->
            <button type="submit"
                    class="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold py-3 rounded-lg shadow-lg transition">
                Daftar
            </button>

            <p class="text-center text-sm text-white mt-4">
                Sudah punya akun? <a href="login.php" class="underline hover:text-yellow-300">Login di sini</a>
            </p>
        </form>
    </div>
</div>

</body>
</html>
