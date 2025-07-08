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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center min-h-screen">
    <form method="POST" class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md space-y-6">
        <h2 class="text-3xl font-bold text-center text-gray-800">Buat Akun</h2>

        <?php if ($error): ?>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Mendaftar',
                    text: '<?= $error ?>'
                });
            </script>
        <?php endif; ?>

        <div>
            <label class="block mb-1 font-medium text-gray-700">Nama Lengkap</label>
            <input type="text" name="fullname" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>

        <div>
            <label class="block mb-1 font-medium text-gray-700">Username</label>
            <input type="text" name="username" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>

        <div>
            <label class="block mb-1 font-medium text-gray-700">Password</label>
            <input type="password" name="password" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>

        <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg transition duration-200">
            Daftar
        </button>

        <p class="text-center text-sm text-gray-600">
            Sudah punya akun? <a href="login.php" class="text-blue-600 hover:underline">Login di sini</a>
        </p>
    </form>
</body>
</html>
