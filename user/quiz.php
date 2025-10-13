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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
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

        // Insert per-question answers into user_answers
        $stmtAns = $conn->prepare('INSERT INTO user_answers(result_id, question_id, selected_option, is_correct) VALUES(?,?,?,?)');
        if (!$stmtAns) throw new Exception('Failed to prepare answers insert');
        foreach ($per_question as $qid => $info) {
            $sel = $info['selected'];
            $is = $info['is_correct'];
            $sparam = $sel !== null ? $sel : null;
            $stmtAns->bind_param('iisi', $result_id, $qid, $sparam, $is);
            $stmtAns->execute();
        }
        $stmtAns->close();

        // Handle "Submit to Season" if requested
        if (isset($_POST['submit_to_season']) && $_POST['submit_to_season'] === '1') {
            $stmtSeason = $conn->prepare('INSERT INTO season_leaderboard(user_id, season_id, quiz_id, result_id, score, total_questions, percentage, submitted_at) VALUES(?,?,?,?,?,?,?,NOW())');
            if (!$stmtSeason) throw new Exception('Failed to prepare season leaderboard insert');
            $stmtSeason->bind_param('iiiiidd', $user_id, $season_id, $quiz_id, $result_id, $score, $total_questions, $percentage);
            $stmtSeason->execute();
            $stmtSeason->close();
        }

        $conn->commit();
        // Store some summary in session for result page
        $_SESSION['score'] = $score;
        $_SESSION['total_questions'] = $total_questions;
        $_SESSION['percentage'] = $percentage;
        $_SESSION['result_id'] = $result_id;
        header('Location: result.php');
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        error_log('Quiz save error: ' . $e->getMessage());
        die('An error occurred while saving your answers. Please try again later.');
    }
}

// Otherwise show quiz: fetch 20 random questions
$questions = [];
$res = $conn->query('SELECT id, question, option_a, option_b, option_c, option_d FROM questions ORDER BY RAND() LIMIT 20');
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
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --primary: #3b82f6; /* Soft blue for accents */
            --secondary: #6b7280; /* Neutral gray for secondary elements */
            --background: #f3f4f6; /* Light gray background */
            --card-bg: #ffffff; /* White cards for contrast */
            --text-primary: #1f2937; /* Dark gray for main text */
            --text-secondary: #6b7280; /* Lighter gray for secondary text */
            --border: #e5e7eb; /* Subtle border color */
        }
        body {
            margin: 0;
            font-family: 'Inter', 'Segoe UI', 'Roboto', 'Arial', sans-serif;
            background: var(--background);
            color: var(--text-primary);
            min-height: 100vh;
            line-height: 1.5;
        }
        .container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 1.5rem;
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }
        h1 {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 600;
        }
        .timer {
            background: var(--card-bg);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            color: var(--text-primary);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        .timer span {
            color: var(--primary);
        }
        .question {
            background: var(--card-bg);
            padding: 1.5rem;
            margin: 1rem 0;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease;
        }
        .question:hover {
            transform: translateY(-4px);
        }
        .qtext {
            font-weight: 600;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        .options label {
            display: block;
            margin: 0.5rem 0;
            padding: 0.75rem;
            border-radius: 8px;
            cursor: pointer;
            background: #f9fafb;
            transition: background-color 0.2s ease;
        }
        .options label:hover {
            background: #f1f5f9;
        }
        .options input {
            margin-right: 0.5rem;
            accent-color: var(--primary);
        }
        .submit-row {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }
        .btn {
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.1s ease;
        }
        .btn-primary {
            background: var(--primary);
            color: #ffffff;
        }
        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }
        .btn-season {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-primary);
        }
        .btn-season:hover {
            background: #f9fafb;
            transform: translateY(-1px);
        }
        @media (max-width: 640px) {
            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }
            .submit-row {
                justify-content: center;
                flex-direction: column;
            }
            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="topbar">
            <h1>Quiz Time!</h1>
            <div class="timer" id="timer">Time left: <span id="time">--:--</span></div>
        </div>

        <?php if (empty($questions)): ?>
            <div class="question">No questions available. Please contact the administrator.</div>
        <?php else: ?>
            <form method="POST" id="quizForm">
                <input type="hidden" name="quiz_token" value="<?php echo htmlspecialchars($_SESSION['quiz_token']); ?>">
                <input type="hidden" id="time_remaining" name="time_remaining" value="<?php echo $time_limit; ?>">

                <?php foreach ($questions as $index => $row):
                    $qid = (int)$row['id'];
                ?>
                    <div class="question">
                        <div class="qtext"><?php echo ($index + 1) . '. ' . htmlspecialchars($row['question']); ?></div>
                        <div class="options">
                            <label><input type="radio" name="answers[<?php echo $qid; ?>]" value="A" required> <?php echo htmlspecialchars($row['option_a']); ?></label>
                            <label><input type="radio" name="answers[<?php echo $qid; ?>]" value="B"> <?php echo htmlspecialchars($row['option_b']); ?></label>
                            <label><input type="radio" name="answers[<?php echo $qid; ?>]" value="C"> <?php echo htmlspecialchars($row['option_c']); ?></label>
                            <label><input type="radio" name="answers[<?php echo $qid; ?>]" value="D"> <?php echo htmlspecialchars($row['option_d']); ?></label>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="submit-row">
                    <label style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" name="submit_to_season" value="1">
                        <span>Submit to Season Leaderboard</span>
                    </label>
                    <button type="submit" name="submit" class="btn btn-primary" id="submitBtn">Submit Quiz</button>
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

        function formatTime(s) {
            const m = Math.floor(s / 60).toString().padStart(2, '0');
            const sec = (s % 60).toString().padStart(2, '0');
            return m + ':' + sec;
        }

        function tick() {
            timerEl.textContent = formatTime(timeLeft);
            timeInput.value = timeLeft;
            if (timeLeft <= 0) {
                // Auto-submit
                submitBtn.disabled = true;
                submitBtn.textContent = 'Submitting...';
                form.submit();
                return;
            }
            timeLeft--;
        }

        // Initialize
        timerEl.textContent = formatTime(timeLeft);
        const interval = setInterval(() => {
            tick();
        }, 1000);

        // On manual submit, disable button to prevent double submits
        form.addEventListener('submit', function(e) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';
        });
    </script>
</body>
</html>