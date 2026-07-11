<?php
session_start();
include("db.php");

// If already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";
$success = "";

if (isset($_POST['signup'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password_raw = $_POST['password'];

    if (empty($username) || empty($email) || empty($password_raw)) {
        $error = "All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } elseif (strlen($password_raw) < 6) {
        $error = "Password must be at least 6 characters long!";
    } else {
        $password = password_hash($password_raw, PASSWORD_DEFAULT);

        // Check if email already exists
        $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($check, "s", $email);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $error = "Email already registered!";
        } else {
            // Insert new user
            $stmt = mysqli_prepare($conn, "INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sss", $username, $email, $password);

            if (mysqli_stmt_execute($stmt)) {
                $success = "Registration Successful! Redirecting to login...";
                echo "<script>
                    setTimeout(function(){
                        window.location='login.php';
                    }, 1500);
                </script>";
            } else {
                $error = "Registration failed! Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SpendWise Sign Up</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #0F0C29, #302B63, #24243E);
            overflow-y: auto;
            padding: 20px;
        }

        .signup-container {
            width: 100%;
            max-width: 500px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        form {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .logo {
            width: 650px;
            max-width: 180%;
            margin-bottom: -80px;
            filter: drop-shadow(0px 0px 20px rgba(255, 255, 255, 0.25));
        }

        .input-group {
            position: relative;
            width: 80%;
            margin-bottom: 15px;
        }

        .input-box {
            width: 100%;
            height: 60px;
            border: none;
            outline: none;
            border-radius: 18px;
            padding-left: 50px;
            padding-right: 50px;
            font-size: 18px;
            color: white;
            background: rgba(255, 255, 255, 0.12);
            text-align: center;
        }

        .input-box::placeholder {
            color: #dddddd;
            text-align: center;
            font-size: 18px;
        }

        .icon-left {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-size: 18px;
        }

        .icon-right {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-size: 18px;
            cursor: pointer;
        }

        .signup-btn {
            width: 80%;
            height: 60px;
            border: none;
            border-radius: 18px;
            font-size: 18px;
            font-weight: bold;
            color: white;
            cursor: pointer;
            background: linear-gradient(to right, #2f67ff, #1d49d8);
            transition: .3s;
            margin-top: 10px;
        }

        .signup-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 0 20px rgba(47, 103, 255, .5);
        }

        .login-text {
            margin-top: 20px;
            color: white;
            font-size: 16px;
        }

        .login-text a {
            color: #4da6ff;
            text-decoration: none;
            font-weight: bold;
        }

        .login-text a:hover {
            text-decoration: underline;
        }

        .error {
            color: #ff5d5d;
            margin-top: 20px;
            font-size: 17px;
            font-weight: bold;
        }

        .success {
            color: #7CFF9E;
            margin-top: 20px;
            font-size: 17px;
            font-weight: bold;
        }

        @media(max-width:600px){
            .logo {
                width: 320px;
            }
        }
    </style>
</head>
<body>

<div class="signup-container">
    <img src="logo.png" class="logo" alt="SpendWise Logo">

    <form method="POST">
        <div class="input-group">
            <i class="fa-solid fa-user icon-left"></i>
            <input
                type="text"
                name="username"
                placeholder="Username"
                class="input-box"
                value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                required>
        </div>

        <div class="input-group">
            <i class="fa-solid fa-envelope icon-left"></i>
            <input
                type="email"
                name="email"
                placeholder="Email Address"
                class="input-box"
                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                required>
        </div>

        <div class="input-group">
            <i class="fa-solid fa-lock icon-left"></i>
            <input
                type="password"
                name="password"
                placeholder="Password"
                class="input-box"
                id="password"
                required>
            <i class="fa-solid fa-eye icon-right" id="togglePassword"></i>
        </div>

        <button type="submit" name="signup" class="signup-btn">
            SIGN UP
        </button>

        <p class="login-text">
            Already have an account?
            <a href="login.php">Log In</a>
        </p>
    </form>

    <?php
    if ($error != "") {
        echo "<div class='error'>$error</div>";
    }
    if ($success != "") {
        echo "<div class='success'>$success</div>";
    }
    ?>
</div>

<script>
    const togglePassword = document.getElementById("togglePassword");
    const password = document.getElementById("password");

    togglePassword.onclick = function() {
        if (password.type === "password") {
            password.type = "text";
            togglePassword.classList.replace("fa-eye", "fa-eye-slash");
        } else {
            password.type = "password";
            togglePassword.classList.replace("fa-eye-slash", "fa-eye");
        }
    }
</script>

</body>
</html>