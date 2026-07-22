<?php

require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/functions.php";

require_student_login();

$studentId = (int) $_SESSION["student_id"];

$resultId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$resultId || $resultId < 1) {
    set_flash_message(
        "danger",
        "The requested screening result is invalid."
    );

    header("Location: dashboard.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Retrieve the result
|--------------------------------------------------------------------------
|
| The student_id condition prevents one student from opening another
| student's screening result by changing the URL.
|
*/

$resultStatement = $conn->prepare(
    "SELECT
        result_id,
        depression_raw,
        anxiety_raw,
        stress_raw,
        depression_score,
        anxiety_score,
        stress_score,
        depression_level,
        anxiety_level,
        stress_level,
        requires_attention,
        screening_date
     FROM dass_results
     WHERE result_id = ?
     AND student_id = ?
     LIMIT 1"
);

if (!$resultStatement) {
    die("Unable to load the screening result.");
}

$resultStatement->bind_param(
    "ii",
    $resultId,
    $studentId
);

$resultStatement->execute();

$resultData = $resultStatement
    ->get_result()
    ->fetch_assoc();

$resultStatement->close();

if (!$resultData) {
    set_flash_message(
        "danger",
        "The screening result was not found."
    );

    header("Location: dashboard.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Check the answer to Question 21
|--------------------------------------------------------------------------
*/

$question21Statement = $conn->prepare(
    "SELECT
        da.answer_value
     FROM dass_answers AS da
     INNER JOIN dass_questions AS dq
        ON dq.question_id = da.question_id
     WHERE da.result_id = ?
     AND dq.question_number = 21
     LIMIT 1"
);

$question21Answer = 0;

if ($question21Statement) {

    $question21Statement->bind_param(
        "i",
        $resultId
    );

    $question21Statement->execute();

    $question21Data = $question21Statement
        ->get_result()
        ->fetch_assoc();

    if ($question21Data) {
        $question21Answer =
            (int) $question21Data["answer_value"];
    }

    $question21Statement->close();
}

$requiresAttention =
    (int) $resultData["requires_attention"] === 1;

$showImmediateSupport =
    $question21Answer > 0;

/*
|--------------------------------------------------------------------------
| Display helpers
|--------------------------------------------------------------------------
*/

function result_level_class(string $level): string
{
    switch ($level) {
        case "Normal":
            return "level-normal";

        case "Ringan":
            return "level-mild";

        case "Sederhana":
            return "level-moderate";

        case "Teruk":
            return "level-severe";

        case "Sangat Teruk":
            return "level-extreme";

        default:
            return "level-unknown";
    }
}

function dass_recommendation(
    string $category,
    string $level
): string {
    if ($level === "Normal") {

        switch ($category) {
            case "Depression":
                return "Teruskan rutin penjagaan diri, tidur yang mencukupi, aktiviti fizikal, dan hubungan sosial yang sihat.";

            case "Anxiety":
                return "Teruskan teknik pernafasan, rehat yang mencukupi, dan rutin harian yang seimbang.";

            case "Stress":
                return "Kekalkan pengurusan masa yang baik, masa rehat, dan aktiviti yang membantu anda bertenang.";

            default:
                return "Teruskan amalan penjagaan diri yang sihat.";
        }
    }

    if ($level === "Ringan") {

        switch ($category) {
            case "Depression":
                return "Pantau perubahan emosi anda. Pertimbangkan jurnal, aktiviti sosial, tidur teratur, dan berbincang dengan seseorang yang dipercayai.";

            case "Anxiety":
                return "Cuba latihan pernafasan perlahan, kurangkan kafein, berehat, dan kenal pasti perkara yang mencetuskan kebimbangan.";

            case "Stress":
                return "Susun tugas mengikut keutamaan, ambil rehat pendek, dan cuba aktiviti relaksasi.";

            default:
                return "Pantau keadaan anda dan gunakan sumber sokongan yang tersedia.";
        }
    }

    if ($level === "Sederhana") {

        switch ($category) {
            case "Depression":
                return "Pertimbangkan untuk berbincang dengan kaunselor UPPSIK bagi mendapatkan sokongan dan penilaian lanjut.";

            case "Anxiety":
                return "Dapatkan sokongan daripada kaunselor UPPSIK, terutama jika kebimbangan mengganggu pembelajaran atau aktiviti harian.";

            case "Stress":
                return "Berbincang dengan kaunselor UPPSIK untuk mengenal pasti punca tekanan dan membina strategi pengurusan yang sesuai.";

            default:
                return "Pertimbangkan untuk mendapatkan bantuan daripada kaunselor.";
        }
    }

    switch ($category) {
        case "Depression":
            return "Keputusan ini menunjukkan tahap simptom yang tinggi. Sila hubungi kaunselor UPPSIK secepat mungkin untuk sokongan dan penilaian lanjut.";

        case "Anxiety":
            return "Keputusan ini menunjukkan tahap kebimbangan yang tinggi. Sila hubungi kaunselor UPPSIK secepat mungkin.";

        case "Stress":
            return "Keputusan ini menunjukkan tahap tekanan yang tinggi. Sila dapatkan sokongan daripada kaunselor UPPSIK dengan segera.";

        default:
            return "Sila dapatkan sokongan daripada kaunselor UPPSIK secepat mungkin.";
    }
}
$categories = [
    [
        "name" => "Depression",
        "malay_name" => "Kemurungan",
        "icon" => "🌧️",
        "score" => (int) $resultData["depression_score"],
        "raw" => (int) $resultData["depression_raw"],
        "level" => $resultData["depression_level"]
    ],
    [
        "name" => "Anxiety",
        "malay_name" => "Kebimbangan",
        "icon" => "💭",
        "score" => (int) $resultData["anxiety_score"],
        "raw" => (int) $resultData["anxiety_raw"],
        "level" => $resultData["anxiety_level"]
    ],
    [
        "name" => "Stress",
        "malay_name" => "Stres",
        "icon" => "⚡",
        "score" => (int) $resultData["stress_score"],
        "raw" => (int) $resultData["stress_raw"],
        "level" => $resultData["stress_level"]
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

    <meta
        name="description"
        content="Poly-Health DASS-21 screening result"
    >

    <title>DASS-21 Result | Poly-Health</title>

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
                href="screening.php"
                class="btn btn-outline-poly btn-sm"
            >
                New Screening
            </a>

            <a
                href="dashboard.php"
                class="btn btn-poly btn-sm"
            >
                Dashboard
            </a>

        </div>

    </div>

</nav>

<main class="result-page">

    <div class="container">

        <?php if ($showImmediateSupport): ?>

            <section
                class="immediate-support-alert"
                role="alert"
            >

                <div class="support-alert-icon">
                    🤝
                </div>

                <div class="support-alert-content">

                    <span>
                        Support is available
                    </span>

                    <h2>
                        Please speak with someone you trust or contact
                        UPPSIK today.
                    </h2>

                    <p>
                        One of your answers suggests that you may be
                        experiencing significant emotional difficulty.
                        You do not have to manage this alone.
                    </p>

                    <div class="support-alert-actions">

                        <a
                            href="help.php"
                            class="btn btn-light rounded-pill px-4"
                        >
                            View Support Options
                        </a>

                        <a
                            href="#uppsik-contact"
                            class="btn btn-outline-light rounded-pill px-4"
                        >
                            Contact UPPSIK
                        </a>

                    </div>

                </div>

            </section>

        <?php elseif ($requiresAttention): ?>

            <section
                class="attention-result-alert"
                role="alert"
            >

                <span class="attention-icon">
                    💚
                </span>

                <div>

                    <h2>
                        We recommend speaking with a counsellor.
                    </h2>

                    <p>
                        One or more scores are within a high range.
                        Consider contacting UPPSIK for confidential support
                        and further assessment.
                    </p>

                </div>

            </section>

        <?php endif; ?>

        <section class="result-header">

            <div>

                <span class="section-label">
                    DASS-21 Screening Result
                </span>

                <h1>Your wellbeing result</h1>

                <p>
                    This result reflects your answers about experiences
                    during the past week.
                </p>

            </div>

            <div class="result-date-card">

                <small>Completed</small>

                <strong>
                    <?= date(
                        "d F Y",
                        strtotime($resultData["screening_date"])
                    ); ?>
                </strong>

                <span>
                    <?= date(
                        "h:i A",
                        strtotime($resultData["screening_date"])
                    ); ?>
                </span>

            </div>

        </section>

        <section class="result-summary-grid">

            <?php foreach ($categories as $category): ?>

                <?php
                $levelClass =
                    result_level_class($category["level"]);
                ?>

                <article
                    class="result-score-card
                    <?= escape($levelClass); ?>"
                >

                    <div class="result-card-heading">

                        <span class="result-category-icon">
                            <?= $category["icon"]; ?>
                        </span>

                        <div>

                            <small>
                                <?= escape(
                                    $category["malay_name"]
                                ); ?>
                            </small>

                            <h2>
                                <?= escape($category["name"]); ?>
                            </h2>

                        </div>

                    </div>

                    <div class="result-score-display">

                        <strong>
                            <?= $category["score"]; ?>
                        </strong>

                        <span>
                            Final score
                        </span>

                    </div>

                    <div class="result-level-row">

                        <span>Severity</span>

                        <strong>
                            <?= escape($category["level"]); ?>
                        </strong>

                    </div>

                    <div class="result-raw-score">

                        Raw score:
                        <?= $category["raw"]; ?>
                        × 2 =
                        <?= $category["score"]; ?>

                    </div>

                </article>

            <?php endforeach; ?>

        </section>

        <section class="result-explanation">

            <div class="result-section-heading">

                <span class="section-label">
                    Personal Recommendations
                </span>

                <h2>Recommended next steps</h2>

                <p>
                    These suggestions are general guidance based on your
                    screening levels.
                </p>

            </div>

            <div class="recommendation-list">

                <?php foreach ($categories as $category): ?>

                    <article class="recommendation-card">

                        <div class="recommendation-heading">

                            <span>
                                <?= $category["icon"]; ?>
                            </span>

                            <div>

                                <h3>
                                    <?= escape(
                                        $category["malay_name"]
                                    ); ?>
                                </h3>

                                <small>
                                    Level:
                                    <?= escape(
                                        $category["level"]
                                    ); ?>
                                </small>

                            </div>

                        </div>

                        <p>
                            <?= escape(
                                dass_recommendation(
                                    $category["name"],
                                    $category["level"]
                                )
                            ); ?>
                        </p>

                    </article>

                <?php endforeach; ?>

            </div>

        </section>

        <section class="result-scale-guide">

            <div class="result-section-heading">

                <span class="section-label">
                    Score Guide
                </span>

                <h2>DASS-21 severity ranges</h2>

            </div>

            <div class="table-responsive">

                <table class="table result-guide-table">

                    <thead>

                        <tr>
                            <th>Level</th>
                            <th>Depression</th>
                            <th>Anxiety</th>
                            <th>Stress</th>
                        </tr>

                    </thead>

                    <tbody>

                        <tr>
                            <td>
                                <span class="guide-badge level-normal">
                                    Normal
                                </span>
                            </td>
                            <td>0–9</td>
                            <td>0–7</td>
                            <td>0–14</td>
                        </tr>

                        <tr>
                            <td>
                                <span class="guide-badge level-mild">
                                    Ringan
                                </span>
                            </td>
                            <td>10–13</td>
                            <td>8–9</td>
                            <td>15–18</td>
                        </tr>

                        <tr>
                            <td>
                                <span class="guide-badge level-moderate">
                                    Sederhana
                                </span>
                            </td>
                            <td>14–20</td>
                            <td>10–14</td>
                            <td>19–25</td>
                        </tr>

                        <tr>
                            <td>
                                <span class="guide-badge level-severe">
                                    Teruk
                                </span>
                            </td>
                            <td>21–27</td>
                            <td>15–19</td>
                            <td>26–33</td>
                        </tr>

                        <tr>
                            <td>
                                <span class="guide-badge level-extreme">
                                    Sangat Teruk
                                </span>
                            </td>
                            <td>28+</td>
                            <td>20+</td>
                            <td>34+</td>
                        </tr>

                    </tbody>

                </table>

            </div>

        </section>

        <section
            class="uppsik-support-card"
            id="uppsik-contact"
        >

            <div class="uppsik-support-icon">
                🤝
            </div>

            <div class="uppsik-support-content">

                <span class="section-label">
                    UPPSIK Student Support
                </span>

                <h2>You are not alone.</h2>

                <p>
                    Contact the UPPSIK counselling unit if you would like
                    to discuss your result or receive confidential support.
                </p>

                <div class="uppsik-contact-placeholder">

                    <div>

                        <small>Telephone</small>

                        <strong>
                            +60 17-666 9724
                        </strong>

                    </div>

                    <div>

                        <small>Email</small>

                        <strong>
                            noraisyah.ismail@psis.edu.my
                        </strong>

                    </div>

                    <div>

                        <small>Location</small>

                        <strong>
                            Pejabat UPPSIK, JHEP, Politeknik Sultan Idris Shah
                        </strong>

                    </div>

                </div>

                <a
                    href="help.php"
                    class="btn btn-poly"
                >
                    Open Support Page
                </a>

            </div>

        </section>

        <section class="screening-disclaimer">

            <strong>Important notice</strong>

            <p>
                This DASS-21 result is intended for initial screening and
                educational support only. It does not provide a medical
                diagnosis and does not replace an assessment by a
                qualified mental-health professional.
            </p>

        </section>

        <div class="result-bottom-actions">

    <a
        href="dashboard.php"
        class="btn btn-outline-poly"
    >
        Return to Dashboard
    </a>

    <a
        href="screening_history.php"
        class="btn btn-outline-poly"
    >
        View Screening History
    </a>

    <a
        href="screening.php"
        class="btn btn-poly"
    >
        Complete Another Screening
    </a>

</div>

    </div>

</main>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<script src="../assets/js/script.js"></script>

</body>
</html>