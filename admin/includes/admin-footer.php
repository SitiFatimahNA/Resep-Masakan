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

// Sidebar active hover effect
document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('mouseenter', function() {
        if (!this.classList.contains('active')) {
            this.querySelector('.nav-icon').style.background = 'rgba(255,255,255,0.15)';
        }
    });
    item.addEventListener('mouseleave', function() {
        if (!this.classList.contains('active')) {
            this.querySelector('.nav-icon').style.background = 'rgba(255,255,255,0.08)';
        }
    });
});
</script>
</body>
</html>
