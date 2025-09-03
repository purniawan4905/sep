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
  <title>Login – Pencatatan SEP</title>
  <link rel="Shortcut Icon" href="assets/img/favicon.ico">

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
            primary: { 50: '#f0f9ff', 100: '#e0f2fe', 200: '#bae6fd', 300: '#7dd3fc', 400: '#38bdf8', 500: '#0ea5e9', 600: '#0284c7', 700: '#0369a1' },
            secondary: { 50: '#faf5ff', 100: '#f3e8ff', 200: '#e9d5ff', 300: '#d8b4fe', 400: '#c084fc', 500: '#a855f7', 600: '#9333ea', 700: '#7c3aed' },
          }
        }
      }
    }
  </script>

  <!-- Particles.js -->
  <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Font & Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

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

    .logo-3d {
      filter: drop-shadow(0 8px 12px rgba(14, 165, 233, 0.2));
      transition: all 0.4s ease;
    }

    .logo-3d:hover {
      transform: translateY(-5px) rotate(5deg);
      filter: drop-shadow(0 12px 16px rgba(14, 165, 233, 0.3));
    }

    .floating {
      animation: floating 6s ease-in-out infinite;
    }

    @keyframes floating {
      0% { transform: translate(0, 0px); }
      50% { transform: translate(0, 15px); }
      100% { transform: translate(0, -0px); }
    }

    #particles-js {
      position: absolute;
      width: 100%;
      height: 100%;
      z-index: 0;
    }
  </style>
