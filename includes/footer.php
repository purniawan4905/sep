    </main> <!-- Akhir konten utama -->

    <!-- Script logout SweetAlert -->
    <script>
    const logoutBtn = document.getElementById('logoutLink');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Yakin ingin logout?',
                text: "Sesi Anda akan dihentikan.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, logout',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '/pencatatansep/logout.php';
                }
            });
        });
    }
    </script>

    <!-- Footer -->
<!-- <footer class="border-t border-gray-200 bg-white text-center text-sm text-gray-500 p-4">
        <div class="max-w-6xl mx-auto flex flex-col sm:flex-row justify-between items-center">
          <p>&copy; <?= date('Y') ?> RS Sebening Kasih. All rights reserved.</p>
          <p>Developed with 💙 by <a href="#" class="text-blue-600 hover:underline">IT Team</a></p>
        </div>
      </footer> -->
</body>
</html>
