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
    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #faf5ff 100%);
      min-height: 100vh;
    }

    .glass-3d {
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.8) 0%, rgba(255, 255, 255, 0.9) 100%);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-radius: 24px;
      box-shadow: 
        0 20px 40px rgba(14, 165, 233, 0.15),
        0 10px 20px rgba(168, 85, 247, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.5),
        0 0 0 1px rgba(255, 255, 255, 0.3);
      transform-style: preserve-3d;
      transition: all 0.4s ease;
    }

    .glass-3d:hover {
      transform: translateY(-5px) rotateX(2deg);
      box-shadow: 
        0 25px 50px rgba(14, 165, 233, 0.2),
        0 15px 30px rgba(168, 85, 247, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.5),
        0 0 0 1px rgba(255, 255, 255, 0.3);
    }

    .btn-gradient-3d {
      background: linear-gradient(135deg, #0ea5e9 0%, #a855f7 100%);
      box-shadow: 
        0 4px 14px rgba(168, 85, 247, 0.35),
        0 6px 20px rgba(14, 165, 233, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.3);
      border-radius: 14px;
      transition: all 0.3s ease;
      transform: translateY(0);
      position: relative;
      overflow: hidden;
    }

    .btn-gradient-3d:before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
      transition: all 0.5s ease;
    }

    .btn-gradient-3d:hover {
      transform: translateY(-3px);
      box-shadow: 
        0 8px 22px rgba(168, 85, 247, 0.4),
        0 10px 24px rgba(14, 165, 233, 0.25),
        inset 0 1px 0 rgba(255, 255, 255, 0.3);
    }

    .btn-gradient-3d:hover:before {
      left: 100%;
    }

    .btn-gradient-3d:active {
      transform: translateY(1px);
      box-shadow: 
        0 2px 10px rgba(168, 85, 247, 0.3),
        0 4px 12px rgba(14, 165, 233, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.3);
    }

    .input-3d {
      background: rgba(255, 255, 255, 0.9);
      border-radius: 14px;
      box-shadow: 
        inset 0 2px 4px rgba(0, 0, 0, 0.05),
        0 4px 12px rgba(14, 165, 233, 0.1),
        0 0 0 1px rgba(14, 165, 233, 0.1);
      transition: all 0.3s ease;
    }

    .input-3d:focus {
      box-shadow: 
        inset 0 2px 4px rgba(0, 0, 0, 0.05),
        0 6px 20px rgba(14, 165, 233, 0.15),
        0 0 0 2px rgba(14, 165, 233, 0.3);
      transform: translateY(-2px);
    }

    .floating {
      animation: floating 6s ease-in-out infinite;
    }

    @keyframes floating {
      0% { transform: translate(0, 0px); }
      50% { transform: translate(0, 15px); }
      100% { transform: translate(0, -0px); }
    }

    .password-strength {
      height: 6px;
      background: rgba(0, 0, 0, 0.1);
      margin-top: 8px;
      border-radius: 3px;
      overflow: hidden;
    }

    .password-strength-fill {
      height: 100%;
      width: 0%;
      transition: width 0.3s ease, background 0.3s ease;
      border-radius: 3px;
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
    .animate-delay-150 {
      animation-delay: 0.15s;
    }
    .animate-delay-200 {
      animation-delay: 0.2s;
    }
    .animate-delay-300 {
      animation-delay: 0.3s;
    }
    .animate-delay-400 {
      animation-delay: 0.4s;
    }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center text-gray-800 antialiased p-4">
  <!-- Background Elements -->
  <div class="absolute inset-0 overflow-hidden pointer-events-none">
    <!-- Floating circles -->
    <div class="absolute w-64 h-64 rounded-full bg-primary-200/30 blur-xl top-1/4 -left-16"></div>
    <div class="absolute w-72 h-72 rounded-full bg-secondary-200/30 blur-xl bottom-1/4 -right-16"></div>
    
    <!-- Floating triangles -->
    <div class="absolute w-40 h-40 bg-primary-300/20 blur-xl top-1/3 right-1/4" style="clip-path: polygon(50% 0%, 0% 100%, 100% 100%);"></div>
    <div class="absolute w-48 h-48 bg-secondary-300/20 blur-xl bottom-1/3 left-1/4" style="clip-path: polygon(50% 0%, 0% 100%, 100% 100%);"></div>
  </div>

  <div class="w-full max-w-md">
    <div class="glass-3d p-10 w-full">
      <div class="text-center mb-8">
        <div class="w-20 h-20 flex items-center justify-center rounded-2xl bg-gradient-to-br from-primary-400 to-secondary-400 shadow-lg mx-auto mb-4 floating">
          <i class="fas fa-user-plus text-3xl text-white"></i>
        </div>
        <h2 class="text-3xl font-bold bg-gradient-to-r from-primary-600 to-secondary-600 bg-clip-text text-transparent">Buat Akun Baru</h2>
        <p class="text-gray-600 mt-2">Isi formulir untuk mendaftar</p>
      </div>

      <?php if ($error): ?>
        <script>
          Swal.fire({
            icon: 'error',
            title: 'Gagal Mendaftar',
            text: '<?= $error ?>',
            background: 'rgba(255, 255, 255, 0.95)',
            color: '#000',
            confirmButtonColor: '#0ea5e9'
          });
        </script>
      <?php endif; ?>

      <form method="POST" autocomplete="off" class="space-y-6">
        <!-- Full Name -->
        <div class="space-y-2 animate-fade-in-up animate-delay-100">
          <label for="fullname" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <i class="fas fa-user text-primary-500"></i>
            </div>
            <input type="text" name="fullname" id="fullname" required
                   class="input-3d w-full pl-10 pr-4 py-4 focus:outline-none">
          </div>
        </div>

        <!-- Username -->
        <div class="space-y-2 animate-fade-in-up animate-delay-150">
          <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <i class="fas fa-at text-primary-500"></i>
            </div>
            <input type="text" name="username" id="username" required
                   class="input-3d w-full pl-10 pr-4 py-4 focus:outline-none">
          </div>
        </div>

        <!-- Password -->
        <div class="space-y-2 animate-fade-in-up animate-delay-200">
          <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <i class="fas fa-lock text-primary-500"></i>
            </div>
            <input type="password" name="password" id="password" required
                   class="input-3d w-full pl-10 pr-12 py-4 focus:outline-none"
                   oninput="checkPasswordStrength(this.value)">
            <button type="button" onclick="togglePassword()"
                    class="absolute right-3 top-4 text-primary-500 hover:text-primary-700 transition-colors">
              <i class="far fa-eye"></i>
            </button>
          </div>
          <div class="password-strength">
            <div class="password-strength-fill" id="password-strength-bar"></div>
          </div>
          <p class="text-xs text-gray-500 mt-1">Gunakan minimal 8 karakter dengan kombinasi huruf dan angka</p>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn-gradient-3d w-full text-white font-semibold py-4 rounded-xl mt-6 animate-fade-in-up animate-delay-300">
          Daftar Sekarang
        </button>

        <div class="text-center text-gray-600 mt-6 animate-fade-in-up animate-delay-400">
          Sudah punya akun? 
          <a href="login.php" class="font-medium text-primary-600 hover:text-primary-800 underline underline-offset-4 transition-colors">
            Login disini
          </a>
        </div>
      </form>
    </div>
  </div>

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

    function checkPasswordStrength(password) {
      const strengthBar = document.getElementById('password-strength-bar');
      let strength = 0;
      
      // Length check
      if (password.length >= 8) strength += 25;
      if (password.length >= 12) strength += 15;
      
      // Contains numbers
      if (/\d/.test(password)) strength += 20;
      
      // Contains lowercase and uppercase
      if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 15;
      
      // Contains special chars
      if (/[^a-zA-Z0-9]/.test(password)) strength += 25;
      
      // Update strength bar
      strength = Math.min(strength, 100);
      strengthBar.style.width = strength + '%';
      
      // Change color based on strength
      if (strength < 40) {
        strengthBar.style.background = '#ef4444'; // red
      } else if (strength < 70) {
        strengthBar.style.background = '#f59e0b'; // amber
      } else {
        strengthBar.style.background = '#10b981'; // emerald
      }
    }

    // Add input focus effects
    document.querySelectorAll('.input-3d').forEach(input => {
      input.addEventListener('focus', function() {
        this.parentElement.querySelector('i').classList.add('text-secondary-500');
      });
      input.addEventListener('blur', function() {
        this.parentElement.querySelector('i').classList.remove('text-secondary-500');
      });
    });
  </script>
</body>
</html>