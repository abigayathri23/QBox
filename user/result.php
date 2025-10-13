<?php
session_start();
require_once '../config/db.php';
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user'){
    header("Location: ../index.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// Try to fetch the latest result for this user (most recent taken_at)
$result_row = null;
$answers = [];

if($stmt = $conn->prepare('SELECT id, score, total_questions, percentage, taken_at FROM user_results WHERE user_id = ? ORDER BY taken_at DESC LIMIT 1')){
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($r = $res->fetch_assoc()){
        $result_row = $r;
    }
    $stmt->close();
}

// If we have a result id, load per-question answers for review
if($result_row){
    $rid = (int)$result_row['id'];
    if($stmt = $conn->prepare('SELECT ua.question_id, ua.selected_option, ua.is_correct, q.question, q.option_a, q.option_b, q.option_c, q.option_d, q.correct_option FROM user_answers ua JOIN questions q ON ua.question_id = q.id WHERE ua.result_id = ?')){
        $stmt->bind_param('i', $rid);
        $stmt->execute();
        $res = $stmt->get_result();
        while($r = $res->fetch_assoc()){
            $answers[] = $r;
        }
        $stmt->close();
    }
}

// Fallback to session values if DB not available
$score = $result_row ? (int)$result_row['score'] : (isset($_SESSION['score']) ? (int)$_SESSION['score'] : 0);
$total = $result_row ? (int)$result_row['total_questions'] : (isset($_SESSION['total_questions']) ? (int)$_SESSION['total_questions'] : 20);
$percentage = $result_row ? (float)$result_row['percentage'] : (isset($_SESSION['percentage']) ? (float)$_SESSION['percentage'] : null);

// clear only quiz-related session data
unset($_SESSION['score'], $_SESSION['total_questions'], $_SESSION['percentage'], $_SESSION['quiz_qids'], $_SESSION['quiz_total'], $_SESSION['quiz_token']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Quiz Result - QBox</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body{background:linear-gradient(135deg,#0f2027,#2c5364);color:#eaf4ff;font-family:Inter,Segoe UI,Arial,sans-serif;margin:0}
        .wrap{max-width:960px;margin:40px auto;padding:20px}
        .card{background:rgba(255,255,255,0.03);padding:28px;border-radius:12px;text-align:center;box-shadow:0 10px 30px rgba(2,6,23,0.6)}
        .score{font-size:48px;font-weight:800;margin:8px 0}
        .small{color:#bcd7f7}
        .actions{display:flex;gap:12px;justify-content:center;margin-top:18px}
        .btn{display:inline-block;padding:10px 18px;border-radius:10px;text-decoration:none;font-weight:700}
        .primary{background:linear-gradient(90deg,#ff8a00,#e52e71);color:#fff}
        .ghost{background:transparent;border:1px solid rgba(255,255,255,0.08);color:#fff}
        .review{margin-top:22px}
        table{width:100%;border-collapse:collapse;margin-top:12px}
        th,td{padding:10px;border-bottom:1px solid rgba(255,255,255,0.04);text-align:left}
        tr.correct td{background:linear-gradient(90deg,rgba(34,197,94,0.06),transparent)}
        tr.wrong td{background:linear-gradient(90deg,rgba(239,68,68,0.06),transparent)}
        .qtext{font-weight:700}
        @media (max-width:640px){.score{font-size:36px}.actions{flex-direction:column}}
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <h1>Quiz Completed</h1>
            <div class="score"><?php echo htmlspecialchars($score); ?> / <?php echo htmlspecialchars($total); ?></div>
            <?php if($percentage !== null): ?>
                <div class="small">Percentage: <strong><?php echo htmlspecialchars($percentage); ?>%</strong></div>
            <?php endif; ?>
            <?php if($result_row): ?>
                <div class="small">Taken at: <?php echo htmlspecialchars($result_row['taken_at']); ?></div>
            <?php endif; ?>

            <div class="actions">
                <a class="btn primary" href="dashboard.php">Back to Dashboard</a>
                <a class="btn ghost" href="history.php">My History</a>
                <?php if(!empty($answers)): ?>
                    <a class="btn ghost" href="#review">Review Answers</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if(!empty($answers)): ?>
            <div class="card review" id="review">
                <h2 style="margin-top:0">Review Your Answers</h2>
                <table>
                    <thead>
                        <tr><th>#</th><th>Question</th><th>Your Answer</th><th>Correct Answer</th><th>Result</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach($answers as $i => $a):
                        $num = $i+1;
                        $selected = $a['selected_option'];
                        $correct = $a['correct_option'];
                        $is = (int)$a['is_correct'];
                        $rowClass = $is ? 'correct' : 'wrong';
                    ?>
                        <tr class="<?php echo $rowClass; ?>">
                            <td style="width:40px"><?php echo $num; ?></td>
                            <td><div class="qtext"><?php echo htmlspecialchars($a['question']); ?></div></td>
                            <td><?php echo $selected ? htmlspecialchars($selected) . ' — ' . htmlspecialchars($a['option_' . strtolower($selected)]) : '<em>Not answered</em>'; ?></td>
                            <td><?php echo htmlspecialchars($correct) . ' — ' . htmlspecialchars($a['option_' . strtolower($correct)]); ?></td>
                            <td><?php echo $is ? '<strong style="color:#22c55e">Correct</strong>' : '<strong style="color:#ef4444">Wrong</strong>'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
