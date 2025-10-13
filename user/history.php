<?php
session_start();
require_once '../config/db.php';
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user'){
    header("Location: ../index.php");
    exit();
}

$sql = "SELECT * FROM user_results WHERE user_id=".(int)$_SESSION['user_id']." ORDER BY taken_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My History - QBox</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>.container{color:#fff;margin:50px auto;width:70%;}table{width:100%;border-collapse:collapse;}th,td{padding:10px;border:1px solid #fff;text-align:center;}</style>
</head>
<body>
<div class="container">
    <h1>My Quiz History</h1>
    <table>
        <tr><th>Score</th><th>Total</th><th>Percentage</th><th>Date Taken</th></tr>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['score']); ?></td>
                <td><?php echo htmlspecialchars($row['total_questions']); ?></td>
                <td><?php echo htmlspecialchars($row['percentage']); ?>%</td>
                <td><?php echo htmlspecialchars($row['taken_at']); ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
    <a href="dashboard.php" style="color:#fff; display:block; margin-top:20px;">Back to Dashboard</a>
</div>
</body>
</html>
