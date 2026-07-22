<?php

require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/functions.php";

require_student_login();

$studentName = $_SESSION["fullname"] ?? "Student";

/*
|--------------------------------------------------------------------------
| UPPSIK contact information
|--------------------------------------------------------------------------
| Replace the information below with your institution's actual details.
*/

$uppsikName = "Puan Noraisah Ismail (Ketua Unit Pengurusan Psikologi & Kesihatan)";
$institutionName = "POLITEKNIK SULTAN IDRIS SHAH (PSIS)";

$phoneDisplay = "+60 17-666 9724";
$phoneLink = "+60176669724";

/*
|--------------------------------------------------------------------------
| WhatsApp number format
|--------------------------------------------------------------------------
| Use country code without:
| - plus sign
| - spaces
| - hyphens
|
| Example: 60123456789
*/

$whatsappNumber = "60176669724";

$whatsappMessage =
    "Hello UPPSIK, I am a student and I would like to request "
    . "information about counselling support.";

$whatsappUrl =
    "https://wa.me/"
    . $whatsappNumber
    . "?text="
    . rawurlencode($whatsappMessage);

$email = "noraisyah@psis.edu.my";

$officeLocation =
    "Pejabat UPPSIK , JHEP, POLITEKNIK SULTAN IDRIS SHAH (PSIS)";

$officeHours = [
    "Monday – Thursday" => "8:00 AM – 5:00 PM",
    "Friday" => "8:00 AM – 12:15 PM and 2:45 PM – 5:00 PM",
    "Saturday – Sunday" => "Closed"
];

$services = [
    "Individual counselling",
    "Mental health and emotional support",
    "Academic stress management",
    "Personal development guidance",
    "Relationship and family support",
    "Crisis intervention",
    "Referral to appropriate professional services"
];

$helpSigns = [
    [
        "icon" => "😔",
        "title" => "Persistent sadness",
        "description" =>
            "You have been feeling sad, empty or emotionally exhausted for several days."
    ],
    [
        "icon" => "😟",
        "title" => "Anxiety affects daily life",
        "description" =>
            "Worry or fear is making it difficult to study, sleep or complete normal activities."
    ],
    [
        "icon" => "📚",
        "title" => "Academic pressure",
        "description" =>
            "Assignments, examinations or expectations feel too difficult to manage alone."
    ],
    [
        "icon" => "😴",
        "title" => "Changes in sleep",
        "description" =>
            "You are sleeping much more or less than usual and it affects your wellbeing."
    ],
    [
        "icon" => "🌧️",
        "title" => "Loss of motivation",
        "description" =>
            "You no longer feel interested in activities, studies or relationships."
    ],
    [
        "icon" => "🫶",
        "title" => "You need someone to listen",
        "description" =>
            "You do not need to wait for a crisis before speaking with a counsellor."
    ]
];

$selfCareTips = [
    [
        "icon" => "🌿",
        "title" => "Take regular breaks",
        "description" =>
            "Allow your mind and body to rest between study sessions."
    ],
    [
        "icon" => "💧",
        "title" => "Stay hydrated",
        "description" =>
            "Drink enough water throughout the day."
    ],
    [
        "icon" => "🚶",
        "title" => "Move your body",
        "description" =>
            "Take a short walk or perform gentle stretching."
    ],
    [
        "icon" => "😴",
        "title" => "Protect your sleep",
        "description" =>
            "Maintain a regular sleep schedule whenever possible."
    ],
    [
        "icon" => "👥",
        "title" => "Stay connected",
        "description" =>
            "Speak with friends, family members or someone you trust."
    ],
    [
        "icon" => "🧘",
        "title" => "Practise relaxation",
        "description" =>
            "Try breathing, grounding or mindfulness exercises."
    ]
];
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Help and Support | Poly-Health</title>

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
        href="../assets/css/style.css"
    >

</head>

<body class="dashboard-body">

<nav class="navbar navbar-expand-lg student-navbar">

    <div class="container">

        <a
            class="navbar-brand student-logo"
            href="dashboard.php"
        >
            POLY-HEALTH
        </a>

        <div class="ms-auto d-flex flex-wrap gap-2">

            <a
                href="dashboard.php"
                class="btn btn-outline-poly btn-sm rounded-pill px-3"
            >
                Dashboard
            </a>

            <a
                href="<?= escape($whatsappUrl); ?>"
                target="_blank"
                rel="noopener noreferrer"
                class="btn btn-poly btn-sm rounded-pill px-3"
            >
                WhatsApp UPPSIK
            </a>

        </div>

    </div>

</nav>

