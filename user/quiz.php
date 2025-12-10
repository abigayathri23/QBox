<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: ../index.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// Time limit in seconds (e.g., 20 minutes = 1200)
$time_limit = 20 * 60; // change as needed
$season_id = 1; // Hardcoded season ID for simplicity

// If form submitted, process answers
// Accept any POST (don't require the submit button field). Rationale: when
// JS programmatically submits the form or disables the submit button during
// submission, the button value may not be present in POST data. Use the
// request method + token check instead.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic CSRF/quiz token check
    if (empty($_POST['quiz_token']) || !isset($_SESSION['quiz_token']) || $_POST['quiz_token'] !== $_SESSION['quiz_token']) {
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
    if(!$stmtCheck) throw new Exception('Failed to prepare question check statement');
    foreach ($expected_qids as $qid) {
        $qid = (int)$qid;
        $selected = isset($answers[$qid]) ? substr($answers[$qid], 0, 1) : null;
        $is_correct = 0;
        if ($selected && in_array($selected, ['A', 'B', 'C', 'D'])) {
            $stmtCheck->bind_param('i', $qid);
            $stmtCheck->execute();
            $res = $stmtCheck->get_result();
            if ($row = $res->fetch_assoc()) {
                if ($row['correct_option'] === $selected) {
                    $is_correct = 1;
                    $score++;
                }
            }
        }
        $per_question[$qid] = ['selected' => $selected, 'is_correct' => $is_correct];
    }
    if ($stmtCheck) $stmtCheck->close();

    // Save result and answers within a transaction
    $conn->begin_transaction();
    try {
        $quiz_id = isset($_SESSION['quiz_id']) ? (int)$_SESSION['quiz_id'] : 1; // default quiz id
        $percentage = $total_questions > 0 ? round(($score / $total_questions) * 100, 2) : 0.00;

        // Insert into user_results
        $stmtRes = $conn->prepare('INSERT INTO user_results(user_id, quiz_id, score, total_questions, percentage) VALUES(?,?,?,?,?)');
        if (!$stmtRes) throw new Exception('Failed to prepare result insert');
        $stmtRes->bind_param('iiiid', $user_id, $quiz_id, $score, $total_questions, $percentage);
        $stmtRes->execute();
        $result_id = $conn->insert_id;
        $stmtRes->close();

        // Insert per-question answers into user_answers (use NULL for empty selection)
        $stmtAns = $conn->prepare("INSERT INTO user_answers(result_id, question_id, selected_option, is_correct) VALUES(?,?,NULLIF(?,''),?)");
        if (!$stmtAns) throw new Exception('Failed to prepare answers insert');
        foreach ($per_question as $qid => $info) {
            $sel = $info['selected'];
            $is = $info['is_correct'];
            // convert null to empty string so NULLIF(?, '') becomes NULL in DB
            $sparam = $sel !== null ? $sel : '';
            $stmtAns->bind_param('iisi', $result_id, $qid, $sparam, $is);
            $stmtAns->execute();
        }
        $stmtAns->close();

        // Handle "Submit to Season" if requested
        if (isset($_POST['submit_to_season']) && $_POST['submit_to_season'] === '1') {
            $stmtSeason = $conn->prepare('INSERT INTO season_leaderboard(user_id, season_id, quiz_id, result_id, score, total_questions, percentage, submitted_at) VALUES(?,?,?,?,?,?,?,NOW())');
            if (!$stmtSeason) throw new Exception('Failed to prepare season leaderboard insert');
            // types: user_id (i), season_id (i), quiz_id (i), result_id (i), score (i), total_questions (i), percentage (d)
            $stmtSeason->bind_param('iiiiiid', $user_id, $season_id, $quiz_id, $result_id, $score, $total_questions, $percentage);
            $stmtSeason->execute();
            $stmtSeason->close();
        }

        $conn->commit();
        // Store some summary in session for dashboard or quick review
        $_SESSION['score'] = $score;
        $_SESSION['total_questions'] = $total_questions;
        $_SESSION['percentage'] = $percentage;
        $_SESSION['result_id'] = $result_id;
        // Redirect to user dashboard which will read the latest attempt from DB
        header('Location: ./dashboard.php');
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        error_log('Quiz save error: ' . $e->getMessage());
        die('An error occurred while saving your answers. Please try again later.');
    }
}

