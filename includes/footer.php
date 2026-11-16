</div>
<!-- End Main Content -->

<!-- FOOTER -->
<footer class="footer">
    <?php
    // Ambil semua kategori dari database
    $footer_kategori = [];
    if (isset($koneksi)) {
        $res_kat = mysqli_query($koneksi, "SELECT id, nama_kategori FROM kategori ORDER BY nama_kategori ASC");
        if ($res_kat) while ($fk = mysqli_fetch_assoc($res_kat)) $footer_kategori[] = $fk;
    }
    ?>
    <div class="container">
        <div class="footer-grid">
            <!-- Brand -->
            <div>
                <div class="footer-brand"><i class="fas fa-utensils"></i> DapurIn</div>
                <p class="footer-desc">
                    Platform berbagi resep masakan terlengkap. Temukan inspirasi masakan dari berbagai kategori, mulai dari sarapan hingga dessert.
                </p>
            </div>

            <!-- Navigasi + Kategori dalam 1 div, 2 kolom di mobile -->
            <div class="footer-nav-kat">
                <!-- Navigasi -->
                <div class="footer-nav-col">
                    <h4 class="footer-title">Navigasi</h4>
                    <div class="footer-links">
                        <a href="/index.php">Beranda</a>
                        <a href="/resep.php">Semua Resep</a>
                        <a href="/kategori.php">Kategori</a>
                        <a href="/populer.php">Terpopuler</a>
                    </div>
                </div>

                <!-- Kategori dari DB -->
                <div class="footer-kat-col">
                    <h4 class="footer-title">Kategori</h4>
                    <div class="footer-links">
                        <?php if (!empty($footer_kategori)): ?>
                            <?php foreach ($footer_kategori as $fk): ?>
                                <a href="/resep.php?kategori=<?= urlencode(strtolower($fk['nama_kategori'])) ?>">
                                    <?= htmlspecialchars($fk['nama_kategori']) ?>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <a href="/kategori.php">Lihat Kategori</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Akun -->
            <div>
                <h4 class="footer-title">Akun</h4>
                <div class="footer-links">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="/user/profil.php">Profil Saya</a>
                        <a href="/user/resep-saya.php">Resep Saya</a>
                        <a href="/user/favorit.php">Favorit</a>
                        <a href="/logout.php">Keluar</a>
                    <?php else: ?>
                        <a href="/login.php">Masuk</a>
                        <a href="/register.php">Daftar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <span>DapurIn</span>. All rights reserved.</p>
        </div>
    </div>
</footer>

<!-- =====================================================
     JAVASCRIPT GLOBAL
     ===================================================== -->
<script>
// Navbar scroll effect
window.addEventListener('scroll', () => {
    const navbar = document.getElementById('navbar');
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});

// Toggle hamburger menu
function toggleMenu() {
    const menu = document.getElementById('navbar-menu');
    const hamburger = document.getElementById('hamburger');
    menu.classList.toggle('open');
    hamburger.classList.toggle('open');
}

// Toggle dropdown avatar — tap buka, tap lagi tutup
document.addEventListener('DOMContentLoaded', function() {
    const avatar = document.querySelector('.user-avatar');
    const dropdown = document.querySelector('.user-dropdown');
    if (!avatar || !dropdown) return;

    let skipNext = false;

    avatar.addEventListener('click', function(e) {
        skipNext = true;
        dropdown.classList.toggle('open');
    });

    document.addEventListener('click', function(e) {
        if (skipNext) { skipNext = false; return; }
        if (!dropdown.contains(e.target)) {
            dropdown.classList.remove('open');
        }
    });
});

// Animate on scroll
const animateElements = document.querySelectorAll('.animate');
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
        }
    });
}, { threshold: 0.1 });

animateElements.forEach(el => observer.observe(el));

// Auto hide alert
const alerts = document.querySelectorAll('.alert');
alerts.forEach(alert => {
    setTimeout(() => {
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-10px)';
        alert.style.transition = 'all 0.4s ease';
        setTimeout(() => alert.remove(), 400);
    }, 4000);
});

// Counter animation (untuk hero stats)
function animateCounter(element, target, duration = 2000) {
    let start = 0;
    const step = target / (duration / 16);
    const timer = setInterval(() => {
        start += step;
        if (start >= target) {
            element.textContent = target + '+';
            clearInterval(timer);
        } else {
            element.textContent = Math.floor(start) + '+';
        }
    }, 16);
}

// Jalankan counter saat elemen masuk viewport
const counters = document.querySelectorAll('.counter');
const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const target = parseInt(entry.target.getAttribute('data-target'));
            animateCounter(entry.target, target);
            counterObserver.unobserve(entry.target);
        }
    });
});
counters.forEach(counter => counterObserver.observe(counter));
</script>

</body>
</html>