<main class="help-page">

    <div class="container">

        <?php display_flash_message(); ?>

        <!-- Hero section -->
        <section class="help-hero">

            <div class="help-hero-content">

                <span class="section-label">
                    Help and Student Support
                </span>

                <h1>
                    Need someone to talk to?
                </h1>

                <p>
                    Hello, <?= escape($studentName); ?>. You do not have
                    to manage difficult emotions alone. Confidential
                    counselling and support are available through UPPSIK.
                </p>

                <div class="help-hero-actions">

                    <a
                        href="<?= escape($whatsappUrl); ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="btn btn-poly rounded-pill px-4"
                    >
                        💬 Contact on WhatsApp
                    </a>

                    <a
                        href="tel:<?= escape($phoneLink); ?>"
                        class="btn btn-outline-poly rounded-pill px-4"
                    >
                        📞 Call UPPSIK
                    </a>

                </div>

            </div>

            <div class="help-hero-visual">

                <span>🤝</span>

                <strong>
                    You are not alone
                </strong>

                <small>
                    Reaching out is a positive step towards feeling better.
                </small>

            </div>

        </section>

        <!-- Emergency warning -->
        <section class="help-emergency-card">

            <div class="help-emergency-icon">
                ⚠️
            </div>

            <div class="help-emergency-content">

                <span class="help-emergency-label">
                    Immediate safety concern
                </span>

                <h2>
                    Get urgent help immediately
                </h2>

                <p>
                    If you believe that you may harm yourself or another
                    person, feel unsafe, or are experiencing a serious
                    emergency, do not wait for an online response.
                </p>

                <p>
                    Contact emergency services, go to the nearest hospital
                    emergency department, or tell a trusted person who can
                    remain with you.
                </p>

                <div class="help-emergency-actions">

                    <a
                        href="tel:999"
                        class="btn btn-danger rounded-pill px-4"
                    >
                        Call Emergency Services
                    </a>

                    <a
                        href="tel:<?= escape($phoneLink); ?>"
                        class="btn btn-outline-danger rounded-pill px-4"
                    >
                        Call UPPSIK
                    </a>

                </div>

            </div>

        </section>

        <!-- UPPSIK introduction -->
        <section class="uppsik-overview">

            <div class="uppsik-person-image">

                <img
                    src="../assets/images/uppsik-person.png"
                    alt="UPPSIK Counsellor"
                    >

            </div>

            <div class="uppsik-overview-content">

                <span class="section-label">
                    Counselling Services
                </span>

                <h2>
                    <?= escape($uppsikName); ?>
                </h2>

                <p>
                    UPPSIK provides a safe and supportive environment for
                    students to discuss emotional, academic, personal and
                    social concerns.
                </p>

                <p>
                    Counselling sessions are intended to help students
                    understand their challenges, identify healthy coping
                    strategies and make informed decisions.
                </p>

            </div>

            <div class="uppsik-services-card">

                <h3>Available services</h3>

                <ul>

                    <?php foreach ($services as $service): ?>

                        <li>
                            <span>✓</span>

                            <?= escape($service); ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        </section>

        <!-- Contact details -->
        <section class="help-contact-section">

            <div class="help-section-heading">

                <span class="section-label">
                    Contact Information
                </span>

                <h2>Connect with UPPSIK</h2>

                <p>
                    Use the contact method that is most convenient for you.
                </p>

            </div>

            <div class="help-contact-grid">

                <!-- Phone and WhatsApp -->
                <article class="help-contact-card">

                    <div class="help-contact-icon">
                        📞
                    </div>

                    <h3>Telephone and WhatsApp</h3>

                    <p class="help-contact-main">
                        <?= escape($phoneDisplay); ?>
                    </p>

                    <small>
                        Contact the counselling unit during office hours.
                    </small>

                    <div class="help-contact-buttons">

                        <a
                            href="tel:<?= escape($phoneLink); ?>"
                            class="btn btn-outline-poly rounded-pill"
                        >
                            📞 Call Now
                        </a>

                        <a
                            href="<?= escape($whatsappUrl); ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="btn help-whatsapp-btn rounded-pill"
                        >
                            💬 WhatsApp
                        </a>

                    </div>

                </article>

                <!-- Email -->
                <article class="help-contact-card">

                    <div class="help-contact-icon">
                        ✉️
                    </div>

                    <h3>Email</h3>

                    <p class="help-contact-main help-email-text">
                        <?= escape($email); ?>
                    </p>

                    <small>
                        Send an enquiry or request information about an
                        appointment.
                    </small>

                    <div class="help-contact-buttons">

                        <a
                            href="mailto:<?= escape($email); ?>?subject=Counselling%20Support%20Enquiry"
                            class="btn btn-outline-poly rounded-pill"
                        >
                            Send Email
                        </a>

                    </div>

                </article>

                <!-- Location -->
                <article class="help-contact-card">

                    <div class="help-contact-icon">
                        📍
                    </div>

                    <h3>Office Location</h3>

                    <p class="help-contact-main">
                        <?= escape($institutionName); ?>
                    </p>

                    <small>
                        <?= escape($officeLocation); ?>
                    </small>

                    <div class="help-contact-buttons">

                        <a
                            href="#officeInformation"
                            class="btn btn-outline-poly rounded-pill"
                        >
                            View Information
                        </a>

                    </div>

                </article>

                <!-- Office hours -->
                <article class="help-contact-card">

                    <div class="help-contact-icon">
                        🕒
                    </div>

                    <h3>Office Hours</h3>

                    <p class="help-contact-main">
                        Monday – Friday
                    </p>

                    <small>
                        Appointments may be required before visiting.
                    </small>

                    <div class="help-contact-buttons">

                        <a
                            href="#officeInformation"
                            class="btn btn-outline-poly rounded-pill"
                        >
                            View Schedule
                        </a>

                    </div>

                </article>

            </div>

        </section>

        <!-- Office information -->
        <section
            class="help-office-section"
            id="officeInformation"
        >

            <div class="help-office-card">

                <div class="help-office-heading">

                    <span class="help-office-icon">
                        🏢
                    </span>

                    <div>

                        <span class="section-label">
                            Visit UPPSIK
                        </span>

                        <h2>Office information</h2>

                    </div>

                </div>

                <div class="help-office-details">

                    <div class="help-office-item">

                        <small>Institution</small>

                        <strong>
                            <?= escape($institutionName); ?>
                        </strong>

                    </div>

                    <div class="help-office-item">

                        <small>Location</small>

                        <strong>
                            <?= escape($officeLocation); ?>
                        </strong>

                    </div>

                    <?php foreach ($officeHours as $day => $hours): ?>

                        <div class="help-office-item">

                            <small>
                                <?= escape($day); ?>
                            </small>

                            <strong>
                                <?= escape($hours); ?>
                            </strong>

                        </div>

                    <?php endforeach; ?>

                </div>

                <div class="help-office-note">

                    <strong>Before visiting</strong>

                    <p>
                        Contact UPPSIK first to confirm counsellor
                        availability and appointment procedures.
                    </p>

                </div>

            </div>

        </section>

        <!-- When to seek help -->
        <section class="help-signs-section">

            <div class="help-section-heading">

                <span class="section-label">
                    Recognising When Support May Help
                </span>

                <h2>When should you speak with a counsellor?</h2>

                <p>
                    You may contact UPPSIK even when you are unsure whether
                    your concern is serious enough.
                </p>

            </div>

            <div class="help-signs-grid">

                <?php foreach ($helpSigns as $sign): ?>

                    <article class="help-sign-card">

                        <span class="help-sign-icon">
                            <?= $sign["icon"]; ?>
                        </span>

                        <h3>
                            <?= escape($sign["title"]); ?>
                        </h3>

                        <p>
                            <?= escape($sign["description"]); ?>
                        </p>

                    </article>

                <?php endforeach; ?>

            </div>

        </section>

        <!-- Self-care section -->
        <section class="help-selfcare-section">

            <div class="help-section-heading">

                <span class="section-label">
                    Everyday Wellbeing
                </span>

                <h2>Small ways to care for yourself</h2>

                <p>
                    These suggestions can support general wellbeing but do
                    not replace professional care.
                </p>

            </div>

            <div class="help-selfcare-grid">

                <?php foreach ($selfCareTips as $tip): ?>

                    <article class="help-selfcare-card">

                        <span>
                            <?= $tip["icon"]; ?>
                        </span>

                        <div>

                            <h3>
                                <?= escape($tip["title"]); ?>
                            </h3>

                            <p>
                                <?= escape($tip["description"]); ?>
                            </p>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

            <div class="help-resource-actions">

                <a
                    href="therapy.php"
                    class="btn btn-outline-poly rounded-pill px-4"
                >
                    View Calming Exercises
                </a>

                <a
                    href="journal.php"
                    class="btn btn-outline-poly rounded-pill px-4"
                >
                    Open Private Journal
                </a>

                <a
                    href="screening.php"
                    class="btn btn-outline-poly rounded-pill px-4"
                >
                    Take DASS-21 Screening
                </a>

            </div>

        </section>

        <!-- Bottom CTA -->
        <section class="help-final-card">

            <div class="help-final-icon">
                💚
            </div>

            <div class="help-final-content">

                <span class="section-label">
                    Remember
                </span>

                <h2>
                    Asking for help is a sign of strength.
                </h2>

                <p>
                    You deserve support, understanding and a safe space to
                    talk about what you are experiencing.
                </p>

                <div class="help-final-actions">

                    <a
                        href="<?= escape($whatsappUrl); ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="btn btn-light rounded-pill px-4"
                    >
                        Contact UPPSIK
                    </a>

                    <a
                        href="dashboard.php"
                        class="btn btn-outline-light rounded-pill px-4"
                    >
                        Return to Dashboard
                    </a>

                </div>

            </div>

        </section>

        <p class="help-disclaimer">

            Poly-Health provides screening and general self-help
            information only. It does not provide a clinical diagnosis and
            is not a replacement for professional medical or psychological
            care.

        </p>

    </div>

</main>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<script src="../assets/js/script.js"></script>

</body>
</html>