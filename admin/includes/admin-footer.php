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

// Tutup sidebar saat klik nav item di mobile
document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', () => {
        if (window.innerWidth <= 768) closeSidebar();
    });
});

// Set initial state sidebar di mobile
if (window.innerWidth <= 768) {
    const sidebar = document.querySelector('.admin-sidebar');
    if (sidebar) {
        sidebar.setAttribute('data-open', 'false');
        sidebar.style.cssText = `
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 260px !important;
            height: 100% !important;
            z-index: 99999 !important;
            transform: translateX(-100%) !important;
            visibility: hidden !important;
        `;
    }
}
</script>
</body>
</html>
