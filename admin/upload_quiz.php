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
        while(($data = fgetcsv($handle, 1000, ",")) !== FALSE){
            $question = $conn->real_escape_string($data[0]);
            $a = $conn->real_escape_string($data[1]);
            $b = $conn->real_escape_string($data[2]);
            $c = $conn->real_escape_string($data[3]);
            $d = $conn->real_escape_string($data[4]);
            $answer = strtoupper(trim($data[5]));
            $conn->query("INSERT INTO questions (question_text, option_a, option_b, option_c, option_d, correct_answer) VALUES ('$question','$a','$b','$c','$d','$answer')");
        }
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
