<?php
session_start();
require_once '../config/db.php';
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user'){
    header("Location: ../index.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// Time limit in seconds (e.g., 20 minutes = 1200)
$time_limit = 20 * 60; // change as needed

// If form submitted, process answers
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])){
    // Basic CSRF/quiz token check
    if(empty($_POST['quiz_token']) || !isset($_SESSION['quiz_token']) || $_POST['quiz_token'] !== $_SESSION['quiz_token']){
        die('Invalid quiz session. Please start the quiz again.');
    }
    // Invalidate token to prevent re-use
    unset($_SESSION['quiz_token']);

    $answers = isset($_POST['answers']) && is_array($_POST['answers']) ? $_POST['answers'] : [];

    // Get expected question ids from session (set when quiz was generated)
    $expected_qids = isset($_SESSION['quiz_qids']) ? $_SESSION['quiz_qids'] : array_map('intval', array_keys($answers));
    $total_questions = isset($_SESSION['quiz_total']) ? (int)$_SESSION['quiz_total'] : count($expected_qids);

    // Calculate score and prepare per-question correctness
    $score = 0;
    $per_question = []; // qid => ['selected' => 'A', 'is_correct' => 0]

    $stmtCheck = $conn->prepare('SELECT correct_option FROM questions WHERE id = ?');
    foreach($expected_qids as $qid){
        $qid = (int)$qid;
        $selected = isset($answers[$qid]) ? substr($answers[$qid],0,1) : null;
        $is_correct = 0;
        if($selected && in_array($selected, ['A','B','C','D'])){
            $stmtCheck->bind_param('i', $qid);
            $stmtCheck->execute();
            $res = $stmtCheck->get_result();
            if($row = $res->fetch_assoc()){
                if($row['correct_option'] === $selected){
                    $is_correct = 1;
                    $score++;
                }
            }
        }
        $per_question[$qid] = ['selected' => $selected, 'is_correct' => $is_correct];
    }
    if($stmtCheck) $stmtCheck->close();

    // Save result and answers within a transaction
    $conn->begin_transaction();
    try{
        $quiz_id = isset($_SESSION['quiz_id']) ? (int)$_SESSION['quiz_id'] : 1; // default quiz id
        $percentage = $total_questions > 0 ? round(($score / $total_questions) * 100, 2) : 0.00;

        // Insert into user_results
        $stmtRes = $conn->prepare('INSERT INTO user_results(user_id, quiz_id, score, total_questions, percentage) VALUES(?,?,?,?,?)');
        if(!$stmtRes) throw new Exception('Failed to prepare result insert');
        $stmtRes->bind_param('iiiid', $user_id, $quiz_id, $score, $total_questions, $percentage);
        $stmtRes->execute();
        $result_id = $conn->insert_id;
        $stmtRes->close();

        // Insert per-question answers into user_answers
        $stmtAns = $conn->prepare('INSERT INTO user_answers(result_id, question_id, selected_option, is_correct) VALUES(?,?,?,?)');
        if(!$stmtAns) throw new Exception('Failed to prepare answers insert');
        foreach($per_question as $qid => $info){
            $sel = $info['selected'];
            $is = $info['is_correct'];
            // selected_option expects enum A-D or NULL; bind as string or null
            $sparam = $sel !== null ? $sel : null;
            $stmtAns->bind_param('iisi', $result_id, $qid, $sparam, $is);
            $stmtAns->execute();
        }
        $stmtAns->close();

        $conn->commit();
        // store some summary in session for result page
        $_SESSION['score'] = $score;
        $_SESSION['total_questions'] = $total_questions;
        $_SESSION['percentage'] = $percentage;
        header('Location: result.php');
        exit();
    } catch(Exception $e){
        $conn->rollback();
        error_log('Quiz save error: ' . $e->getMessage());
        die('An error occurred while saving your answers. Please try again later.');
    }
}

// Otherwise show quiz: fetch 20 random questions
$questions = [];
$res = $conn->query('SELECT id, question, option_a, option_b, option_c, option_d FROM questions ORDER BY RAND() LIMIT 20');
if($res){
    while($row = $res->fetch_assoc()){
        $questions[] = $row;
    }
}

