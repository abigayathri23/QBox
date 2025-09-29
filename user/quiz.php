<?php
session_start();
require_once '../config/db.php';
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user'){
    header("Location: ../index.php");
    exit();
}

// Fetch 20 random questions
$sql = "SELECT * FROM questions ORDER BY RAND() LIMIT 20";
$result = $conn->query($sql);

if(isset($_POST['submit'])){
    $score = 0;
    foreach($_POST['answers'] as $qid => $answer){
        $sqlCheck = "SELECT correct_answer FROM questions WHERE id=$qid";
        $resCheck = $conn->query($sqlCheck)->fetch_assoc();
        if($resCheck['correct_answer'] == $answer) $score++;

        $sqlInsert = "INSERT INTO answers(user_id, question_id, selected_answer) VALUES(".$_SESSION['user_id'].", $qid, '$answer')";
        $conn->query($sqlInsert);
    }
    // Save result
    $conn->query("INSERT INTO results(user_id, score) VALUES(".$_SESSION['user_id'].", $score)");
    $_SESSION['score'] = $score;
    header("Location: result.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Quiz - QBox</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.container { color:#fff; margin: 50px auto; width: 70%; }
button { background: linear-gradient(90deg,#ff8a00,#e52e71); color:#fff; padding:10px 20px; border:none; border-radius:10px; cursor:pointer; }
.question { background: rgba(255,255,255,0.1); padding:15px; margin:15px 0; border-radius:10px; }
</style>
</head>
<body>
<div class="container">
    <h1>Quiz Time!</h1>
    <form method="POST">
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="question">
                <p><b><?php echo $row['question_text']; ?></b></p>
                <input type="radio" name="answers[<?php echo $row['id']; ?>]" value="A" required> <?php echo $row['option_a']; ?><br>
                <input type="radio" name="answers[<?php echo $row['id']; ?>]" value="B"> <?php echo $row['option_b']; ?><br>
                <input type="radio" name="answers[<?php echo $row['id']; ?>]" value="C"> <?php echo $row['option_c']; ?><br>
                <input type="radio" name="answers[<?php echo $row['id']; ?>]" value="D"> <?php echo $row['option_d']; ?><br>
            </div>
        <?php endwhile; ?>
        <button type="submit" name="submit">Submit Quiz</button>
    </form>
</div>
</body>
</html>
