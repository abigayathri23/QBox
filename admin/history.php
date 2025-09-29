<?php
session_start();
require_once '../config/db.php';
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../index.php");
    exit();
}
$sql = "SELECT r.id, u.name, r.score, r.date_taken FROM results r JOIN users u ON r.user_id=u.id ORDER BY r.date_taken DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Quiz History - Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>.container{color:#fff;margin:50px auto;width:80%;}table{width:100%;border-collapse:collapse;}th,td{padding:10px;border:1px solid #fff;text-align:center;}</style>
</head>
<body>
<div class="container">
    <h1>All Users Quiz History</h1
