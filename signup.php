<?php
require_once "db.php";
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['email'], $_POST['password'], $_POST['confirmPassword'])) {
    $un = $_POST['username'];
    $email = trim($_POST['email']);
    $pass = $_POST['password'];
    $confirmPass = $_POST['confirmPassword'];

    if ($pass === $confirmPass) {
        $sql = "SELECT * FROM accounts WHERE email=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION["Error"] = "Email already taken.";
        } else {
            $insert = "INSERT INTO accounts (username, email, password) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insert);
            $hashedPass = password_hash($pass, PASSWORD_BCRYPT);
            $stmt->bind_param("sss", $un, $email, $hashedPass);

            if ($stmt->execute()) {
                header("Location: home.php");
                $_SESSION['LoggedIn'] = true;
                $_SESSION['Username'] = $un;
                $_SESSION['Email'] = $email;
                $_SESSION['UserId'] = $stmt->insert_id;
                exit;
            } else {
                $_SESSION["Error"] = "Failed to create account. Please try again.";
            }
        }
    } else {
        $_SESSION["Error"] = "Passwords don't match.";
    }
    header("Location: signup.php");
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
  <link
    href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap"
    rel="stylesheet">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Rammetto+One&display=swap" rel="stylesheet">
  <title>Signup</title>
</head>

<body>

  <main class="wrapper">
    <div class="login-image">
    </div>

    <div class="login">
      <div class="login-wrapper">
        <form action="signup.php" method="post" class="login-form">
          <div class="greetings">
            <h2>Welcome!</h2>
            <p>Please enter your account details</p>
          </div>
          <div class="input-box">
            <label for="#username">Username</label>
            <input required name="username" id="#username" type="text" placeholder="ex.John Doe">
          </div>
          <div class="input-box">
            <label for="#email">Email</label>
            <input required name="email" id="#email" type="email" placeholder="ex.abc123@gmail.com">
          </div>
          <div class="input-box">
            <label for="#password">Password</label>
            <input required name="password" id="#password" type="password" placeholder="password">
          </div>
          <div class="input-box">
            <label for="#cpassword">Confirm Password</label>
            <input required name="confirmPassword" id="#cpassword" type="password" placeholder="password">
          </div>
          
          <?php
            if (isset($_SESSION['Error'])) {
              echo '<p class="error">'.htmlspecialchars($_SESSION['Error'])."</p>";
              unset($_SESSION['Error']);
            }
          ?>
  
          <button type="submit" class="login-button">Create Account</button>
          <button onclick="window.location.href='login.php'" class="signup">Already have an account? Log in</button>
        </form>
      </div>
    </div>
  </main>

</body>

</html>