<?php
require_once 'config/db.php';

if(isset($_POST['register'])){
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $sql = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$password')";
    if($conn->query($sql)){
        header("Location: index.php");
        exit();
    } else {
        $error = "Error: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Q_BOX Register</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>

/* ---------------------------------------------- */
/*                GLOBAL DARK BODY                */
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

* {
    box-sizing: border-box;
}

/* ---------------------------------------------- */
/*          ANIMATED BG LIGHT GLOWS               */
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
/*            REGISTER CARD (GLASS UI)            */
/* ---------------------------------------------- */
.register-card {
    position: relative;
    width: 500px;
    padding: 60px;
    background: rgba(255,255,255,0.05);
    backdrop-filter: blur(14px);
    border-radius: 18px;
    box-shadow: 0px 0px 25px rgba(0, 115, 255, 0.2);
    border: 1px solid rgba(255,255,255,0.15);
    z-index: 10;
}

.register-card h2 {
    text-align: center;
    margin-bottom: 25px;
    font-weight: 800;
    font-size: 1.9rem;
    letter-spacing: 2px;
    color: #82b4ff;
    text-shadow: 0 0 15px rgba(130,180,255,0.4);
}

/* ---------------------------------------------- */
/*              CLEAN INPUT FIELDS                */
/* ---------------------------------------------- */
.register-card input {
    width: 100%;
    padding: 14px;
    margin: 12px auto;
    display: block;
    border-radius: 14px;
    border: 1px solid rgba(255,255,255,0.20);
    background: rgba(255,255,255,0.10);
    color: #fff;
    font-size: 1rem;
    outline: none;
    transition: 0.3s ease;
}

.register-card input:focus {
    border: 1.5px solid #6ba3ff;
    box-shadow: 0 0 12px rgba(120,170,255,0.7);
    background: rgba(255,255,255,0.14);
}

/* ---------------------------------------------- */
/*                REGISTER BUTTON                 */
/* ---------------------------------------------- */
.register-card button {
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

.register-card button:hover {
    transform: translateY(-4px);
    box-shadow: 0 0 35px rgba(100,160,255,0.8);
}

/* ---------------------------------------------- */
/*               ERROR + LINKS                    */
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

.register-card p {
    text-align: center;
    margin-top: 12px;
    color: #d0d9ff;
}

.register-card a {
    color: #7ab4ff;
    font-weight: 600;
    text-decoration: none;
}

.register-card a:hover {
    text-shadow: 0 0 10px #8fbfff;
}

</style>
</head>

<body>

<div class="bg-animation"></div>
<div class="bg-animation"></div>

<div class="register-card">
    <h2>Q_BOX REGISTER</h2>

    <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>

    <form method="POST">
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="register">Register</button>
    </form>

    <p>Already have an account? <a href="index.php">Login</a></p>
</div>

</body>
</html>
