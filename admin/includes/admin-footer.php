    </div><!-- end admin-content -->
</main><!-- end admin-main -->
</div><!-- end admin-wrapper -->

<script>
// Auto hide alerts
document.querySelectorAll('.alert').forEach(el => {
    setTimeout(() => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(-8px)';
        el.style.transition = 'all 0.4s ease';
        setTimeout(() => el.remove(), 400);
    }, 4000);
});

// ===== SIDEBAR MOBILE =====
function toggleSidebar() {
    const sidebar = document.querySelector('.admin-sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    sidebar.classList.toggle('open');
    overlay.classList.toggle('open');
}

function closeSidebar() {
    const sidebar = document.querySelector('.admin-sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    if (sidebar) sidebar.classList.remove('open');
    if (overlay) overlay.classList.remove('open');
}

// Tutup sidebar saat klik nav item di mobile
document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', () => {
        if (window.innerWidth <= 768) closeSidebar();
    });
});
</script>
</body>
</html>
