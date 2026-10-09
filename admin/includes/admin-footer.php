    </div><!-- end admin-content -->
</main><!-- end admin-main -->
</div><!-- end admin-wrapper -->

<script>
// Auto hide alerts
document.querySelectorAll('.alert').forEach(el => {
    setTimeout(() => {
        el.style.opacity = '0';
        el.style.transition = 'opacity 0.4s';
        setTimeout(() => el.remove(), 400);
    }, 4000);
});
</script>
</body>
</html>