// Generate and store a one-time token to prevent double-submits / CSRF
$_SESSION['quiz_token'] = bin2hex(random_bytes(16));
// Store the question ids & total in session so we can record total_questions and validate on submit
$_SESSION['quiz_qids'] = array_map(function($q){ return (int)$q['id']; }, $questions);
$_SESSION['quiz_total'] = count($_SESSION['quiz_qids']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Quiz - QBox</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
    .container { color:#fff; margin: 30px auto; max-width:900px; padding:16px }
    .topbar{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px}
    .timer{background:rgba(255,255,255,0.06);padding:10px 14px;border-radius:10px;font-weight:700}
    .question { background: rgba(255,255,255,0.03); padding:15px; margin:12px 0; border-radius:10px; }
    .qtext{font-weight:600;margin-bottom:8px}
    .options label{display:block;margin:6px 0;padding:8px;border-radius:8px;cursor:pointer}
    .options input{margin-right:8px}
    .submit-row{display:flex;justify-content:flex-end;margin-top:18px}
    button.primary{ background: linear-gradient(90deg,#ff8a00,#e52e71); color:#fff; padding:10px 20px; border:none; border-radius:10px; cursor:pointer; font-weight:700 }
    @media (max-width:640px){.topbar{flex-direction:column;align-items:flex-start}.submit-row{justify-content:center}}
</style>
</head>
<body>
<div class="container">
    <div class="topbar">
        <h1>Quiz Time!</h1>
        <div class="timer" id="timer">Time left: <span id="time">--:--</span></div>
    </div>

    <?php if(empty($questions)): ?>
        <div class="question">No questions available. Please contact the administrator.</div>
    <?php else: ?>
    <form method="POST" id="quizForm">
        <input type="hidden" name="quiz_token" value="<?php echo htmlspecialchars($_SESSION['quiz_token']); ?>">
        <input type="hidden" id="time_remaining" name="time_remaining" value="<?php echo $time_limit; ?>">

        <?php foreach($questions as $index => $row):
            $qid = (int)$row['id'];
        ?>
            <div class="question">
                <div class="qtext"><?php echo ($index+1) . '. ' . htmlspecialchars($row['question']); ?></div>
                <div class="options">
                    <label><input type="radio" name="answers[<?php echo $qid; ?>]" value="A" <?php echo 'required'; ?>> <?php echo htmlspecialchars($row['option_a']); ?></label>
                    <label><input type="radio" name="answers[<?php echo $qid; ?>]" value="B"> <?php echo htmlspecialchars($row['option_b']); ?></label>
                    <label><input type="radio" name="answers[<?php echo $qid; ?>]" value="C"> <?php echo htmlspecialchars($row['option_c']); ?></label>
                    <label><input type="radio" name="answers[<?php echo $qid; ?>]" value="D"> <?php echo htmlspecialchars($row['option_d']); ?></label>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="submit-row">
            <button type="submit" name="submit" class="primary" id="submitBtn">Submit Quiz</button>
        </div>
    </form>
    <?php endif; ?>
</div>

<script>
// Simple countdown timer
let timeLeft = <?php echo (int)$time_limit; ?>; // seconds
const timerEl = document.getElementById('time');
const timeInput = document.getElementById('time_remaining');
const form = document.getElementById('quizForm');
const submitBtn = document.getElementById('submitBtn');

function formatTime(s){
    const m = Math.floor(s/60).toString().padStart(2,'0');
    const sec = (s%60).toString().padStart(2,'0');
    return m + ':' + sec;
}

function tick(){
    timerEl.textContent = formatTime(timeLeft);
    timeInput.value = timeLeft;
    if(timeLeft <= 0){
        // Auto-submit
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';
        form.submit();
        return;
    }
    timeLeft--;
}

// initialize
timerEl.textContent = formatTime(timeLeft);
const interval = setInterval(()=>{
    tick();
},1000);

// On manual submit, disable button to prevent double submits
form.addEventListener('submit', function(e){
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';
});
</script>
</body>
</html>
