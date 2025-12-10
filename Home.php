<?php 
// Public landing page for Q_Box

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Q_Box – Focus|Analyze|Rise</title>

<style>
/* -----------------------------------------------------------
   GLOBAL
----------------------------------------------------------- */
body {
    margin: 0;
    font-family: "Poppins", sans-serif;
    background: #0b0f1a;
    overflow-x: hidden;
}

/* -----------------------------------------------------------
   TOP HEADER
----------------------------------------------------------- */
.top-header {
    text-align: center;
    padding: 1px 1px;
    background: linear-gradient(135deg, #0d1b4c, #131f3eff, #0b0f1a);
    color: #ffffff;
    position: relative;
}

.top-header .glow-layer {
    position: absolute;
    inset: 0;
    background: radial-gradient(circle, rgba(16, 11, 75, 0.1), transparent 60%);
    animation: slowGlow 6s infinite ease-in-out;
}

@keyframes slowGlow {
    0% { opacity: 0.2; }
    50% { opacity: 0.5; }
    100% { opacity: 0.2; }
}

.title-line {
    font-size: 1.8rem;
    font-weight: 800;
    letter-spacing: 3px;
    position: relative;
    z-index: 2;
    color: #c8d8ff;
}

.sub-title {
    margin-top: 8px;
    font-size: 1.15rem;
    color: #ffb2f5;
    font-weight: 500;
    position: relative;
    z-index: 2;
}

/* -----------------------------------------------------------
   HERO SECTION
----------------------------------------------------------- */
.hero-section {
    text-align: center;
    padding: 130px 20px 120px;
    background: linear-gradient(160deg, #10214b, #0b0f1a, #110a31ff);
    position: relative;
}

/* Soft glow blobs */
.hero-section::before {
    content: "";
    position: absolute;
    width: 350px;
    height: 350px;
    background: radial-gradient(circle, rgba(10, 4, 54, 0.77), transparent 70%);
    top: 10%;
    left: -5%;
    filter: blur(70px);
}

.hero-section::after {
    content: "";
    position: absolute;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(0,95,255,0.25), transparent 70%);
    bottom: 10%;
    right: -5%;
    filter: blur(80px);
}

.main-title {
    font-size: 4rem;
    font-weight: 800;
    color: #8fbaff;
    text-shadow: 0 0 25px rgba(140,170,255,0.6);
    letter-spacing: 4px;
    animation: fadeInTop 2s ease forwards;
}

@keyframes fadeInTop {
    from { opacity: 0; transform: translateY(-25px); }
    to { opacity: 1; transform: translateY(0); }
}

.tagline {
    font-size: 1.35rem;
    color: #ffcaec;
    margin-top: 15px;
    animation: fadeIn 2.5s ease forwards;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* -----------------------------------------------------------
   PORTAL BUTTON
----------------------------------------------------------- */
.btn-portal {
    display: inline-block;
    margin-top: 45px;
    padding: 17px 55px;
    font-size: 1.35rem;
    font-weight: 700;
    border-radius: 14px;
    text-decoration: none;
    color: white;
    background: linear-gradient(135deg, #183a9a, #3c7df3, #1422a3ff);
    box-shadow: 0 0 25px rgba(120,140,255,0.6);
    position: relative;
    overflow: hidden;
    transition: 0.35s ease;
}

.btn-portal:hover {
    transform: translateY(-6px) scale(1.05);
    box-shadow: 0 0 40px  #103476ff;
}

.btn-portal::after {
    content: "";
    position: absolute;
    top: 0; left: -120%;
    width: 100%; height: 100%;
    background: linear-gradient(120deg, transparent, rgba(255,255,255,0.4), transparent);
    animation: shineEffect 2.5s infinite;
}

@keyframes shineEffect {
    0% { left: -120%; }
    100% { left: 120%; }
}

/* -----------------------------------------------------------
   FOOTER
----------------------------------------------------------- */
.footer {
    text-align: center;
    padding: 22px 0;
    background: linear-gradient(120deg, #0b0f1a, #0b0f1a, #110a31ff);
    color: #c5d1ff;
    letter-spacing: 1px;
    font-size: 1rem;
}

</style>
</head>

<body>

<!-- ========================================= -->
<!-- TOP HEADER -->
<!-- ========================================= -->
<header class="top-header">
    <div class="glow-layer"></div>
    <h2 class="title-line"><i>Self-analysis is the first step to self-mastery</i></h2>
</header>

<!-- ========================================= -->
<!-- HERO SECTION -->
<!-- ========================================= -->
<section class="hero-section">
    <h1 class="main-title">Q_BOX Self Analyze Your Skill</h1>
    <p class="tagline"><i> Focus | Analyze | Rise | Unlock the thinker within</i></p>

    <a href="index.php" class="btn-portal">Login</a>
</section>

<!-- ========================================= -->
<!-- FOOTER -->
<!-- ========================================= -->
<footer class="footer">
    © 2025 | Crafted with ❤️ | Abi Gayathri
</footer>

</body>
</html>
