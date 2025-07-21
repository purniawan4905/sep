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
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            'sans': ['Inter', 'sans-serif'],
          },
          colors: {
            primary: {
              50: '#f0f9ff',
              100: '#e0f2fe',
              200: '#bae6fd',
              300: '#7dd3fc',
              400: '#38bdf8',
              500: '#0ea5e9',
              600: '#0284c7',
              700: '#0369a1',
              800: '#075985',
              900: '#0c4a6e',
            },
            secondary: {
              50: '#faf5ff',
              100: '#f3e8ff',
              200: '#e9d5ff',
              300: '#d8b4fe',
              400: '#c084fc',
              500: '#a855f7',
              600: '#9333ea',
              700: '#7e22ce',
              800: '#6b21a8',
              900: '#581c87',
            }
          }
        }
      }
    }
  </script>
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
  :root {
    --primary-500: #0ea5e9;
    --primary-600: #0284c7;
    --secondary-500: #a855f7;
    --secondary-600: #9333ea;
  }
  
  body {
    background: linear-gradient(135deg, var(--primary-600), var(--secondary-600));
    background-size: 200% 200%;
    animation: gradientShift 10s ease infinite;
    font-family: 'Inter', sans-serif;
  }

  @keyframes gradientShift {
    0%   { background-position: 0% 50%; }
    50%  { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
  }

  .glass {
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.18);
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
  }

  .glass:hover {
    background: rgba(255, 255, 255, 0.15);
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
    animation: spin 12s linear infinite;
  }

  @keyframes fade-in-up {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .animate-fade-in-up {
    animation: fade-in-up 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
  }

  .animate-delay-100 {
    animation-delay: 0.1s;
  }
  .animate-delay-200 {
    animation-delay: 0.2s;
  }
  .animate-delay-300 {
    animation-delay: 0.3s;
  }

  .input-field {
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.85);
  }

  .input-field:focus {
    background: rgba(255, 255, 255, 0.95);
    box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.3);
  }

  .btn-gradient {
    background: linear-gradient(135deg, var(--primary-500), var(--secondary-500));
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(168, 85, 247, 0.3);
  }

  .btn-gradient:hover {
    background: linear-gradient(135deg, var(--primary-600), var(--secondary-600));
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(168, 85, 247, 0.4);
  }

  .btn-gradient:active {
    transform: translateY(0);
  }

  .floating-shapes {
    filter: drop-shadow(0 10px 8px rgba(0, 0, 0, 0.04)) drop-shadow(0 4px 3px rgba(0, 0, 0, 0.1));
  }
