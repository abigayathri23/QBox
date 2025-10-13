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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - QBox</title>
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
        .wrap {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 1.5rem;
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border);
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .logo {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            background: var(--primary);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.5rem;
        }
        h1 {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 600;
        }
        p.lead {
            margin: 0.25rem 0 0;
            color: var(--text-secondary);
            font-size: 1rem;
        }
        .actions {
            display: flex;
            gap: 0.75rem;
        }
        .btn {
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
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
        .btn-secondary {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-primary);
        }
        .btn-secondary:hover {
            background: #f9fafb;
            transform: translateY(-1px);
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-top: 2rem;
        }
        .card {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease;
        }
        .card:hover {
            transform: translateY(-4px);
        }
        .card h3 {
            margin: 0;
            font-size: 1rem;
            color: var(--text-secondary);
            font-weight: 500;
        }
        .stat {
            font-size: 2rem;
            font-weight: 700;
            margin-top: 0.5rem;
            color: var(--text-primary);
        }
        .muted {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        .welcome {
            margin-top: 2rem;
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .welcome p {
            margin: 0.75rem 0 0;
            color: var(--text-secondary);
            font-size: 0.95rem;
        }
        .welcome .btn-group {
            margin-top: 1rem;
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        @media (max-width: 880px) {
            .grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 560px) {
            .grid {
                grid-template-columns: 1fr;
            }
            .header {
                flex-direction: column;
                align-items: flex-start;
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
        <div class="header">
            <div class="brand">
                <div class="logo">Q</div>
                <div>
                    <h1>Welcome back, <?php echo htmlspecialchars($username); ?>!</h1>
                    <p class="lead">Ready for a new challenge? Track your progress below.</p>
                </div>
            </div>
            <div class="actions">
                <a href="quiz.php" class="btn btn-primary">Start Quiz</a>
                <a href="history.php" class="btn btn-secondary">My History</a>
                <a href="../logout.php" class="btn btn-secondary">Logout</a>
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
                <div class="muted"><?php echo $lastAttempt ? date('F j, Y \a\t g:ia', strtotime($lastAttempt['taken_at'])) : 'No attempts yet'; ?></div>
            </div>
        </div>

        <div class="welcome">
            <h3>Quick Links</h3>
            <p>Use the buttons to start a timed quiz, review past attempts, or manage your account.</p>
            <div class="btn-group">
                <a href="quiz.php" class="btn btn-primary">Take New Quiz</a>
                <a href="history.php" class="btn btn-secondary">View History</a>
                <a href="../logout.php" class="btn btn-secondary">Sign Out</a>
            </div>
        </div>
    </div>
</body>
</html>