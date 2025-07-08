<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: pages/dashboard.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: pages/dashboard.php');
        exit();
    } else {
        $error = 'Username atau password salah';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="Shortcut Icon" href="assets/img/favicon.ico">
    <title>Login – Pencatatan SEP</title>
    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Heroicons -->
    <script src="https://unpkg.com/heroicons@2.0.16/dist/heroicons.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Custom animation -->
    <style>
        @keyframes slideFade {
            0%   { opacity: 0; transform: translateY(20px); }
            100% { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col md:flex-row">
    <!-- Left side / Illustration -->
    <div class="relative hidden md:flex flex-1 items-center justify-center overflow-hidden">
        <!-- Gradient background -->
        <div class="absolute inset-0 bg-gradient-to-tr from-blue-600 via-purple-600 to-pink-500 opacity-80"></div>
        <!-- Decorative circles -->
        <div class="absolute w-80 h-80 bg-white/10 rounded-full top-10 -left-20 blur-3xl"></div>
        <div class="absolute w-72 h-72 bg-white/10 rounded-full bottom-16 -right-16 blur-3xl"></div>
        <!-- Branding text -->
        <div class="relative text-center text-white p-10">
            <h4 class="text-2xl font-black mb-4 drop-shadow-md">Pencatatan SEP Rawat Inap</h4>
            <h1 class="text-4xl font-black mb-4 drop-shadow-md">RSU SEBENING KASIH</h1>
            <p class="text-lg max-w-sm mx-auto opacity-90">Kelola data Anda dengan cepat, aman, dan modern.</p>
        </div>
    </div>

    <!-- Right side / Form -->
    <div class="flex flex-1 items-center justify-center p-6">
        <form class="w-full max-w-md bg-white/70 backdrop-blur-lg shadow-lg rounded-2xl p-8 animate-[slideFade_.5s_ease-out]" 
              method="POST" autocomplete="off">
            <h2 class="text-3xl font-extrabold text-center text-gray-800 mb-8">Selamat Datang 👋, Login Di sini</h2>

            <?php if ($error): ?>
                <script>
                    Swal.fire({ icon: 'error', title: 'Oops...', text: '<?= $error ?>' });
                </script>
            <?php endif; ?>

            <!-- Username -->
            <div class="mb-5 relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <svg class="h-5 w-5"><use href="#user" /></svg>
                </span>
                <input
                    class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    type="text" name="username" placeholder="Username" required>
            </div>

            <!-- Password -->
            <div class="mb-6 relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <svg class="h-5 w-5"><use href="#lock-closed" /></svg>
                </span>
                <input
                    class="w-full pl-10 pr-10 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    type="password" id="pwd" name="password" placeholder="Password" required>
                <!-- show/hide toggle -->
                <span onclick="togglePwd()" class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer text-gray-400">
                    <svg id="eye" class="h-5 w-5"><use href="#eye" /></svg>
                </span>
            </div>

            <button
                class="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold py-3 rounded-xl transition duration-150">
                Login
            </button>

            <p class="text-center text-sm text-gray-600 mt-6">
                Belum punya akun?
                <a href="register.php" class="text-blue-600 font-medium hover:underline">Daftar Akun</a>
            </p>
        </form>
    </div>

    <!-- Heroicons symbols -->
    <svg xmlns="http://www.w3.org/2000/svg" style="display:none;">
        <symbol id="user" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 12c2.761 0 5-2.239 5-5S14.761 2 12 2 7 4.239 7 7s2.239 5 5 5zm0 2c-3.314 0-9 1.657-9 5v3h18v-3c0-3.343-5.686-5-9-5z"/>
        </symbol>
        <symbol id="lock-closed" viewBox="0 0 24 24" fill="currentColor">
            <path d="M17 9V7a5 5 0 00-10 0v2H5v12h14V9h-2zm-8-2a3 3 0 016 0v2H9V7z"/>
        </symbol>
        <symbol id="eye" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 5C7 5 2.73 8.11 1 12c1.73 3.89 6 7 11 7s9.27-3.11 11-7c-1.73-3.89-6-7-11-7zm0 12a5 5 0 110-10 5 5 0 010 10z"/>
        </symbol>
        <symbol id="eye-off" viewBox="0 0 24 24" fill="currentColor">
            <path d="M2.808 1.394l19.799 19.799-1.414 1.414L18.99 19.4 17.7 18.11C15.885 19.284 13.951 20 12 20c-5 0-9.27-3.11-11-7 1.027-2.301 2.78-4.269 4.93-5.57L1.394 2.808 2.808 1.394zM12 7a5 5 0 00-5 5c0 .51.079 1.002.224 1.465l6.241-6.241A4.987 4.987 0 0012 7zm5 5a4.978 4.978 0 00-.224-1.465l-6.241 6.241A4.988 4.988 0 0017 12z"/>
        </symbol>
    </svg>

    <!-- Toggle password visibility -->
    <script>
        function togglePwd() {
            const pwd = document.getElementById('pwd');
            const eye = document.getElementById('eye');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                eye.setAttribute('href', '#eye-off');
            } else {
                pwd.type = 'password';
                eye.setAttribute('href', '#eye');
            }
        }
    </script>
</body>
</html>