// Otherwise show quiz: fetch 10 random questions
$questions = [];
$res = $conn->query('SELECT id, question, option_a, option_b, option_c, option_d FROM questions ORDER BY RAND() LIMIT 10');
if ($res) {
    while ($row = $res->fetch_assoc()) {
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
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Quiz - QBox</title>

<style>
    /* ---------- DARK THEME ---------- */
    :root {
        --bg: #0b1220;
        --card: #121c31;
        --border: #1d2942;
        --text: #ffffff;
        --muted: #a8b3c7;
        --primary: #4f8cff;
        --hover: #2a4c99;
    }

    body {
        margin: 0;
        background: var(--bg);
        color: var(--text);
        font-family: "Inter", sans-serif;
        line-height: 1.5;
    }

    .container {
        max-width: 900px;
        margin: auto;
        padding: 1.4rem;
    }

    /* ---------- TOP BAR ---------- */
    .topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: .8rem 1rem;
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 12px;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    h1 {
        font-size: 1.4rem;
        margin: 0;
        font-weight: 600;
    }

    .timer {
        padding: .4rem .9rem;
        background: #0f1730;
        border-radius: 10px;
        font-weight: 600;
        color: var(--primary);
        border: 1px solid var(--border);
    }

    /* ---------- QUESTION CARD ---------- */
    .question {
        background: var(--card);
        padding: 1.4rem;
        margin: 1.2rem 0;
        border-radius: 14px;
        border: 1px solid var(--border);
        transition: .25s ease;
    }

    .question:hover {
        transform: translateY(-4px);
        border-color: var(--primary);
        box-shadow: 0 0 18px rgba(79,140,255,0.2);
    }

    .qtext {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: .8rem;
    }

    /* ---------- OPTIONS ---------- */
    .options label {
        display: flex;
        align-items: center;
        gap: .6rem;
        background: #0e162b;
        padding: .7rem;
        border-radius: 8px;
        margin: .45rem 0;
        cursor: pointer;
        transition: .2s ease;
        border: 1px solid transparent;
    }

    .options label:hover {
        background: #162246;
        border-color: var(--primary);
    }

    .options input {
        accent-color: var(--primary);
        transform: scale(1.2);
    }

    /* ---------- SUBMIT ---------- */
    .submit-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: .8rem;
        margin-top: 1.5rem;
        flex-wrap: wrap;
    }

    .btn {
        padding: .7rem 1.2rem;
        border-radius: 10px;
        border: none;
        cursor: pointer;
        font-weight: 600;
        transition: .2s ease;
    }

    .btn-primary {
        background: var(--primary);
        color: white;
    }

    .btn-primary:hover {
        background: #7aa8ff;
        transform: translateY(-1px);
    }

    .season-box {
        display: flex;
        align-items: center;
        gap: .4rem;
        color: var(--muted);
    }

    /* ---------- MOBILE ---------- */
    @media(max-width: 600px){
        .submit-row {
            flex-direction: column;
            align-items: stretch;
        }

        .btn {
            width: 100%;
        }

        .topbar {
            flex-direction: column;
            gap: .6rem;
            align-items: flex-start;
        }
    }
</style>
</head>

<body>

<div class="container">

    <!-- TOP BAR -->
    <div class="topbar">
        <h1>Quiz Time ✨</h1>
        <div class="timer">Time Left: <span id="time">--:--</span></div>
    </div>

    <form method="POST" id="quizForm">

        <input type="hidden" name="quiz_token"
               value="<?php echo htmlspecialchars($_SESSION['quiz_token']); ?>">

        <input type="hidden" id="time_remaining" name="time_remaining"
               value="<?php echo $time_limit; ?>">

        <!-- QUESTIONS (single-question view) -->
        <div id="progress" style="margin:8px 0;color:var(--muted);font-weight:600">Question <span id="currentNum">1</span>/<span id="totalNum"><?php echo count($questions); ?></span></div>

        <?php foreach ($questions as $index => $row): 
            $qid = (int)$row['id'];
        ?>
            <div class="question" data-index="<?php echo $index; ?>" style="display:<?php echo $index === 0 ? 'block' : 'none'; ?>">
                <div class="qtext">
                    <?php echo ($index + 1) . ". " . htmlspecialchars($row['question']); ?>
                </div>

                <div class="options">
                    <label>
                        <input type="radio" name="answers[<?php echo $qid; ?>]" value="A">
                        <?php echo htmlspecialchars($row['option_a']); ?>
                    </label>

                    <label>
                        <input type="radio" name="answers[<?php echo $qid; ?>]" value="B">
                        <?php echo htmlspecialchars($row['option_b']); ?>
                    </label>

                    <label>
                        <input type="radio" name="answers[<?php echo $qid; ?>]" value="C">
                        <?php echo htmlspecialchars($row['option_c']); ?>
                    </label>

                    <label>
                        <input type="radio" name="answers[<?php echo $qid; ?>]" value="D">
                        <?php echo htmlspecialchars($row['option_d']); ?>
                    </label>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Navigation controls for single-question flow -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;gap:12px;flex-wrap:wrap">
            <div>
                <button type="button" id="prevBtn" class="btn" style="background:transparent;border:1px solid rgba(255,255,255,0.04);color:var(--text);">&larr; Previous</button>
            </div>
            <div style="text-align:center;color:var(--muted)" id="navHint">Select an answer and click Next</div>
            <div>
                <button type="button" id="nextBtn" class="btn" style="background:var(--primary);color:#fff;padding:.6rem 1rem;border-radius:8px;">Next &rarr;</button>
            </div>
        </div>

        <!-- SUBMIT -->
        <div class="submit-row">

            <label class="season-box">
                <input type="checkbox" name="submit_to_season" value="1">
                Submit to Season Leaderboard
            </label>

            <button type="submit" class="btn btn-primary" id="submitBtn">
                Submit Quiz
            </button>

        </div>
    </form>
</div>


<script>
    let timeLeft = <?php echo (int)$time_limit; ?>;
    const timerEl = document.getElementById("time");
    const timeInput = document.getElementById("time_remaining");
    const form = document.getElementById("quizForm");
    const submitBtn = document.getElementById("submitBtn");
    const questions = document.querySelectorAll('.question');
    const totalQuestions = questions.length;
    const totalNum = document.getElementById('totalNum');
    let currentIndex = 0;
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const currentNum = document.getElementById('currentNum');

    function showQuestion(index){
        questions.forEach(q => q.style.display = 'none');
        if(questions[index]) questions[index].style.display = 'block';
        currentNum.textContent = index + 1;
        if(totalNum) totalNum.textContent = totalQuestions;
        // toggle prev/next/submit
        prevBtn.style.display = index === 0 ? 'none' : 'inline-block';
        if(index === totalQuestions - 1){
            nextBtn.style.display = 'none';
            submitBtn.style.display = 'inline-block';
        } else {
            nextBtn.style.display = 'inline-block';
            submitBtn.style.display = 'none';
        }
    }

    prevBtn.addEventListener('click', () => {
        if(currentIndex > 0){ currentIndex--; showQuestion(currentIndex); }
    });

    nextBtn.addEventListener('click', () => {
        // validate current question has selection before moving on
        const q = questions[currentIndex];
        const qIndex = q.getAttribute('data-index');
        const radios = q.querySelectorAll('input[type=radio]');
        let checked = false;
        radios.forEach(r => { if(r.checked) checked = true; });
        if(!checked){
            alert('Please select an answer before proceeding.');
            return;
        }
        if(currentIndex < totalQuestions - 1){ currentIndex++; showQuestion(currentIndex); }
    });

    // initialize view
    showQuestion(currentIndex);

    function format(s){
        const m = String(Math.floor(s/60)).padStart(2,'0');
        const sec = String(s%60).padStart(2,'0');
        return m + ":" + sec;
    }

    function tick(){
        timerEl.textContent = format(timeLeft);
        timeInput.value = timeLeft;

        if(timeLeft <= 0){
            // Ensure the hidden submit marker is present and button disabled
            markSubmitting();
            form.submit();
            return;
        }

        timeLeft--;
    }

    setInterval(tick, 1000);
    tick();

    // Add a hidden submission marker and disable the submit button when submitting.
    // Use a name that does NOT shadow the form.submit() method (avoid 'submit').
    function markSubmitting() {
        // If there's no hidden marker yet, add one so POST contains a marker
        if (!form.querySelector('input[name="_submitted"][type="hidden"]')) {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = '_submitted';
            hidden.value = '1';
            form.appendChild(hidden);
        }
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';
    }

    form.addEventListener("submit", (e) => {
        // mark submitting (adds hidden marker and disables button)
        markSubmitting();
    });
</script>

</body>
</html>
