<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard - QBox</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>.container{color:#fff;text-align:center;margin-top:50px;}a.btn{display:inline-block;padding:10px 20px;background:linear-gradient(90deg,#ff8a00,#e52e71);color:#fff;text-decoration:none;margin:10px;border-radius:10px;}</style>
</head>
<body>
<div class="container">
    <h1>Admin Dashboard</h1>
    <a href="upload_quiz.php" class="btn">Upload Quiz CSV</a>
    <a href="history.php" class="btn">View Quiz History</a>
    <a href="../logout.php" class="btn">Logout</a>
</div>
</body>
</html>
