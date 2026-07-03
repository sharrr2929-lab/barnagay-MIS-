Optional folder for a real barangay seal/logo image, if you have one.

The UI currently draws a "BM" seal badge with CSS (see .sidebar-seal and
.login-seal in assets/css/style.css) so no image file is required to run
the app. To use a real logo instead:

1. Drop your logo file here, e.g. logo.png
2. Reference it with <img src="<?= BASE_URL ?>/assets/img/logo.png"> in
   includes/sidebar.php and login.php (replacing the .sidebar-seal /
   .login-seal <div> elements)
