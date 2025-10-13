<?php
session_start();
require_once '../config/db.php';
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user'){
    header("Location: ../index.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// Fetch user info (name or email fallback)
$username = 'User';
if($stmt = $conn->prepare('SELECT name, email FROM users WHERE id = ?')){
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($row = $res->fetch_assoc()){
        $username = !empty($row['name']) ? $row['name'] : $row['email'];
    }
    $stmt->close();
}

// Fetch stats: total quizzes taken, best score, last attempt
$totalQuizzes = 0;
$bestScore = 0;
$lastAttempt = null;

if($stmt = $conn->prepare('SELECT COUNT(*) as total, COALESCE(MAX(score),0) as best FROM user_results WHERE user_id = ?')){
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($r = $res->fetch_assoc()){
        $totalQuizzes = (int) $r['total'];
        $bestScore = (int) $r['best'];
    }
    $stmt->close();
}

if($stmt = $conn->prepare('SELECT score, taken_at FROM user_results WHERE user_id = ? ORDER BY taken_at DESC LIMIT 1')){
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($r = $res->fetch_assoc()){
        $lastAttempt = $r;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dashboard - QBox</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root{--bg1:#0f2027;--bg2:#2c5364;--card:#111827;--accent1:#ff8a00;--accent2:#e52e71}
        body{margin:0;font-family:Inter,Segoe UI,Roboto,Arial,sans-serif;background:linear-gradient(135deg,var(--bg1),var(--bg2));color:#e6eef8;min-height:100vh}
        .wrap{max-width:1100px;margin:40px auto;padding:24px}
        .header{display:flex;align-items:center;justify-content:space-between;gap:16px}
        .brand{display:flex;align-items:center;gap:12px}
        .logo{width:56px;height:56px;border-radius:12px;background:linear-gradient(90deg,var(--accent1),var(--accent2));display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:20px}
        h1{margin:0;font-size:22px}
        p.lead{margin:4px 0 0;color:#cfe6ff}
        .actions{display:flex;gap:10px}
        .btn{background:linear-gradient(90deg,var(--accent1),var(--accent2));color:#fff;padding:10px 16px;border-radius:10px;text-decoration:none;font-weight:600}
        .grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:24px}
        .card{background:rgba(255,255,255,0.04);padding:18px;border-radius:12px;box-shadow:0 6px 18px rgba(2,6,23,0.6)}
        .card h3{margin:0;font-size:14px;color:#cfe6ff}
        .stat{font-size:28px;font-weight:700;margin-top:8px}
        .muted{color:#9fb7d9;font-size:13px;margin-top:6px}
        .welcome{margin-top:28px;background:linear-gradient(90deg,rgba(255,255,255,0.03),rgba(255,255,255,0.01));padding:20px;border-radius:12px}
        .welcome p{margin:8px 0 0;color:#dbeeff}
        @media (max-width:880px){.grid{grid-template-columns:repeat(2,1fr)} }
        @media (max-width:560px){.grid{grid-template-columns:1fr}.header{flex-direction:column;align-items:flex-start}}
    </style>
</head>
<body>
    <div class="wrap">
        <div class="header">
            <div class="brand">
                <div class="logo">Q</div>
                <div>
                    <h1>Welcome back, <?php echo htmlspecialchars($username); ?>!</h1>
                    <p class="lead">Ready for a new challenge? Track your progress below.</p>
                </div>
            </div>
            <div class="actions">
                <a href="quiz.php" class="btn">Start Quiz</a>
                <a href="history.php" class="btn" style="background:transparent;border:1px solid rgba(255,255,255,0.08);">My History</a>
                <a href="../logout.php" class="btn" style="background:transparent;border:1px solid rgba(255,255,255,0.08);">Logout</a>
            </div>
        </div>

        <div class="grid">
            <div class="card">
                <h3>Total Quizzes</h3>
                <div class="stat"><?php echo $totalQuizzes; ?></div>
                <div class="muted">Quizzes you've taken so far</div>
            </div>
            <div class="card">
                <h3>Best Score</h3>
                <div class="stat"><?php echo $bestScore; ?> / 20</div>
                <div class="muted">Your highest recorded score</div>
            </div>
            <div class="card">
                <h3>Last Attempt</h3>
                <div class="stat"><?php echo $lastAttempt ? htmlspecialchars($lastAttempt['score']) . ' / 20' : '—'; ?></div>
                <div class="muted"><?php echo $lastAttempt ? date('F j, Y \a\t g:ia', strtotime($lastAttempt['date_taken'])) : 'No attempts yet'; ?></div>
            </div>
        </div>

        <div class="welcome">
            <h3 style="margin:0">Quick Links</h3>
            <p style="margin:10px 0 0">Use the buttons to start a timed quiz, review past attempts, or manage your account.</p>
            <div style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap">
                <a href="quiz.php" class="btn">Take New Quiz</a>
                <a href="history.php" class="btn" style="background:transparent;border:1px solid rgba(255,255,255,0.08);">View History</a>
                <a href="../logout.php" class="btn" style="background:transparent;border:1px solid rgba(255,255,255,0.08);">Sign Out</a>
            </div>
        </div>
    </div>
</body>
</html>
