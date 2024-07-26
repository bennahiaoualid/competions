document.addEventListener('DOMContentLoaded', function () {
    const sideBarToggleBtn =  document.getElementById('toggleSidebarBtn');
    const sidebar = document.getElementById('sidebar');
    sideBarToggleBtn.addEventListener('click', function() {
        sidebar.classList.remove('-translate-x-full');
    });

    // Close sidebar when clicking outside
    document.addEventListener('click', function(e) {
        const sidebar = document.getElementById('sidebar');
        if (!sidebar.contains(e.target) && !sideBarToggleBtn.contains(e.target)) {
            sidebar.classList.add('-translate-x-full');
        }

    });
});
