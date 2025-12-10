<?php 
session_start();
require_once '../config/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user'){
    header("Location: ../index.php");
    exit();
}

$sql = "SELECT * FROM user_results 
        WHERE user_id=".(int)$_SESSION['user_id']." 
        ORDER BY taken_at DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Quiz History - QBox</title>

<style>
    /* --------- DARK THEME --------- */
    :root {
        --bg: #0b1220;
        --card: #121c31;
        --border: #1d2942;
        --text: #ffffff;
        --muted: #a8b3c7;
        --primary: #4f8cff;
        --row-hover: #162246;
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
        font-size: 1.8rem;
        margin-bottom: 1.2rem;
    }

    /* -------- TABLE -------- */
    .history-box {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 0 20px rgba(0,0,0,0.35);
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.95rem;
    }

    th {
        background: #0f1730;
        padding: 12px;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: 0.8px;
        border-bottom: 1px solid var(--border);
    }

    td {
        padding: 12px;
        text-align: center;
        border-bottom: 1px solid var(--border);
    }

    tr:hover {
        background: var(--row-hover);
        transition: .2s ease;
    }

    /* -------- BACK BUTTON -------- */
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

    /* -------- RESPONSIVE -------- */
    @media(max-width: 600px){
        table, th, td {
            font-size: 0.8rem;
        }

        .container {
            padding: 1rem;
        }
    }
</style>

</head>

<body>

<div class="container">

    <h1>📘 My Quiz History</h1>

    <div class="history-box">

        <table>
            <tr>
                <th>Score</th>
                <th>Total</th>
                <th>Percentage</th>
                <th>Date Taken</th>
            </tr>

            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
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
