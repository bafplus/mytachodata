<!-- Main Footer -->
<footer class="main-footer">
  <div class="float-right d-none d-sm-block">
    <b>Version</b> 1.0
  </div>
  <strong>&copy; 2025 MyTacho</strong> All rights reserved.
</footer>
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="/adminlte/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap -->
<script src="/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="/adminlte/dist/js/adminlte.min.js"></script>

<!-- Theme toggle script -->
<script>
$(function() {
    // Toggle light/dark mode
    $('#theme-toggle').on('click', function(e) {
        e.preventDefault();
        $('body').toggleClass('dark-mode');

        // Save preference
        if($('body').hasClass('dark-mode')){
            localStorage.setItem('theme', 'dark');
        } else {
            localStorage.setItem('theme', 'light');
        }
    });

    // Load saved theme on page load
    if(localStorage.getItem('theme') === 'dark') {
        $('body').addClass('dark-mode');
    }
});
</script>
</body>
</html>
