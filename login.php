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
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link rel="Shortcut Icon" href="assets/img/favicon.ico">
  <title>Login – Pencatatan SEP</title>

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
  body {
    background: linear-gradient(-45deg, #4f46e5, #9333ea, #4f46e5, #9333ea);
    background-size: 400% 400%;
    animation: gradientShift 12s ease infinite;
  }

  @keyframes gradientShift {
    0%   { background-position: 0% 50%; }
    50%  { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
  }

  .glass {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.25);
  }

  @keyframes float {
    0%, 100% { transform: translateY(0); }
    50%      { transform: translateY(-15px); }
  }

  .animate-float {
    animation: float 6s ease-in-out infinite;
  }

  .animate-float-delay {
    animation: float 8s ease-in-out infinite;
    animation-delay: 1.5s;
  }

  .animate-pulse-slow {
    animation: pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite;
  }

  .animate-spin-slow {
    animation: spin 10s linear infinite;
  }

  @keyframes fade-in {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .animate-fade-in {
    animation: fade-in 0.5s ease-out forwards;
  }
</style>
</head>
<body class="min-h-screen flex items-center justify-center text-gray-800">
    <!-- Floating SVG Icons -->
<div class="absolute inset-0 overflow-hidden -z-10">
  <!-- Icon 1 -->
  <svg class="absolute w-12 h-12 text-white/10 animate-float" style="top: 10%; left: 15%;" fill="currentColor" viewBox="0 0 24 24">
    <circle cx="12" cy="12" r="10" />
  </svg>

  <!-- Icon 2 -->
  <svg class="absolute w-16 h-16 text-white/10 animate-pulse-slow" style="top: 40%; left: 5%;" fill="currentColor" viewBox="0 0 24 24">
    <rect x="4" y="4" width="16" height="16" rx="2" />
  </svg>

  <!-- Icon 3 -->
  <svg class="absolute w-10 h-10 text-white/10 animate-spin-slow" style="top: 75%; left: 80%;" fill="currentColor" viewBox="0 0 24 24">
    <path d="M12 2L15 8H9L12 2Z" />
  </svg>

  <!-- Icon 4 -->
  <svg class="absolute w-14 h-14 text-white/10 animate-float-delay" style="top: 60%; left: 50%;" fill="currentColor" viewBox="0 0 24 24">
    <path d="M4 12a8 8 0 0116 0" />
  </svg>
</div>

<div class="w-full max-w-5xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-6 px-4">
  <!-- Left Branding -->
  <div class="hidden md:flex flex-col justify-center text-white space-y-6 p-6">
    <h1 class="text-4xl font-extrabold drop-shadow-md">RSU Sebening Kasih</h1>
    <p class="text-lg leading-relaxed max-w-md">Pencatatan SEP Rawat Inap kini lebih efisien, modern, dan aman. Masuk untuk mulai kelola data Anda.</p>
  </div>

  <!-- Right Login Form -->
  <div class="glass p-8 rounded-2xl shadow-lg w-full animate-fade-in">
    <h2 class="text-2xl font-bold text-center text-white mb-6">Silakan Login</h2>

    <?php if ($error): ?>
      <script>
        Swal.fire({ icon: 'error', title: 'Gagal Login', text: '<?= $error ?>' });
      </script>
    <?php endif; ?>

    <form method="POST" autocomplete="off" class="space-y-5">
      <!-- Username -->
      <div>
        <label for="username" class="block text-sm font-medium text-white mb-1">Username</label>
        <input type="text" name="username" id="username" required
               class="w-full px-4 py-3 rounded-lg bg-white/70 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-inner">
      </div>

      <!-- Password -->
      <div class="relative">
        <label for="password" class="block text-sm font-medium text-white mb-1">Password</label>
        <input type="password" name="password" id="password" required
               class="w-full px-4 py-3 rounded-lg bg-white/70 focus:outline-none focus:ring-2 focus:ring-purple-500 shadow-inner">
        <button type="button" onclick="togglePassword()"
                class="absolute right-3 top-[38px] text-sm text-gray-600 hover:text-gray-800">
          👁️
        </button>
      </div>

      <button
        class="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold py-3 rounded-lg shadow-lg transition">
        Login
      </button>

      <p class="text-sm text-center text-white mt-4">
        Belum punya akun? <a href="register.php" class="underline hover:text-yellow-300">Register</a>
      </p>
    </form>
  </div>
</div>

<!-- Toggle Password JS -->
<script>
  function togglePassword() {
    const pwd = document.getElementById('password');
    pwd.type = pwd.type === 'password' ? 'text' : 'password';
  }
</script>

</body>
</html>
