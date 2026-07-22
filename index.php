<?php
session_start();

if (isset($_SESSION['student_id'])) {
    header('Location: student/dashboard.php');
    exit;
}

if (isset($_SESSION['admin_id'])) {
    header('Location: admin/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="Poly-Health mental health screening and student support platform."
    >

    <title>Poly-Health | Mental Health Support</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-light fixed-top poly-navbar">
    <div class="container">

        <a class="navbar-brand poly-logo" href="index.php">
            POLY-HEALTH
        </a>

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNavbar"
            aria-controls="mainNavbar"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <div
            class="collapse navbar-collapse"
            id="mainNavbar"
        >
            <ul class="navbar-nav ms-auto align-items-lg-center">

                <li class="nav-item">
                    <a class="nav-link" href="#about">
                        About
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#features">
                        Features
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="login.php">
                        Login
                    </a>
                </li>

                <li class="nav-item ms-lg-3">
                    <a
                        class="btn btn-poly btn-small"
                        href="register.php"
                    >
                        Get Started
                    </a>
                </li>

            </ul>
        </div>

    </div>
</nav>

<main>

    <section class="hero-section">
        <div class="hero-blob hero-blob-one"></div>
        <div class="hero-blob hero-blob-two"></div>

        <div class="container position-relative">
            <div class="row align-items-center min-vh-100">

                <div class="col-lg-6 hero-content">

                    <span class="hero-badge">
                        🌿 A safe space for students
                    </span>

                    <h1>
                        Are You Feeling
                        <span>Okay Today?</span>
                    </h1>

                    <p class="hero-description">
                        Your mental health matters. Poly-Health gives you
                        a safe and private place to check your emotional
                        wellbeing, track your mood, write your thoughts,
                        and connect with support.
                    </p>

                    <div class="hero-buttons">

                        <a
                            href="register.php"
                            class="btn btn-poly btn-lg"
                        >
                            Get Started
                        </a>

                        <a
                            href="login.php"
                            class="btn btn-outline-poly btn-lg"
                        >
                            Student Login
                        </a>

                    </div>

                    <div class="hero-trust">

                        <div class="trust-item">
                            <span class="trust-icon">✓</span>
                            Private
                        </div>

                        <div class="trust-item">
                            <span class="trust-icon">✓</span>
                            Student Friendly
                        </div>

                        <div class="trust-item">
                            <span class="trust-icon">✓</span>
                            Easy to Use
                        </div>

                    </div>

                </div>

                <div class="col-lg-6 text-center">

                    <div class="hero-image-wrapper">

                        <div class="floating-card floating-card-one">
                            <span>😊</span>
                            <div>
                                <strong>Mood tracking</strong>
                                <small>Understand your feelings</small>
                            </div>
                        </div>

                        <div class="floating-card floating-card-two">
                            <span>🌱</span>
                            <div>
                                <strong>Daily wellbeing</strong>
                                <small>Take care of yourself</small>
                            </div>
                        </div>

                        <div class="floating-card floating-card-three">
                            <span>💚</span>
                            <div>
                                <strong>You are not alone</strong>
                                <small>Support is available</small>
                            </div>
                        </div>

                    </div>

                </div>

            </div>
        </div>
    </section>

    <section id="about" class="section-padding">
        <div class="container">

            <div class="section-heading text-center">
                <span>About Poly-Health</span>

                <h2>
                    A healthier student community starts with awareness
                </h2>

                <p>
                    Poly-Health helps students identify emotional
                    difficulties early and gives counsellors a centralized
                    platform for monitoring screening results and support
                    needs.
                </p>
            </div>

            <div class="row g-4 mt-4">

                <div class="col-md-4">
                    <article class="info-card h-100">
                        <div class="info-icon">🔐</div>

                        <h3>Private and Secure</h3>

                        <p>
                            Student accounts and screening records are
                            protected through secure authentication.
                        </p>
                    </article>
                </div>

                <div class="col-md-4">
                    <article class="info-card h-100">
                        <div class="info-icon">🧠</div>

                        <h3>Early Screening</h3>

                        <p>
                            Complete a DASS-21 screening and receive an
                            immediate result with basic recommendations.
                        </p>
                    </article>
                </div>

                <div class="col-md-4">
                    <article class="info-card h-100">
                        <div class="info-icon">🤝</div>

                        <h3>Student Support</h3>

                        <p>
                            Access helpful resources and request support
                            when you feel overwhelmed.
                        </p>
                    </article>
                </div>

            </div>
        </div>
    </section>

    <section id="features" class="features-section section-padding">
        <div class="container">

            <div class="section-heading text-center">
                <span>Main Features</span>

                <h2>
                    Everything you need in one supportive platform
                </h2>
            </div>

            <div class="row g-4 mt-4">

                <div class="col-sm-6 col-lg-3">
                    <article class="feature-card h-100">
                        <div class="feature-number">01</div>
                        <h3>DASS-21 Screening</h3>
                        <p>
                            Answer 21 simple questions and understand
                            your current mental wellbeing.
                        </p>
                    </article>
                </div>

                <div class="col-sm-6 col-lg-3">
                    <article class="feature-card h-100">
                        <div class="feature-number">02</div>
                        <h3>Mood Tracking</h3>
                        <p>
                            Record how you feel and identify emotional
                            changes over time.
                        </p>
                    </article>
                </div>

                <div class="col-sm-6 col-lg-3">
                    <article class="feature-card h-100">
                        <div class="feature-number">03</div>
                        <h3>Private Journal</h3>
                        <p>
                            Write about your thoughts, feelings, and daily
                            experiences.
                        </p>
                    </article>
                </div>

                <div class="col-sm-6 col-lg-3">
                    <article class="feature-card h-100">
                        <div class="feature-number">04</div>
                        <h3>Support Access</h3>
                        <p>
                            View relaxation activities, motivation, and
                            counsellor support options.
                        </p>
                    </article>
                </div>

            </div>

        </div>
    </section>

    <section class="cta-section">
        <div class="container">
            <div class="cta-card">

                <div>
                    <span class="cta-label">
                        Start your wellbeing journey
                    </span>

                    <h2>
                        Small steps can make a meaningful difference.
                    </h2>

                    <p>
                        Create your Poly-Health account and begin checking
                        in with yourself today.
                    </p>
                </div>

                <a
                    href="register.php"
                    class="btn btn-light btn-lg cta-button"
                >
                    Create Account
                </a>

            </div>
        </div>
    </section>

</main>

</main>


<footer class="main-footer">

    <!-- Developer Credit -->
    <div class="developer-section">

        <h4>
            Poly-Health
        </h4>

        <p>
            Smart Mental Health Screening and Support System
        </p>

        <div class="developer-team">

            <span>Created & Developed by:</span>

            <strong>
                NURUL DAMIA HUSNA BINTI BANI HASNAN
            </strong>

            <strong>
                TASNIM BINTI KHAIRIL ZAMAN
            </strong>

            <strong>
                LEE NYUK YUNG
            </strong>

        </div>

        <small>
            Diploma Batch 2024 | Student of Politeknik Sultan Idris Shah
        </small>

    </div>


    <!-- Website Footer -->
    <div class="footer-bottom">

        <div>

            <h3>
                POLY-HEALTH
            </h3>

            <p>
                Smart Mental Health Screening and Support
            </p>

        </div>

        <span>
            © 2026 Poly-Health. All rights reserved.
        </span>

    </div>

</footer>


</body>
</html>


