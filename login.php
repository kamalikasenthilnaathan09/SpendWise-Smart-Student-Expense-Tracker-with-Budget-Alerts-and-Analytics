<?php
session_start();
include("db.php");

// If already logged in
if(isset($_SESSION['user_id'])){
    header("Location: dashboard.php");
    exit();
}

$error = "";
$success = "";

if(isset($_POST['login'])){

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Secure prepared statement
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email=?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if(mysqli_num_rows($result) == 1){

        $row = mysqli_fetch_assoc($result);

        if(password_verify($password, $row['password'])){

            session_regenerate_id(true);

            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['theme'] = $row['theme'] ?? 'light';
            $_SESSION['currency'] = $row['currency'] ?? 'INR';
            $_SESSION['monthly_budget'] = $row['monthly_budget'] ?? 50000.00;
            $_SESSION['savings_goal_name'] = $row['savings_goal_name'] ?? 'New Laptop';
            $_SESSION['savings_goal_target'] = $row['savings_goal_target'] ?? 50000.00;
            $_SESSION['savings_goal_current'] = $row['savings_goal_current'] ?? 33433.00;
            $_SESSION['notifications_enabled'] = $row['notifications_enabled'] ?? 1;

            $success = "Login Successful! Redirecting...";

            echo "<script>
                setTimeout(function(){
                    window.location='dashboard.php';
                },1000);
            </script>";

        }else{
            $error = "Invalid Password";
        }

    }else{
        $error = "User not found";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>SpendWise Login</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family: Arial, sans-serif;
}

body{
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background: linear-gradient(135deg,#0F0C29,#302B63,#24243E);
    overflow-y:auto;
    padding:20px;
}

.login-container{
    width:100%;
    max-width:500px;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    text-align:center;
}

form{
    width:100%;
    display:flex;
    flex-direction:column;
    align-items:center;
}

.logo{
    width:650px;
    max-width:180%;
    margin-bottom:-80px;
    filter: drop-shadow(0px 0px 20px rgba(255,255,255,0.25));
}

.input-group{
    position:relative;
    width:80%;
    margin-bottom:15px;
}

.input-box{
    width:100%;
    height:60px;
    border:none;
    outline:none;
    border-radius:18px;
    padding-left:50px;
    padding-right:50px;
    font-size:18px;
    color:white;
    background: rgba(255,255,255,0.12);
    text-align:center;
}

.input-box::placeholder{
    color:#dddddd;
    text-align:center;
    font-size:18px;
}

.icon-left{
    position:absolute;
    left:18px;
    top:50%;
    transform:translateY(-50%);
    color:white;
    font-size:18px;
}

.icon-right{
    position:absolute;
    right:18px;
    top:50%;
    transform:translateY(-50%);
    color:white;
    font-size:18px;
    cursor:pointer;
}

.login-btn{
    width:80%;
    height:60px;
    border:none;
    border-radius:18px;
    font-size:18px;
    font-weight:bold;
    color:white;
    cursor:pointer;
    background: linear-gradient(to right,#2f67ff,#1d49d8);
    transition:.3s;
}

.login-btn:hover{
    transform:scale(1.02);
    box-shadow:0 0 20px rgba(47,103,255,.5);
}

.signup-text{
    margin-top:20px;
    color:white;
    font-size:16px;
}

.signup-text a{
    color:#4da6ff;
    text-decoration:none;
    font-weight:bold;
}

.signup-text a:hover{
    text-decoration:underline;
}

.error{
    color:#ff5d5d;
    margin-top:20px;
    font-size:17px;
    font-weight:bold;
}

.success{
    color:#7CFF9E;
    margin-top:20px;
    font-size:17px;
    font-weight:bold;
}

@media(max-width:600px){
.logo{
width:320px;
}
}

</style>

</head>

<body>

<div class="login-container">

<img src="logo.png" class="logo">

<form method="POST">

<div class="input-group">
<i class="fa-solid fa-envelope icon-left"></i>

<input
type="email"
name="email"
placeholder="Enter Email"
class="input-box"
required>

</div>

<div class="input-group">

<i class="fa-solid fa-lock icon-left"></i>

<input
type="password"
name="password"
placeholder="Enter Password"
class="input-box"
id="password"
required>

<i class="fa-solid fa-eye icon-right" id="togglePassword"></i>

</div>

<button type="submit" name="login" class="login-btn">
LOGIN
</button>

<p class="signup-text">
Don't have an account?
<a href="signup.php">Sign Up</a>
</p>

</form>

<?php
if($error!=""){
    echo "<div class='error'>$error</div>";
}

if($success!=""){
    echo "<div class='success'>$success</div>";
}
?>

</div>

<script>

const togglePassword=document.getElementById("togglePassword");
const password=document.getElementById("password");

togglePassword.onclick=function(){

if(password.type==="password"){
password.type="text";
togglePassword.classList.replace("fa-eye","fa-eye-slash");
}
else{
password.type="password";
togglePassword.classList.replace("fa-eye-slash","fa-eye");
}

}

</script>

</body>
</html>