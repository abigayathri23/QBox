<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user'){
    header("Location: ../index.php");
    exit();
}
$score = $_SESSION['score'];
unset($_SESSION['score']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Quiz Result - QBox</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>.container{color:#fff;text-align:center;margin-top:50px;}a.btn{display:inline-block;padding:10px 20px;background:linear-gradient(90deg,#ff8a00,#e52e71);color:#fff;text-decoration:none;margin-top:20px;border-radius:10px;}</style>
</head>
<body>
<div class="container">
    <h1>Quiz Completed!</h1>
    <p>Your Score: <b><?php echo $score; ?></b> / 20</p>
    <a href="dashboard.php" class="btn">Back to Dashboard</a>
</div>
</body>
</html>