</head>
<body class="relative min-h-screen flex items-center justify-center overflow-hidden">
  <!-- Particle Background -->
  <div id="particles-js"></div>

  <!-- Login Container -->
  <div class="relative z-10 max-w-5xl w-full mx-6 md:mx-auto grid grid-cols-1 md:grid-cols-2 gap-10 p-8">
    <!-- Left Side -->
    <div class="hidden md:flex flex-col justify-center space-y-8 floating">
      <div class="flex items-center space-x-4 logo-3d">
        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-primary-400 to-secondary-400 flex items-center justify-center p-2 shadow-xl">
          <img src="assets/img/sbk2.png" alt="Logo" class="w-full h-full object-contain">
        </div>
        <h1 class="text-3xl font-bold bg-gradient-to-r from-primary-600 to-secondary-600 bg-clip-text text-transparent">RSU Sebening Kasih</h1>
      </div>
      <p class="text-gray-700 text-lg leading-relaxed">
        Sistem pencatatan SEP terintegrasi untuk rawat inap. Akses data pasien dengan cepat dan aman melalui dashboard interaktif.
      </p>
      <ul class="space-y-4 text-gray-600">
        <li class="flex items-center space-x-3">
          <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center">
            <i class="fas fa-bolt text-primary-600"></i>
          </div>
          <span>Akses cepat dan real-time</span>
        </li>
        <li class="flex items-center space-x-3">
          <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center">
            <i class="fas fa-shield-alt text-primary-600"></i>
          </div>
          <span>Keamanan data terjamin</span>
        </li>
        <li class="flex items-center space-x-3">
          <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center">
            <i class="fas fa-paint-brush text-primary-600"></i>
          </div>
          <span>Antarmuka modern dan responsif</span>
        </li>
      </ul>
    </div>

    <!-- Login Form -->
    <div class="glass-3d p-10 w-full">
      <div class="text-center mb-8">
        <div class="w-20 h-20 mx-auto rounded-2xl bg-gradient-to-br from-primary-400 to-secondary-400 flex items-center justify-center mb-6 shadow-lg">
          <i class="fas fa-user-md text-white text-3xl"></i>
        </div>
        <h2 class="text-3xl font-bold bg-gradient-to-r from-primary-600 to-secondary-600 bg-clip-text text-transparent">Selamat Datang</h2>
        <p class="text-gray-600 mt-2">Silakan login untuk melanjutkan</p>
      </div>

      <?php if ($error): ?>
        <script>
          Swal.fire({
            icon: 'error',
            title: 'Login Gagal',
            text: '<?= $error ?>',
            background: 'rgba(255, 255, 255, 0.95)',
            color: '#000',
            confirmButtonColor: '#0ea5e9'
          });
        </script>
      <?php endif; ?>

      <form method="POST" autocomplete="off" class="space-y-6">
        <div>
          <label for="username" class="block text-sm mb-2 font-medium text-gray-700">Username</label>
          <div class="relative">
            <span class="absolute left-4 top-4 text-primary-500"><i class="fas fa-user"></i></span>
            <input type="text" id="username" name="username" required
              class="input-3d w-full pl-12 pr-4 py-4 text-gray-800 focus:outline-none">
          </div>
        </div>

        <div>
          <label for="password" class="block text-sm mb-2 font-medium text-gray-700">Password</label>
          <div class="relative">
            <span class="absolute left-4 top-4 text-primary-500"><i class="fas fa-lock"></i></span>
            <input type="password" id="password" name="password" required
              class="input-3d w-full pl-12 pr-12 py-4 text-gray-800 focus:outline-none">
            <button type="button" onclick="togglePassword()" class="absolute right-4 top-4 text-primary-500 hover:text-primary-700">
              <i class="far fa-eye" id="eye-icon"></i>
            </button>
          </div>
        </div>

        <div class="flex items-center justify-between text-sm text-gray-600">
          <label class="flex items-center gap-2">
            <input type="checkbox" class="h-4 w-4 text-primary-500 focus:ring-primary-400 border-gray-300 rounded">
            Ingat saya
          </label>
          <a href="#" class="text-primary-600 hover:text-primary-800 font-medium hover:underline">Lupa password?</a>
        </div>

        <button type="submit" class="btn-gradient-3d w-full py-4 text-white font-semibold text-lg">Masuk</button>

        <p class="text-center text-gray-600 mt-6">
          Belum punya akun? 
          <a href="register.php" class="text-primary-600 hover:text-primary-800 font-medium hover:underline">Daftar disini</a>
        </p>
      </form>
    </div>
  </div>

  <!-- JS: Toggle Password -->
  <script>
    function togglePassword() {
      const pwd = document.getElementById('password');
      const icon = document.getElementById('eye-icon');
      if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
      } else {
        pwd.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
      }
    }
  </script>

  <!-- Init Particle.js -->
  <script>
    particlesJS("particles-js", {
      particles: {
        number: { value: 80, density: { enable: true, value_area: 800 } },
        color: { value: ["#0ea5e9", "#a855f7", "#7dd3fc", "#c084fc"] },
        shape: { type: "circle" },
        opacity: { value: 0.25, random: true },
        size: { value: 4, random: true },
        line_linked: {
          enable: true,
          distance: 150,
          color: "#7dd3fc",
          opacity: 0.2,
          width: 1
        },
        move: { 
          enable: true, 
          speed: 2,
          direction: "none",
          random: true,
          straight: false,
          out_mode: "out",
          bounce: false
        }
      },
      interactivity: {
        detect_on: "canvas",
        events: {
          onhover: { enable: true, mode: "repulse" },
          onclick: { enable: true, mode: "push" },
          resize: true
        },
        modes: {
          repulse: { distance: 100, duration: 0.4 },
          push: { particles_nb: 6 }
        }
      },
      retina_detect: true
    });
  </script>
</body>
<footer class="absolute bottom-0 w-full text-center py-3 text-sm text-white bg-gradient-to-r from-primary-500 to-secondary-500">
  <marquee behavior="scroll" direction="left" scrollamount="5" class="tracking-wide">
    © 2025 – RSU Sebening Kasih | Developed with ❤️ by Agus Cah Ganteng 😎
  </marquee>
</footer>
</html>