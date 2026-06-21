</div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.getElementById('sidebarToggle').addEventListener('click', function() {
    const sidebar = document.getElementById('appSidebar');
    sidebar.classList.toggle('active');
});

document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const appSidebar = document.getElementById('appSidebar');
    const logoText = document.getElementById('logoText');

    if (sidebarToggle && appSidebar) {
        sidebarToggle.addEventListener('click', function() {
            appSidebar.classList.toggle('collapsed');

            if (appSidebar.classList.contains('collapsed')) {
                logoText.textContent = "H.";
            } else {
                logoText.textContent = "H-Event";
            }
        });
    }
});
</script>
</body>

</html>