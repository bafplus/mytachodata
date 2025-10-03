<!-- Main Footer -->
<footer class="main-footer">
  <div class="float-right d-none d-sm-block">
    <b>Version</b> 1.0
  </div>
  <strong>&copy; 2025 MyTacho</strong> All rights reserved.
</footer>
</div> <!-- /.wrapper -->

<!-- jQuery -->
<script src="/adminlte/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap -->
<script src="/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="/adminlte/dist/js/adminlte.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('theme-toggle');
    toggleBtn.addEventListener('click', function(e) {
        e.preventDefault();
        document.body.classList.toggle('dark-mode');

        // Optionally, save preference in localStorage
        if(document.body.classList.contains('dark-mode')){
            localStorage.setItem('theme', 'dark');
        } else {
            localStorage.setItem('theme', 'light');
        }
    });

    // Load saved theme preference
    if(localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark-mode');
    }
});
</script>
</body>
</html>

