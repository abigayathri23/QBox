<?php
session_start();
require_once '../config/db.php';
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../index.php");
    exit();
}

if(isset($_POST['upload'])){
    $file = $_FILES['csv']['tmp_name'];
    if($file){
        $handle = fopen($file, 'r');
        // prepare insert statement matching DB schema
        $stmt = $conn->prepare('INSERT INTO questions (quiz_id, question, option_a, option_b, option_c, option_d, correct_option) VALUES(?,?,?,?,?,?,?)');
        $quiz_id = 1; // default quiz id; adjust if CSV contains quiz id column
        while(($data = fgetcsv($handle, 1000, ",")) !== FALSE){
            $question = trim($data[0]);
            $a = trim($data[1]);
            $b = trim($data[2]);
            $c = isset($data[3]) ? trim($data[3]) : null;
            $d = isset($data[4]) ? trim($data[4]) : null;
            $answer = strtoupper(trim($data[5]));
            if(!in_array($answer, ['A','B','C','D'])) $answer = 'A';
            $stmt->bind_param('issssss', $quiz_id, $question, $a, $b, $c, $d, $answer);
            $stmt->execute();
        }
        if($stmt) $stmt->close();
        fclose($handle);
        $msg = "CSV Uploaded Successfully!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Upload Quiz - Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>.container{color:#fff;text-align:center;margin-top:50px;}input[type=file]{margin:20px;}button{background:linear-gradient(90deg,#ff8a00,#e52e71);color:#fff;padding:10px 20px;border:none;border-radius:10px;cursor:pointer;}</style>
</head>
<body>
<div class="container">
    <h1>Upload Quiz CSV</h1>
    <?php if(isset($msg)) echo "<p>$msg</p>"; ?>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="csv" accept=".csv" required>
        <br>
        <button type="submit" name="upload">Upload</button>
    </form>
    <a href="dashboard.php" style="color:#fff; display:block; margin-top:20px;">Back to Dashboard</a>
</div>
</body>
</html>
