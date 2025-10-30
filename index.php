


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="logo/pawpetcarelogo.png">
  <title>Paw Pet Care</title>
  <style>
    html, body {
      margin: 0;
      padding: 0;
      height: 100%;
      overflow: hidden; /* prevent scrolling */
    }
    iframe {
      width: 100%;
      height: 100%;
      border: none;
      display: block;
    }
  </style>
</head>
<body>
  <iframe src="main.php"></iframe>

  <?php
session_start();

// If already logged in, go straight to dashboard
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    echo '<script>window.top.location.href = "dashboard.php";</script>';
    exit;
}

// Otherwise, stay on login page
?>

</body>
</html>
