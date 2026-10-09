</div>
<!-- End Main Content -->

<!-- FOOTER -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <!-- Brand -->
            <div>
                <div class="footer-brand"><i class="fas fa-utensils"></i> ResepMasakan</div>
                <p class="footer-desc">
                    Platform berbagi resep masakan terlengkap. Temukan inspirasi masakan dari berbagai kategori, mulai dari sarapan hingga dessert.
                </p>
            </div>

            <!-- Navigasi -->
            <div>
                <h4 class="footer-title">Navigasi</h4>
                <div class="footer-links">
                    <a href="/resep-masakan/index.php">Beranda</a>
                    <a href="/resep-masakan/resep.php">Semua Resep</a>
                    <a href="/resep-masakan/kategori.php">Kategori</a>
                    <a href="/resep-masakan/populer.php">Terpopuler</a>
                </div>
            </div>

            <!-- Kategori -->
            <div>
                <h4 class="footer-title">Kategori</h4>
                <div class="footer-links">
                    <a href="/resep-masakan/resep.php?kategori=ayam">Ayam</a>
                    <a href="/resep-masakan/resep.php?kategori=ikan">Ikan</a>
                    <a href="/resep-masakan/resep.php?kategori=sayuran">Sayuran</a>
                    <a href="/resep-masakan/resep.php?kategori=dessert">Dessert</a>
                </div>
            </div>

            <!-- Akun -->
            <div>
                <h4 class="footer-title">Akun</h4>
                <div class="footer-links">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="/resep-masakan/user/profil.php">Profil Saya</a>
                        <a href="/resep-masakan/user/resep-saya.php">Resep Saya</a>
                        <a href="/resep-masakan/user/favorit.php">Favorit</a>
                        <a href="/resep-masakan/logout.php">Keluar</a>
                    <?php else: ?>
                        <a href="/resep-masakan/login.php">Masuk</a>
                        <a href="/resep-masakan/register.php">Daftar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <span>ResepMasakan</span>. Dibuat oleh <span>Siti Fatimah Nur Az-Zahra</span></p>
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
