<?php
session_start();
require_once 'config/db.php';

if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = $conn->query($sql);
    if($result->num_rows > 0){
        $user = $result->fetch_assoc();
        if(password_verify($password, $user['password'])){
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            if($user['role'] == 'admin'){
                header("Location: admin/dashboard.php");
            } else {
                header("Location: user/dashboard.php");
            }
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "Email not registered!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Q_BOX Login</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
/* ---------------------------------------------- */
/*               DARK THEME BODY                  */
/* ---------------------------------------------- */
body {
    margin: 0;
    padding: 0;
    font-family: "Inter", sans-serif;
    background: radial-gradient(circle at top, #0a0f1f, #05070d, #020409);
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
    color: #fff;
}

/* ---------------------------------------------- */
/*          ANIMATED GRADIENT BACKGLOW            */
/* ---------------------------------------------- */
.bg-animation {
    position: absolute;
    width: 450px;
    height: 450px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(60,130,255,0.4), rgba(0,0,0,0));
    animation: glowFloat 6s infinite ease-in-out alternate;
    filter: blur(120px);
}

.bg-animation:nth-child(1) {
    top: -50px;
    left: -70px;
}
.bg-animation:nth-child(2) {
    bottom: -60px;
    right: -50px;
    animation-duration: 8s;
}

@keyframes glowFloat {
    0% { transform: translate(0, 0); opacity: 0.6; }
    100% { transform: translate(20px, -20px); opacity: 0.9; }
}

/* ---------------------------------------------- */
/*            LOGIN CARD (GLASS EFFECT)           */
/* ---------------------------------------------- */
.login-card {
    position: relative;
    width: 370px;
    padding: 60px;
    background: rgba(255,255,255,0.05);
    backdrop-filter: blur(14px);
    border-radius: 18px;
    box-shadow: 0px 0px 25px rgba(0, 115, 255, 0.2);
    border: 1px solid rgba(255,255,255,0.15);
    z-index: 10;
}

.login-card h2 {
    text-align: center;
    margin-bottom: 25px;
    font-weight: 800;
    font-size: 1.9rem;
    letter-spacing: 2px;
    color: #82b4ff;
    text-shadow: 0 0 15px rgba(130,180,255,0.4);
}

/* ---------------------------------------------- */
/*              INPUT FIELDS                      */
/* ---------------------------------------------- */
.login-card input {
    width: 95%;
    padding: 14px;
    margin: 10px 0px;
    margin-center: auto;
    border-radius: 14px;  /* FIXED — smoother rounded corners */
    border: none;
    outline: none;
    background: rgba(255,255,255,0.10); /* Slightly brighter */
    color: #fff;
    font-size: 1rem;
    transition: 0.3s ease;
    border: 1px solid rgba(255,255,255,0.20);  /* Clean soft border */
}

.login-card input:focus {
    border: 1.5px solid #6ba3ff;
    padding: 13.5px; /* Adjust padding to maintain size */
    margin-left: 0;
    box-shadow: 0 0 12px rgba(120,170,255,0.7);
    background: rgba(255,255,255,0.14);
}


/* ---------------------------------------------- */
/*                LOGIN BUTTON                    */
/* ---------------------------------------------- */
.login-card button {
    width: 95%;
    margin-top: 15px;
    margin-left:12px;
    padding: 14px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(135deg, #1e3a8a, #3f82ff);
    color: #fff;
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    transition: 0.3s ease;
    box-shadow: 0 0 20px rgba(63,130,255,0.4);
}

.login-card button:hover {
    transform: translateY(-4px);
    box-shadow: 0 0 35px rgba(100,160,255,0.8);
}

/* ---------------------------------------------- */
/*                ERROR MESSAGE                   */
/* ---------------------------------------------- */
.error {
    background: rgba(255,0,0,0.2);
    padding: 10px;
    border-radius: 8px;
    text-align: center;
    color: #ff9a9a;
    margin-bottom: 12px;
    border: 1px solid rgba(255,0,0,0.3);
}

/* ---------------------------------------------- */
/*              REGISTER LINK                     */
/* ---------------------------------------------- */
.login-card p {
    text-align: center;
    margin-top: 12px;
    color: #d0d9ff;
}

.login-card a {
    color: #7ab4ff;
    font-weight: 600;
    text-decoration: none;
}

.login-card a:hover {
    text-shadow: 0 0 10px #8fbfff;
}

</style>
</head>

<body>

<div class="bg-animation"></div>
<div class="bg-animation"></div>

<div class="login-card">
    <h2>Q_BOX LOGIN</h2>

    <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>

    <form method="POST">
        <input type="email" name="email" placeholder="Enter Email" required>
        <input type="password" name="password" placeholder="Enter Password" required>
        <button type="submit" name="login">Login</button>
    </form>

    <p>Don't have an account? <a href="register.php">Register</a></p>
</div>

</body>
</html>
