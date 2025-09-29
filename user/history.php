<?php
session_start();
require_once '../config/db.php';
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user'){
    header("Location: ../index.php");
    exit();
}

$sql = "SELECT * FROM results WHERE user_id=".$_SESSION['user_id']." ORDER BY date_taken DESC";
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
        <tr><th>Score</th><th>Date Taken</th></tr>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['score']; ?></td>
                <td><?php echo $row['date_taken']; ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
    <a href="dashboard.php" style="color:#fff; display:block; margin-top:20px;">Back to Dashboard</a>
</div>
</body>
</html>
