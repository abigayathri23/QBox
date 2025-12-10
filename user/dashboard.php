<?php
session_start();
require_once '../config/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// Fetch user info from DB (name, email)
$name = 'User';
$email = '';
if($stmt = $conn->prepare('SELECT name, email FROM users WHERE id = ?')){
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($row = $res->fetch_assoc()){
        $name = !empty($row['name']) ? $row['name'] : $row['email'];
        $email = $row['email'];
    }
    $stmt->close();
}

// Default stats
$totalQuizzes = 0;
$bestScore = 0;
$lastAttemptScore = null;
$lastAttemptTaken = null;

// Total quizzes taken and best score
if($stmt = $conn->prepare('SELECT COUNT(*) as total, COALESCE(MAX(score),0) as best FROM user_results WHERE user_id = ?')){
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($r = $res->fetch_assoc()){
        $totalQuizzes = (int)$r['total'];
        $bestScore = (int)$r['best'];
    }
    $stmt->close();
}

// Last attempt
if($stmt = $conn->prepare('SELECT score, percentage, taken_at FROM user_results WHERE user_id = ? ORDER BY taken_at DESC LIMIT 1')){
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($r = $res->fetch_assoc()){
        $lastAttemptScore = $r['score'];
        $lastAttemptTaken = $r['taken_at'];
        // if percentage column exists use it for display; otherwise compute if needed
        $lastAttemptPerc = isset($r['percentage']) ? $r['percentage'] : null;
    }
    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Q_BOX – Dashboard</title>

<style>
/* ------------------ DARK BLUE THEME (REFINED) ------------------ */
:root {
    --primary: #1a73e8;
    --primary-dark: #0d47a1;
    --background: #0b1220;
    --card-bg: #121c31;
    --card-glow: rgba(26, 115, 232, 0.25);
    --text-primary: #ffffff;
    --text-secondary: #a8b3c7;
    --border: #1d2942;
}

body {
    margin: 0;
    font-family: 'Inter', sans-serif;
    background: var(--background);
    color: var(--text-primary);
}

/* Wrapper */
.wrap {
    max-width: 1200px;
    margin: auto;
    padding: 1.5rem;
}

/* ------------------ HEADER ------------------ */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.2rem 0;
    border-bottom: 1px solid var(--border);
}

.brand {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.logo {
    width: 55px;
    height: 55px;
    border-radius: 14px;
    background: var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    font-weight: 700;
    color: #fff;
    box-shadow: 0 0 15px var(--card-glow);
}

h1 {
    margin: 0;
    font-size: 1.9rem;
    letter-spacing: 0.5px;
}

.lead {
    margin: 3px 0 0;
    font-size: 0.95rem;
    color: var(--text-secondary);
}

/* ------------------ BUTTONS ------------------ */
.actions {
    display: flex;
    gap: 0.8rem;
    flex-wrap: wrap;
}

.btn {
    padding: 0.7rem 1.2rem;
    border-radius: 10px;
    font-size: 0.9rem;
    text-decoration: none;
    transition: 0.2s ease;
    font-weight: 500;
}

.btn-primary {
    background: var(--primary);
    color: #fff;
    box-shadow: 0 0 10px var(--card-glow);
}
.btn-primary:hover {
    background: #2a82ff;
    transform: translateY(-2px);
}

.btn-secondary {
    border: 1px solid var(--border);
    color: var(--text-secondary);
    background: transparent;
}
.btn-secondary:hover {
    background: rgba(255,255,255,0.06);
    transform: translateY(-2px);
}

/* ------------------ STAT CARDS ------------------ */
.grid {
    margin-top: 2rem;
    display: grid;
    gap: 1.4rem;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
}

.card {
    background: var(--card-bg);
    padding: 1.4rem;
    border-radius: 14px;
    box-shadow: 0 0 25px rgba(0,0,0,0.45);
    border: 1px solid var(--border);
    transition: 0.25s ease;
}
.card:hover {
    transform: translateY(-4px);
    box-shadow: 0 0 25px var(--card-glow);
}

.card h3 {
    font-size: 1rem;
    color: var(--text-secondary);
}

.stat {
    font-size: 2.2rem;
    margin-top: 0.6rem;
    font-weight: 700;
}

.muted {
    font-size: 0.8rem;
    margin-top: 0.5rem;
    color: var(--text-secondary);
}

/* ------------------ QUICK ACCESS BOX ------------------ */
.welcome {
    margin-top: 2rem;
    background: var(--card-bg);
    border-radius: 14px;
    padding: 1.5rem;
    border: 1px solid var(--border);
    box-shadow: 0 0 20px rgba(0,0,0,0.45);
}

.welcome h3 {
    margin-bottom: 0.6rem;
}

.btn-group {
    margin-top: 1rem;
    display: flex;
    gap: 0.8rem;
    flex-wrap: wrap;
}

/* ------------------ MOBILE OPTIMIZATION ------------------ */
@media (max-width: 700px) {
    .header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    .actions {
        width: 100%;
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

<div class="wrap">

    <!-- HEADER -->
    <header class="header">
        <div class="brand">
            <div class="logo">Q</div>
            <div>
                <h1>Q_BOX Dashboard</h1>
                <p class="lead">Welcome back, <?php echo htmlspecialchars($name); ?> 👋</p>
            </div>
        </div>

        <div class="actions">
            <a href="../logout.php" class="btn btn-secondary">Logout</a>
        </div>
    </header>

    <?php if(isset($_SESSION['score'])): ?>
        <div class="mt-1" style="max-width:1200px;margin:12px auto;padding:0 1.5rem;">
            <div class="card" style="border-left:4px solid var(--primary);">
                <strong>Quiz submitted</strong>
                <div style="margin-top:6px;color:var(--text-muted);">Your score: <?php echo htmlspecialchars($_SESSION['score']); ?> / <?php echo htmlspecialchars($_SESSION['total_questions']); ?> (<?php echo htmlspecialchars($_SESSION['percentage']); ?>%)</div>
            </div>
        </div>
    <?php
        // clear summary values so message shows only once
        unset($_SESSION['score'], $_SESSION['total_questions'], $_SESSION['percentage'], $_SESSION['result_id']);
    endif;
    ?>

    <!-- STAT CARDS -->
    <div class="grid">
        <div class="card">
            <h3>Total Quizzes Taken</h3>
            <p class="stat"><?php echo (int)$totalQuizzes; ?></p>
            <p class="muted">Keep learning & pushing forward!</p>
        </div>

        <div class="card">
            <h3>Best Score</h3>
            <p class="stat"><?php echo htmlspecialchars($bestScore) === '' ? '0' : htmlspecialchars($bestScore); ?>%</p>
            <p class="muted">Great job, you are improving!</p>
        </div>

        <div class="card">
            <h3>Last Attempt</h3>
            <p class="stat"><?php echo $lastAttemptScore !== null ? htmlspecialchars($lastAttemptScore) . '%' : '—'; ?></p>
            <p class="muted"><?php echo $lastAttemptTaken ? htmlspecialchars($lastAttemptTaken) : 'No attempts yet'; ?></p>
        </div>
    </div>

    <!-- QUICK ACCESS SECTION -->
    <div class="welcome">
        <h3>Quick Access</h3>
        <div class="btn-group">
            <a href="quiz.php" class="btn btn-primary">Start New Quiz</a>
            <a href="history.php" class="btn btn-secondary">View History</a>
            <a href="leaderboard.php" class="btn btn-secondary">Leaderboard</a>
        </div>
    </div>

</div>

</body>
</html>
