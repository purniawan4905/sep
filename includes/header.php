<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <link rel="Shortcut Icon" href="../../assets/img/favicon.ico">
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Pencatatan SEP</title>
  <!-- Tailwind -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen flex font-sans antialiased">

<!-- Sidebar -->
<aside class="w-64 bg-white shadow-lg border-r border-gray-200 h-screen fixed flex flex-col justify-between">
  <div>
    <div class="px-6 py-4 text-2xl font-extrabold text-blue-600 tracking-tight border-b border-gray-200">
      <a href="/pencatatansep/pages/dashboard.php">RS Sebening Kasih</a>
    </div>
    <nav class="mt-4 space-y-1 px-4">
      <!-- Dashboard -->
      <a href="/pencatatansep/pages/dashboard.php"
         class="flex items-center gap-3 py-2 px-4 rounded-lg hover:bg-blue-50 transition text-gray-700 group">
        <svg class="h-5 w-5 text-blue-500 group-hover:text-blue-700 transition" xmlns="http://www.w3.org/2000/svg" fill="none"
             viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 12l2-2m0 0l7-7 7 7M13 5v6h6m-6 4v6m-4-6v6" />
        </svg>
        <span>Dashboard</span>
      </a>

      <!-- Records -->
      <a href="/pencatatansep/pages/records/index.php"
         class="flex items-center gap-3 py-2 px-4 rounded-lg hover:bg-blue-50 transition text-gray-700 group">
        <svg class="h-5 w-5 text-green-500 group-hover:text-green-700 transition" xmlns="http://www.w3.org/2000/svg" fill="none"
             viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 6h16M4 12h16M4 18h16" />
        </svg>
        <span>Rawat Inap</span>
      </a>

        <!-- Diagnosa -->
      <a href="/pencatatansep/pages/diagnosa/index.php"
        class="flex items-center gap-3 py-2 px-4 rounded-lg hover:bg-blue-50 transition text-gray-700 group">
        <svg class="h-5 w-5 text-purple-500 group-hover:text-purple-700 transition" xmlns="http://www.w3.org/2000/svg" fill="none"
            viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 17v-2a4 4 0 014-4h6m-6 0V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2h4" />
        </svg>
        <span>Data Diagnosa</span>
      </a>

      <!-- ICD -->
      <a href="/pencatatansep/pages/icd/index.php"
        class="flex items-center gap-3 py-2 px-4 rounded-lg hover:bg-blue-50 transition text-gray-700 group">
        <svg class="h-5 w-5 text-yellow-500 group-hover:text-yellow-700 transition" xmlns="http://www.w3.org/2000/svg" fill="none"
            viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 8c-1.1 0-2 .9-2 2m0 4v-4m0 4h4m4-8v8a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2h4" />
        </svg>
        <span>Data ICD</span>
      </a>

      <!-- Data Dokumen -->
      <a href="/pencatatansep/pages/dokumen/index.php"
        class="flex items-center gap-3 py-2 px-4 rounded-lg hover:bg-blue-50 transition text-gray-700 group">
        <svg class="h-5 w-5 text-yellow-500 group-hover:text-yellow-700 transition" xmlns="http://www.w3.org/2000/svg" fill="none"
            viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M7 7h10M7 11h10M7 15h6M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z" />
        </svg>
        <span>Data Dokumen</span>
      </a>

          </nav>
        </div>

        
  <!-- Logout -->
  <div class="px-4 py-4">
    <a href="/pencatatansep/logout.php"
       id="logoutLink"
       class="flex items-center justify-between py-2 px-4 rounded-lg text-red-600 hover:bg-red-50 transition group w-full">

        <div class="flex items-center gap-3">
            <svg class="h-5 w-5 text-red-500 group-hover:text-red-700 transition"
                 xmlns="http://www.w3.org/2000/svg" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1" />
            </svg>
            <span>Logout</span>
        </div>

        <!-- Nama Pengguna dari Session -->
        <span class="text-sm text-gray-500">
            <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>
        </span>
    </a>
</div>
</aside>

<!-- Main content -->
<main class="flex-1 ml-64 p-6">
