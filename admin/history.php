<?php
session_start();
require_once '../config/db.php';
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../index.php");
    exit();
}
$sql = "SELECT r.id, u.name, r.score, r.total_questions, r.percentage, r.taken_at FROM user_results r JOIN users u ON r.user_id=u.id ORDER BY r.taken_at DESC";
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
    <h1>All Users Quiz History</h1>
    <table>
        <tr><th>User</th><th>Score</th><th>Total</th><th>%</th><th>Taken At</th></tr>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['score']); ?></td>
                <td><?php echo htmlspecialchars($row['total_questions']); ?></td>
                <td><?php echo htmlspecialchars($row['percentage']); ?>%</td>
                <td><?php echo htmlspecialchars($row['taken_at']); ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
