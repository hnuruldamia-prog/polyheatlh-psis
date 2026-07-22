<?php

require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/functions.php";

require_student_login();

$studentName = $_SESSION["fullname"] ?? "Student";

$resources = [
    [
        "title" => "Deep Breathing Exercise",
        "category" => "Relaxation",
        "icon" => "🌬️",
        "duration" => "2–5 minutes",
        "description" =>
            "Use slow breathing to calm your body and reduce feelings of tension or anxiety.",
        "steps" => [
            "Sit comfortably and relax your shoulders.",
            "Breathe in slowly through your nose for four seconds.",
            "Hold your breath gently for four seconds.",
            "Breathe out slowly through your mouth for six seconds.",
            "Repeat the exercise five times."
        ]
    ],
    [
        "title" => "5-4-3-2-1 Grounding",
        "category" => "Anxiety Support",
        "icon" => "🖐️",
        "duration" => "3–5 minutes",
        "description" =>
            "A grounding exercise that helps bring your attention back to the present moment.",
        "steps" => [
            "Name five things that you can see.",
            "Name four things that you can touch.",
            "Name three things that you can hear.",
            "Name two things that you can smell.",
            "Name one thing that you can taste."
        ]
    ],
    [
        "title" => "Progressive Muscle Relaxation",
        "category" => "Stress Management",
        "icon" => "🧘",
        "duration" => "10 minutes",
        "description" =>
            "Reduce physical tension by slowly tightening and relaxing different muscle groups.",
        "steps" => [
            "Find a quiet and comfortable place.",
            "Tighten the muscles in your feet for five seconds.",
            "Release the muscles slowly and notice the difference.",
            "Continue with your legs, stomach, hands, arms and shoulders.",
            "Finish by taking three slow breaths."
        ]
    ],
    [
        "title" => "Thought Reframing",
        "category" => "Emotional Wellbeing",
        "icon" => "💭",
        "duration" => "5–10 minutes",
        "description" =>
            "Examine an upsetting thought and replace it with a more balanced and realistic view.",
        "steps" => [
            "Write down the thought that is troubling you.",
            "Ask yourself whether the thought is completely true.",
            "Identify evidence that supports and challenges the thought.",
            "Write a more balanced alternative thought.",
            "Notice whether your feelings change."
        ]
    ],
    [
        "title" => "Short Mindfulness Practice",
        "category" => "Mindfulness",
        "icon" => "🍃",
        "duration" => "5 minutes",
        "description" =>
            "Focus gently on the present moment without judging your thoughts or feelings.",
        "steps" => [
            "Sit comfortably and close your eyes if appropriate.",
            "Pay attention to your natural breathing.",
            "Notice thoughts without trying to stop them.",
            "Bring your attention back to your breathing.",
            "Continue for five minutes."
        ]
    ],
    [
        "title" => "Healthy Study Break",
        "category" => "Student Wellbeing",
        "icon" => "📚",
        "duration" => "10–15 minutes",
        "description" =>
            "Take a structured break to refresh your mind and avoid study exhaustion.",
        "steps" => [
            "Move away from your study area.",
            "Drink some water.",
            "Stretch or walk for several minutes.",
            "Avoid checking stressful messages or assignments.",
            "Return with one clear and manageable study goal."
        ]
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

    <title>Therapy Resources | Poly-Health</title>

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

        <div class="ms-auto d-flex gap-2">

            <a
                href="dashboard.php"
                class="btn btn-outline-poly btn-sm rounded-pill px-3"
            >
                Dashboard
            </a>

            <a
                href="help.php"
                class="btn btn-poly btn-sm rounded-pill px-3"
            >
                Get Support
            </a>

        </div>

    </div>

</nav>

<main class="therapy-page">

    <div class="container">

        <?php display_flash_message(); ?>

        <section class="therapy-hero">

            <div class="therapy-hero-content">

                <span class="section-label">
                    Self-Help and Wellbeing
                </span>

                <h1>
                    Take a moment for yourself
                </h1>

                <p>
                    Hello, <?= escape($studentName); ?>. Explore simple
                    exercises that may help you manage stress, anxiety and
                    difficult emotions.
                </p>

                <div class="therapy-hero-actions">

                    <a
                        href="#therapyResources"
                        class="btn btn-poly rounded-pill px-4"
                    >
                        Explore Resources
                    </a>

                    <a
                        href="screening.php"
                        class="btn btn-outline-poly rounded-pill px-4"
                    >
                        Take DASS-21
                    </a>

                </div>

            </div>

            <div class="therapy-hero-visual">

                <span>🌿</span>

                <strong>
                    Pause. Breathe. Reset.
                </strong>

                <small>
                    Small steps can support your wellbeing.
                </small>

            </div>

        </section>

        <section class="therapy-reminder">

            <span class="therapy-reminder-icon">
                💚
            </span>

            <div>

                <strong>
                    These activities are for general self-help
                </strong>

                <p>
                    They do not replace professional counselling or
                    medical treatment. Contact UPPSIK when additional
                    support is needed.
                </p>

            </div>

        </section>

        <section
            class="therapy-resources-section"
            id="therapyResources"
        >

            <div class="therapy-section-heading">

                <span class="section-label">
                    Guided Activities
                </span>

                <h2>Choose an exercise</h2>

                <p>
                    Select any activity and follow the steps at your own
                    pace.
                </p>

            </div>

            <div class="therapy-grid">

                <?php foreach ($resources as $index => $resource): ?>

                    <article class="therapy-card">

                        <div class="therapy-card-heading">

                            <span class="therapy-card-icon">
                                <?= $resource["icon"]; ?>
                            </span>

                            <div>

                                <small>
                                    <?= escape(
                                        $resource["category"]
                                    ); ?>
                                </small>

                                <h3>
                                    <?= escape(
                                        $resource["title"]
                                    ); ?>
                                </h3>

                            </div>

                        </div>

                        <p>
                            <?= escape(
                                $resource["description"]
                            ); ?>
                        </p>

                        <div class="therapy-card-duration">

                            <span>⏱️</span>

                            <small>
                                <?= escape(
                                    $resource["duration"]
                                ); ?>
                            </small>

                        </div>

                        <button
                            type="button"
                            class="btn btn-outline-poly w-100"
                            data-bs-toggle="modal"
                            data-bs-target="#therapyModal<?= $index; ?>"
                        >
                            View Exercise
                        </button>

                    </article>

                    <div
                        class="modal fade"
                        id="therapyModal<?= $index; ?>"
                        tabindex="-1"
                        aria-labelledby="therapyModalLabel<?= $index; ?>"
                        aria-hidden="true"
                    >

                        <div
                            class="modal-dialog modal-dialog-centered modal-lg"
                        >

                            <div class="modal-content therapy-modal">

                                <div class="modal-header">

                                    <div class="therapy-modal-heading">

                                        <span>
                                            <?= $resource["icon"]; ?>
                                        </span>

                                        <div>

                                            <small>
                                                <?= escape(
                                                    $resource["category"]
                                                ); ?>
                                            </small>

                                            <h2
                                                class="modal-title"
                                                id="therapyModalLabel<?= $index; ?>"
                                            >
                                                <?= escape(
                                                    $resource["title"]
                                                ); ?>
                                            </h2>

                                        </div>

                                    </div>

                                    <button
                                        type="button"
                                        class="btn-close"
                                        data-bs-dismiss="modal"
                                        aria-label="Close"
                                    ></button>

                                </div>

                                <div class="modal-body">

                                    <p class="therapy-modal-description">
                                        <?= escape(
                                            $resource["description"]
                                        ); ?>
                                    </p>

                                    <div class="therapy-modal-time">

                                        <span>⏱️</span>

                                        Estimated time:
                                        <strong>
                                            <?= escape(
                                                $resource["duration"]
                                            ); ?>
                                        </strong>

                                    </div>

                                    <h3>Follow these steps</h3>

                                    <ol class="therapy-step-list">

                                        <?php foreach (
                                            $resource["steps"]
                                            as $step
                                        ): ?>

                                            <li>
                                                <span>
                                                    <?= escape($step); ?>
                                                </span>
                                            </li>

                                        <?php endforeach; ?>

                                    </ol>

                                    <div class="therapy-modal-notice">

                                        <strong>
                                            Be gentle with yourself
                                        </strong>

                                        <p>
                                            Stop the exercise if it makes
                                            you uncomfortable. You may
                                            return to it later or seek
                                            support from a counsellor.
                                        </p>

                                    </div>

                                </div>

                                <div class="modal-footer">

                                    <button
                                        type="button"
                                        class="btn btn-poly rounded-pill px-4"
                                        data-bs-dismiss="modal"
                                    >
                                        Complete
                                    </button>

                                </div>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </section>

        <section class="therapy-support-card">

            <div class="therapy-support-icon">
                🤝
            </div>

            <div>

                <span class="section-label">
                    Need additional support?
                </span>

                <h2>Talk to someone you trust</h2>

                <p>
                    UPPSIK counsellors can provide confidential support
                    when stress, anxiety or difficult emotions affect your
                    studies or daily life.
                </p>

                <a
                    href="help.php"
                    class="btn btn-light rounded-pill px-4"
                >
                    View Support Information
                </a>

            </div>

        </section>

    </div>

</main>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<script src="../assets/js/script.js"></script>

</body>
</html>