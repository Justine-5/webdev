<?php
session_start();
require_once "db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email']);
  $password = $_POST['password'];

  if (empty($email) || empty($password)) {
      $_SESSION['Error'] = "All fields are required.";
      header("Location: login.php");
      exit;
  }

  $stmt = $conn->prepare("SELECT * FROM accounts WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 1) {
      $user = $result->fetch_assoc();

      if (password_verify($password, $user['password'])) {
          $_SESSION['LoggedIn'] = true;
          $_SESSION['Username'] = $user["username"];
          $_SESSION['Email'] = $user['email'];
          $_SESSION['UserId'] = $user['id'];

          header("Location: home.php");
          exit;
      } else {
          $_SESSION['Error'] = "Invalid email or password.";
      }
  } else {
      $_SESSION['Error'] = "Invalid email or password.";
  }

  $stmt->close();
  header("Location: index.php");
  exit;
}
  
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="styles/index.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Rammetto+One&display=swap" rel="stylesheet">
  <title>Login</title>
</head>

<body>

  <main class="wrapper">
    <div class="login-image"></div>

    <div class="login">
      <div class="login-wrapper">
        <form action="login.php" method="post" class="login-form">

          <div class="greetings">
            <h2>Welcome Back!</h2>
            <p>Please enter your login details</p>
          </div>

          <div class="input-box">
            <label for="email">Email</label>
            <input name="email" required id="email" type="email" placeholder="ex.abc123@gmail.com">
          </div>
  
          <div class="input-box">
            <label for="password">Password</label>
            <input name="password" required id="password" type="password" placeholder="password">
          </div>

          <?php
            if (isset($_SESSION['Error'])) {
              echo '<p class="error">'.$_SESSION['Error'].'</p>';
              unset($_SESSION['Error']);
            }
          ?>
  
          <button type="submit" class="login-button">Login</button>
          <button onclick="window.location.href='signup.php'" class="signup">Don't have an account? Sign up</button>
        </form>
      </div>
    </div>
  </main>

</body>

</html>