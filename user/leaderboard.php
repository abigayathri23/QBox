<?php
session_start();
require_once '../config/db.php';

// Only users allowed
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user'){
    header("Location: ../index.php");
    exit();
}

/*
    Leaderboard:
    Highest percentage first
*/
$sql = "
    SELECT ur.*, u.name 
    FROM user_results ur
    JOIN users u ON u.id = ur.user_id
    ORDER BY ur.percentage DESC, ur.score DESC, ur.taken_at DESC
";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Leaderboard - QBox</title>

<style>
    /* -------- DARK THEME -------- */
    :root {
        --bg: #0b1220;
        --card: #121c31;
        --border: #1d2942;
        --text: #ffffff;
        --muted: #a8b3c7;
        --primary: #4f8cff;
        --hover: #162246;
    }

    body {
        margin: 0;
        background: var(--bg);
        color: var(--text);
        font-family: "Inter", sans-serif;
    }

    .container {
        max-width: 900px;
        margin: auto;
        padding: 1.5rem;
    }

    h1 {
        text-align: center;
        font-size: 1.9rem;
        margin-bottom: 1rem;
    }

    /* -------- leaderboard card -------- */
    .board {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 0 18px rgba(0,0,0,0.35);
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.95rem;
    }

    th {
        padding: 12px;
        background: #0f1730;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: .8px;
        border-bottom: 1px solid var(--border);
    }

    td {
        padding: 12px;
        text-align: center;
        border-bottom: 1px solid var(--border);
    }

    tr:hover {
        background: var(--hover);
        transition: .2s ease;
    }

    .back-btn {
        display: inline-block;
        margin-top: 20px;
        padding: .6rem 1.2rem;
        background: transparent;
        border: 1px solid var(--border);
        color: var(--text);
        border-radius: 8px;
        text-decoration: none;
        transition: .2s ease;
    }

    .back-btn:hover {
        background: var(--primary);
        border-color: var(--primary);
        color: white;
    }

    @media(max-width:600px){
        table, th, td {
            font-size: .8rem;
        }

        .container {
            padding: 1rem;
        }
    }
</style>
</head>

<body>

<div class="container">

    <h1>🏆 Quiz Leaderboard</h1>

    <div class="board">

        <table>
            <tr>
                <th>Rank</th>
                <th>Name</th>
                <th>Score</th>
                <th>Total</th>
                <th>Percentage</th>
                <th>Date</th>
            </tr>

            <?php 
            $rank = 1;

            while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $rank++; ?></td>

                    <td><?php echo htmlspecialchars($row['name']); ?></td>

                    <td><?php echo htmlspecialchars($row['score']); ?></td>

                    <td><?php echo htmlspecialchars($row['total_questions']); ?></td>

                    <td><?php echo htmlspecialchars($row['percentage']); ?>%</td>

                    <td><?php echo htmlspecialchars($row['taken_at']); ?></td>
                </tr>
            <?php endwhile; ?>

        </table>

    </div>

    <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>

</div>

</body>
</html>