</style>
</head>
<body class="min-h-screen flex items-center justify-center text-gray-800 antialiased">
  <!-- Floating Background Shapes -->
  <div class="absolute inset-0 overflow-hidden pointer-events-none">
    <!-- Floating circles -->
    <div class="absolute w-64 h-64 rounded-full bg-white/5 blur-xl top-1/4 -left-16 animate-float"></div>
    <div class="absolute w-72 h-72 rounded-full bg-white/5 blur-xl bottom-1/4 -right-16 animate-float-delay"></div>
    
    <!-- Floating triangles -->
    <div class="absolute w-40 h-40 bg-white/5 blur-xl top-1/3 right-1/4 animate-pulse-slow" style="clip-path: polygon(50% 0%, 0% 100%, 100% 100%);"></div>
    <div class="absolute w-48 h-48 bg-white/5 blur-xl bottom-1/3 left-1/4 animate-pulse-slow" style="clip-path: polygon(50% 0%, 0% 100%, 100% 100%);"></div>
  </div>

  <div class="w-full max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-8 px-6">
    <!-- Left Branding Section -->
    <div class="hidden lg:flex flex-col justify-center text-white space-y-8 p-8 animate-fade-in-up">
      <div class="flex items-center space-x-4">
        <div class="w-14 h-14 flex items-center justify-center rounded-xl bg-white/10 backdrop-blur-sm">
          <img src="assets/img/sbk2.png" class="w-8 h-8"></img>
        </div>
        <h1 class="text-4xl font-bold tracking-tight">RSU Sebening Kasih</h1>
      </div>
      
      <p class="text-lg leading-relaxed text-white/90 max-w-md">
        Sistem pencatatan SEP terintegrasi untuk rawat inap. Akses data pasien dengan cepat dan aman.
      </p>
      
      <div class="mt-6 space-y-4">
        <div class="flex items-center space-x-3">
          <div class="w-8 h-8 flex items-center justify-center rounded-full bg-white/10 backdrop-blur-sm">
            <i class="fas fa-check text-sm text-white"></i>
          </div>
          <span class="text-white/90">Akses cepat dan real-time</span>
        </div>
        <div class="flex items-center space-x-3">
          <div class="w-8 h-8 flex items-center justify-center rounded-full bg-white/10 backdrop-blur-sm">
            <i class="fas fa-check text-sm text-white"></i>
          </div>
          <span class="text-white/90">Keamanan data terjamin</span>
        </div>
        <div class="flex items-center space-x-3">
          <div class="w-8 h-8 flex items-center justify-center rounded-full bg-white/10 backdrop-blur-sm">
            <i class="fas fa-check text-sm text-white"></i>
          </div>
          <span class="text-white/90">Antarmuka modern dan intuitif</span>
        </div>
      </div>
    </div>

    <!-- Right Login Form -->
    <div class="glass p-10 rounded-2xl shadow-2xl w-full animate-fade-in-up animate-delay-100">
      <div class="text-center mb-8">
        <div class="w-20 h-20 flex items-center justify-center rounded-2xl bg-gradient-to-br from-primary-500 to-secondary-500 shadow-lg mx-auto mb-4">
          <i class="fas fa-user-md text-3xl text-white"></i>
        </div>
        <h2 class="text-3xl font-bold text-white">Selamat Datang</h2>
        <p class="text-white/80 mt-2">Masuk untuk mengakses dashboard</p>
      </div>

      <?php if ($error): ?>
        <script>
          Swal.fire({
            icon: 'error',
            title: 'Gagal Login',
            text: '<?= $error ?>',
            background: 'rgba(255, 255, 255, 0.9)',
            backdrop: 'rgba(0, 0, 0, 0.4)'
          });
        </script>
      <?php endif; ?>

      <form method="POST" autocomplete="off" class="space-y-6">
        <!-- Username -->
        <div class="space-y-2 animate-fade-in-up animate-delay-200">
          <label for="username" class="block text-sm font-medium text-white/90">Username</label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <i class="fas fa-user text-gray-400"></i>
            </div>
            <input type="text" name="username" id="username" required
                   class="input-field w-full pl-10 pr-4 py-3 rounded-xl focus:outline-none focus:ring-0">
          </div>
        </div>

        <!-- Password -->
        <div class="space-y-2 animate-fade-in-up animate-delay-300">
          <label for="password" class="block text-sm font-medium text-white/90">Password</label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <i class="fas fa-lock text-gray-400"></i>
            </div>
            <input type="password" name="password" id="password" required
                   class="input-field w-full pl-10 pr-4 py-3 rounded-xl focus:outline-none focus:ring-0">
            <button type="button" onclick="togglePassword()"
                    class="absolute right-3 top-3.5 text-gray-400 hover:text-gray-600 transition-colors">
              <i class="far fa-eye"></i>
            </button>
          </div>
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between animate-fade-in-up animate-delay-300">
          <div class="flex items-center">
            <input id="remember" name="remember" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-primary-500 focus:ring-primary-500">
            <label for="remember" class="ml-2 block text-sm text-white/80">Ingat saya</label>
          </div>
          <div class="text-sm">
            <a href="#" class="font-medium text-white hover:text-primary-200 transition-colors">Lupa password?</a>
          </div>
        </div>

        <button type="submit" class="btn-gradient w-full text-white font-semibold py-3.5 rounded-xl mt-6 animate-fade-in-up animate-delay-400">
          Masuk Sekarang
        </button>

        <div class="text-center text-sm text-white/80 mt-6 animate-fade-in-up animate-delay-500">
          Belum punya akun? 
          <a href="register.php" class="font-medium text-white hover:text-primary-200 underline underline-offset-4 transition-colors">
            Daftar disini
          </a>
        </div>
      </form>
    </div>
  </div>

  <!-- Toggle Password JS -->
  <script>
    function togglePassword() {
      const pwd = document.getElementById('password');
      const icon = document.querySelector('#password + button i');
      if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
      } else {
        pwd.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
      }
    }

    // Add input focus effects
    document.querySelectorAll('.input-field').forEach(input => {
      input.addEventListener('focus', function() {
        this.parentElement.querySelector('i').classList.add('text-primary-500');
      });
      input.addEventListener('blur', function() {
        this.parentElement.querySelector('i').classList.remove('text-primary-500');
      });
    });
  </script>
</body>
</html>