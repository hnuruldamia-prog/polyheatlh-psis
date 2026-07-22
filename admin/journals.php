<?php

session_start();

require_once __DIR__ . "/../includes/config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$adminName = $_SESSION["admin_fullname"] ?? "Administrator";

function admin_escape($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}
if (empty($_SESSION["journal_csrf"])) {
    $_SESSION["journal_csrf"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["journal_csrf"];

$successMessage = $_SESSION["success"] ?? "";
$errorMessage = $_SESSION["error"] ?? "";

unset(
    $_SESSION["success"],
    $_SESSION["error"]
);

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["delete_journal"])
) {

    if (
        !hash_equals(
            $csrfToken,
            $_POST["csrf_token"] ?? ""
        )
    ) {

        $_SESSION["error"] =
            "Invalid request.";

        header("Location: journals.php");
        exit;
    }

    $journalId = (int)$_POST["journal_id"];

    $delete = $conn->prepare(
        "DELETE FROM journals
         WHERE journal_id=?"
    );

    $delete->bind_param(
        "i",
        $journalId
    );

    if ($delete->execute()) {

        $_SESSION["success"] =
            "Journal deleted successfully.";

    } else {

        $_SESSION["error"] =
            "Unable to delete journal.";

    }

    header("Location: journals.php");
    exit;
}

$search = trim($_GET["search"] ?? "");
$date = trim($_GET["date"] ?? "");

$perPage = 10;

$page = max(
    1,
    (int)($_GET["page"] ?? 1)
);

$offset =
    ($page - 1) * $perPage;

    $where = [];
$params = [];
$types = "";

if ($search !== "") {

    $where[] = "
        (
            students.fullname LIKE ?
            OR journals.title LIKE ?
        )
    ";

    $like =
        "%" . $search . "%";

    $params[] = $like;
    $params[] = $like;

    $types .= "ss";
}

if ($date !== "") {

    $where[] =
        "DATE(journals.created_at)=?";

    $params[] = $date;
    $types .= "s";
}

$whereSQL = "";

if (!empty($where)) {

    $whereSQL =
        "WHERE " .
        implode(" AND ", $where);
}
$countSQL = "

SELECT COUNT(*) total

FROM journals

JOIN students

ON journals.student_id =
students.student_id

$whereSQL

";

$stmt =
$conn->prepare($countSQL);

if (!empty($params)) {

    $stmt->bind_param(
        $types,
        ...$params
    );

}

$stmt->execute();

$totalRows =
$stmt
->get_result()
->fetch_assoc()["total"];

$stmt->close();

$totalPages =
max(
1,
ceil(
$totalRows /
$perPage
)
);

$sql = "

SELECT

journals.*,

students.fullname,

students.student_number

FROM journals

JOIN students

ON journals.student_id =
students.student_id

$whereSQL

ORDER BY
journals.created_at DESC

LIMIT ?

OFFSET ?

";

$stmt =
$conn->prepare($sql);

$bind =
$params;

$bind[] =
$perPage;

$bind[] =
$offset;

$stmt->bind_param(

$types . "ii",

...$bind

);

$stmt->execute();

$result =
$stmt->get_result();

$journals = [];

while (
$row =
$result->fetch_assoc()
){

$journals[] = $row;

}

$stmt->close();

function cardCount(
$conn,
$sql
){

$result =
$conn->query($sql);

$row =
$result->fetch_assoc();

return
(int)$row["total"];

}

$totalJournal =
cardCount(

$conn,

"SELECT COUNT(*) total
FROM journals"

);

$totalStudents =
cardCount(

$conn,

"SELECT COUNT(DISTINCT student_id)
total
FROM journals"

);

$todayJournal =
cardCount(

$conn,

"SELECT COUNT(*) total

FROM journals

WHERE DATE(created_at)=CURDATE()"

);

$thisMonth =
cardCount(

$conn,

"SELECT COUNT(*) total

FROM journals

WHERE MONTH(created_at)=MONTH(CURDATE())

AND YEAR(created_at)=YEAR(CURDATE())"

);

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Journal Entries | Poly-Health</title>

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

<body class="admin-dashboard-body">

<div class="admin-dashboard-layout">

    <!-- Sidebar -->
    <aside
        class="admin-sidebar"
        id="adminSidebar"
    >

        <div class="admin-sidebar-header">

            <a
                href="dashboard.php"
                class="admin-sidebar-logo"
            >
                <span class="admin-sidebar-logo-icon">
                    +
                </span>

                <span>POLY-HEALTH</span>
            </a>

            <button
                type="button"
                class="admin-sidebar-close"
                id="closeAdminSidebar"
                aria-label="Close sidebar"
            >
                ×
            </button>

        </div>

        <div class="admin-sidebar-profile">

            <div class="admin-sidebar-avatar">

                <?= admin_escape(
                    strtoupper(
                        substr(
                            trim($adminName),
                            0,
                            1
                        )
                    )
                ); ?>

            </div>

            <div>

                <strong>
                    <?= admin_escape($adminName); ?>
                </strong>

                <small>
                    System Administrator
                </small>

            </div>

        </div>

        <nav class="admin-sidebar-nav">

            <span class="admin-nav-label">
                Main Menu
            </span>

            <a
                href="dashboard.php"
                class="admin-nav-link"
            >
                <span>🏠</span>
                Dashboard
            </a>

            <a
                href="students.php"
                class="admin-nav-link"
            >
                <span>🎓</span>
                Manage Students
            </a>

            <a
                href="screening_results.php"
                class="admin-nav-link"
            >
                <span>📊</span>
                DASS-21 Results
            </a>

            <a
                href="journals.php"
                class="admin-nav-link active"
            >
                <span>📖</span>
                Journal Entries
            </a>

            <a
                href="reports.php"
                class="admin-nav-link"
            >
                <span>📈</span>
                Reports
            </a>

            <span class="admin-nav-label admin-nav-label-second">
                Account
            </span>

            <a
    href="profile.php"
    class="admin-nav-link"
>
    <span>👤</span>
    My Profile
</a>

            <a
                href="logout.php"
                class="admin-nav-link admin-logout-link"
            >
                <span>🚪</span>
                Logout
            </a>

        </nav>

        <div class="admin-sidebar-footer">

            <span>🔒</span>

            <p>
                Journal entries are private and must be handled
                confidentially.
            </p>

        </div>

    </aside>

    <div
        class="admin-sidebar-overlay"
        id="adminSidebarOverlay"
    ></div>

    <!-- Main content -->
    <div class="admin-dashboard-main">

        <header class="admin-topbar">

            <button
                type="button"
                class="admin-menu-button"
                id="openAdminSidebar"
                aria-label="Open sidebar"
            >
                ☰
            </button>

            <div class="admin-topbar-title">

                <span>Administrator Portal</span>

                <strong>
                    Journal Management
                </strong>

            </div>

            <div class="admin-topbar-account">

                <div class="admin-topbar-avatar">

                    <?= admin_escape(
                        strtoupper(
                            substr(
                                trim($adminName),
                                0,
                                1
                            )
                        )
                    ); ?>

                </div>

                <div class="admin-topbar-user">

                    <strong>
                        <?= admin_escape($adminName); ?>
                    </strong>

                    <small>
                        Administrator
                    </small>

                </div>

                <a
                    href="logout.php"
                    class="btn admin-topbar-logout"
                >
                    Logout
                </a>

            </div>

        </header>

        <main class="admin-dashboard-content">

            <!-- Page heading -->
            <section class="admin-page-header">

                <div>

                    <span class="admin-section-label">
                        Student Wellbeing Records
                    </span>

                    <h1>Journal Entries</h1>

                    <p>
                        Review and manage journal entries created by
                        registered students.
                    </p>

                </div>

                <a
                    href="dashboard.php"
                    class="btn admin-secondary-button"
                >
                    ← Dashboard
                </a>

            </section>

            <!-- Flash message -->
            <?php if ($successMessage !== ""): ?>

                <div
                    class="alert alert-success admin-page-alert"
                    role="alert"
                >
                    <?= admin_escape($successMessage); ?>
                </div>

            <?php endif; ?>

            <?php if ($errorMessage !== ""): ?>

                <div
                    class="alert alert-danger admin-page-alert"
                    role="alert"
                >
                    <?= admin_escape($errorMessage); ?>
                </div>

            <?php endif; ?>

            <!-- Summary cards -->
            <section class="admin-student-stat-grid">

                <article class="admin-student-stat-card">

                    <span>📖</span>

                    <div>

                        <small>
                            Total Journal Entries
                        </small>

                        <strong>
                            <?= number_format($totalJournal); ?>
                        </strong>

                    </div>

                </article>

                <article class="admin-student-stat-card">

                    <span>🎓</span>

                    <div>

                        <small>
                            Students Using Journal
                        </small>

                        <strong>
                            <?= number_format($totalStudents); ?>
                        </strong>

                    </div>

                </article>

                <article class="admin-student-stat-card">

                    <span>📝</span>

                    <div>

                        <small>
                            Entries Today
                        </small>

                        <strong>
                            <?= number_format($todayJournal); ?>
                        </strong>

                    </div>

                </article>

                <article class="admin-student-stat-card">

                    <span>📅</span>

                    <div>

                        <small>
                            Entries This Month
                        </small>

                        <strong>
                            <?= number_format($thisMonth); ?>
                        </strong>

                    </div>

                </article>

            </section>

            <!-- Confidentiality notice -->
            <section class="admin-journal-privacy-card">

                <div class="admin-journal-privacy-icon">
                    🔒
                </div>

                <div>

                    <strong>
                        Confidential Student Information
                    </strong>

                    <p>
                        Journal entries may contain sensitive personal
                        information. Access and use these records only
                        for authorised student support purposes.
                    </p>

                </div>

            </section>

            <!-- Filters -->
            <section class="admin-filter-card">

                <form
                    method="GET"
                    action="journals.php"
                    class="admin-journal-filter-form"
                >

                    <div class="admin-journal-search">

                        <label for="search">
                            Search journal
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="search"
                            name="search"
                            value="<?= admin_escape($search); ?>"
                            placeholder="Student name or journal title"
                        >

                    </div>

                    <div>

                        <label for="date">
                            Entry date
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            id="date"
                            name="date"
                            value="<?= admin_escape($date); ?>"
                        >

                    </div>

                    <div class="admin-filter-actions">

                        <button
                            type="submit"
                            class="btn btn-poly"
                        >
                            Apply Filters
                        </button>

                        <a
                            href="journals.php"
                            class="btn admin-filter-reset"
                        >
                            Reset
                        </a>

                    </div>

                </form>

            </section>

            <!-- Journal table -->
            <section class="admin-dashboard-section">

                <div class="admin-dashboard-heading">

                    <div>

                        <span class="admin-section-label">
                            Journal Directory
                        </span>

                        <h2>
                            Student Journal Records
                        </h2>

                    </div>

                    <span class="admin-record-count">

                        <?= number_format($totalRows); ?>

                        entr<?= (int)$totalRows === 1
                            ? "y"
                            : "ies"; ?>

                    </span>

                </div>

                <div class="admin-table-card">

                    <?php if (!empty($journals)): ?>

                        <div class="table-responsive">

                            <table class="table admin-journals-table">

                                <thead>

                                    <tr>

                                        <th>Student</th>
                                        <th>Journal Title</th>
                                        <th>Entry Preview</th>
                                        <th>Date Created</th>
                                        <th>Actions</th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <?php foreach ($journals as $journal): ?>

                                        <?php

                                        $journalContent =
                                            $journal["content"] ?? "";

                                        $plainContent = trim(
                                            strip_tags($journalContent)
                                        );

                                        $preview = mb_strlen(
                                            $plainContent
                                        ) > 120
                                            ? mb_substr(
                                                $plainContent,
                                                0,
                                                120
                                            ) . "..."
                                            : $plainContent;

                                        ?>

                                        <tr>

                                            <td>

                                                <div class="admin-table-student">

                                                    <div class="admin-table-avatar">

                                                        <?= admin_escape(
                                                            strtoupper(
                                                                substr(
                                                                    trim(
                                                                        $journal[
                                                                            "fullname"
                                                                        ]
                                                                    ),
                                                                    0,
                                                                    1
                                                                )
                                                            )
                                                        ); ?>

                                                    </div>

                                                    <div>

                                                        <strong>
                                                            <?= admin_escape(
                                                                $journal[
                                                                    "fullname"
                                                                ]
                                                            ); ?>
                                                        </strong>

                                                        <small>
                                                            <?= admin_escape(
                                                                $journal[
                                                                    "student_number"
                                                                ]
                                                            ); ?>
                                                        </small>

                                                    </div>

                                                </div>

                                            </td>

                                            <td>

                                                <div class="admin-journal-title-cell">

                                                    <strong>
                                                        <?= admin_escape(
                                                            $journal[
                                                                "title"
                                                            ] ?? "Untitled Entry"
                                                        ); ?>
                                                    </strong>

                                                    <small>
                                                        Journal ID:
                                                        <?= (int)$journal[
                                                            "journal_id"
                                                        ]; ?>
                                                    </small>

                                                </div>

                                            </td>

                                            <td>

                                                <p class="admin-journal-preview">

                                                    <?= $preview !== ""
                                                        ? admin_escape(
                                                            $preview
                                                        )
                                                        : "No journal content."; ?>

                                                </p>

                                            </td>

                                            <td>

                                                <span class="admin-table-date">

                                                    <?= date(
                                                        "d M Y",
                                                        strtotime(
                                                            $journal[
                                                                "created_at"
                                                            ]
                                                        )
                                                    ); ?>

                                                    <small>

                                                        <?= date(
                                                            "h:i A",
                                                            strtotime(
                                                                $journal[
                                                                    "created_at"
                                                                ]
                                                            )
                                                        ); ?>

                                                    </small>

                                                </span>

                                            </td>

                                            <td>

                                                <div class="admin-table-actions">

                                                    <button
                                                        type="button"
                                                        class="admin-action-view"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#viewJournalModal"
                                                        data-journal-title="<?= admin_escape(
                                                            $journal[
                                                                "title"
                                                            ] ?? "Untitled Entry"
                                                        ); ?>"
                                                        data-journal-student="<?= admin_escape(
                                                            $journal[
                                                                "fullname"
                                                            ]
                                                        ); ?>"
                                                        data-journal-number="<?= admin_escape(
                                                            $journal[
                                                                "student_number"
                                                            ]
                                                        ); ?>"
                                                        data-journal-date="<?= admin_escape(
                                                            date(
                                                                "d F Y, h:i A",
                                                                strtotime(
                                                                    $journal[
                                                                        "created_at"
                                                                    ]
                                                                )
                                                            )
                                                        ); ?>"
                                                        data-journal-content="<?= admin_escape(
                                                            $plainContent
                                                        ); ?>"
                                                    >
                                                        View
                                                    </button>

                                                    <a
                                                        href="student_details.php?id=<?= (int)$journal[
                                                            "student_id"
                                                        ]; ?>"
                                                        class="admin-action-student"
                                                    >
                                                        Student
                                                    </a>

                                                    <button
                                                        type="button"
                                                        class="admin-action-delete"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#deleteJournalModal"
                                                        data-journal-id="<?= (int)$journal[
                                                            "journal_id"
                                                        ]; ?>"
                                                        data-journal-title="<?= admin_escape(
                                                            $journal[
                                                                "title"
                                                            ] ?? "Untitled Entry"
                                                        ); ?>"
                                                    >
                                                        Delete
                                                    </button>

                                                </div>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php else: ?>

                        <div class="admin-empty-state">

                            <span>📖</span>

                            <h3>No journal entries found</h3>

                            <p>
                                Try changing the search keyword or
                                clearing the selected date filter.
                            </p>

                            <a
                                href="journals.php"
                                class="btn btn-poly mt-3"
                            >
                                Clear Filters
                            </a>

                        </div>

                    <?php endif; ?>

                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>

                    <nav class="admin-pagination-wrapper">

                        <ul class="pagination admin-pagination">

                            <?php

                            $queryParameters = $_GET;

                            $previousPage = max(
                                1,
                                $page - 1
                            );

                            $queryParameters["page"] =
                                $previousPage;

                            $previousUrl =
                                "journals.php?" .
                                http_build_query(
                                    $queryParameters
                                );

                            ?>

                            <li class="page-item <?= $page <= 1
                                ? "disabled"
                                : ""; ?>">

                                <a
                                    class="page-link"
                                    href="<?= $page > 1
                                        ? admin_escape(
                                            $previousUrl
                                        )
                                        : "#"; ?>"
                                >
                                    Previous
                                </a>

                            </li>

                            <?php

                            $startPage = max(
                                1,
                                $page - 2
                            );

                            $endPage = min(
                                $totalPages,
                                $page + 2
                            );

                            ?>

                            <?php for (
                                $pageNumber = $startPage;
                                $pageNumber <= $endPage;
                                $pageNumber++
                            ): ?>

                                <?php

                                $queryParameters = $_GET;

                                $queryParameters["page"] =
                                    $pageNumber;

                                $pageUrl =
                                    "journals.php?" .
                                    http_build_query(
                                        $queryParameters
                                    );

                                ?>

                                <li class="page-item <?= $pageNumber === $page
                                    ? "active"
                                    : ""; ?>">

                                    <a
                                        class="page-link"
                                        href="<?= admin_escape(
                                            $pageUrl
                                        ); ?>"
                                    >
                                        <?= $pageNumber; ?>
                                    </a>

                                </li>

                            <?php endfor; ?>

                            <?php

                            $queryParameters = $_GET;

                            $nextPage = min(
                                $totalPages,
                                $page + 1
                            );

                            $queryParameters["page"] =
                                $nextPage;

                            $nextUrl =
                                "journals.php?" .
                                http_build_query(
                                    $queryParameters
                                );

                            ?>

                            <li class="page-item <?= $page >= $totalPages
                                ? "disabled"
                                : ""; ?>">

                                <a
                                    class="page-link"
                                    href="<?= $page < $totalPages
                                        ? admin_escape(
                                            $nextUrl
                                        )
                                        : "#"; ?>"
                                >
                                    Next
                                </a>

                            </li>

                        </ul>

                    </nav>

                <?php endif; ?>

            </section>

        </main>

    </div>

</div>

<!-- View Journal Modal -->
<div
    class="modal fade"
    id="viewJournalModal"
    tabindex="-1"
    aria-labelledby="viewJournalModalLabel"
    aria-hidden="true"
>

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content admin-journal-view-modal">

            <div class="modal-header">

                <div>

                    <span class="admin-section-label">
                        Student Journal
                    </span>

                    <h2
                        class="modal-title"
                        id="viewJournalModalLabel"
                    >
                        Journal Entry
                    </h2>

                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>

            </div>

            <div class="modal-body">

                <div class="admin-journal-modal-meta">

                    <div>

                        <span>Student</span>

                        <strong id="viewJournalStudent">
                            —
                        </strong>

                        <small id="viewJournalNumber">
                            —
                        </small>

                    </div>

                    <div>

                        <span>Date Created</span>

                        <strong id="viewJournalDate">
                            —
                        </strong>

                    </div>

                </div>

                <div class="admin-journal-modal-content">

                    <span>Journal Content</span>

                    <p id="viewJournalContent">
                        —
                    </p>

                </div>

                <div class="admin-journal-modal-notice">

                    <span>🔒</span>

                    <p>
                        This journal entry is confidential. Do not copy,
                        distribute or disclose its contents without
                        proper authorisation.
                    </p>

                </div>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-poly"
                    data-bs-dismiss="modal"
                >
                    Close
                </button>

            </div>

        </div>

    </div>

</div>

<!-- Delete Journal Modal -->
<div
    class="modal fade"
    id="deleteJournalModal"
    tabindex="-1"
    aria-labelledby="deleteJournalModalLabel"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content admin-delete-modal">

            <div class="modal-header">

                <h2
                    class="modal-title fs-5"
                    id="deleteJournalModalLabel"
                >
                    Delete Journal Entry
                </h2>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>

            </div>

            <form
                method="POST"
                action="journals.php"
            >

                <div class="modal-body">

                    <div class="admin-delete-warning-icon">
                        ⚠️
                    </div>

                    <p>
                        Are you sure you want to permanently delete
                        <strong id="deleteJournalTitle">
                            this journal entry
                        </strong>?
                    </p>

                    <div class="alert alert-danger">

                        This journal entry will be permanently removed.
                        This action cannot be undone.

                    </div>

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= admin_escape(
                            $csrfToken
                        ); ?>"
                    >

                    <input
                        type="hidden"
                        name="journal_id"
                        id="deleteJournalId"
                        value=""
                    >

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        data-bs-dismiss="modal"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        name="delete_journal"
                        class="btn btn-danger"
                    >
                        Delete Permanently
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById("adminSidebar");
    const overlay = document.getElementById(
        "adminSidebarOverlay"
    );
    const openButton = document.getElementById(
        "openAdminSidebar"
    );
    const closeButton = document.getElementById(
        "closeAdminSidebar"
    );

    function openSidebar() {
        if (!sidebar || !overlay) {
            return;
        }

        sidebar.classList.add("show");
        overlay.classList.add("show");
        document.body.classList.add("admin-sidebar-open");
    }

    function closeSidebar() {
        if (!sidebar || !overlay) {
            return;
        }

        sidebar.classList.remove("show");
        overlay.classList.remove("show");
        document.body.classList.remove("admin-sidebar-open");
    }

    if (openButton) {
        openButton.addEventListener(
            "click",
            openSidebar
        );
    }

    if (closeButton) {
        closeButton.addEventListener(
            "click",
            closeSidebar
        );
    }

    if (overlay) {
        overlay.addEventListener(
            "click",
            closeSidebar
        );
    }

    const viewModal = document.getElementById(
        "viewJournalModal"
    );

    if (viewModal) {
        viewModal.addEventListener(
            "show.bs.modal",
            function (event) {
                const button = event.relatedTarget;

                if (!button) {
                    return;
                }

                document.getElementById(
                    "viewJournalModalLabel"
                ).textContent =
                    button.getAttribute(
                        "data-journal-title"
                    ) || "Journal Entry";

                document.getElementById(
                    "viewJournalStudent"
                ).textContent =
                    button.getAttribute(
                        "data-journal-student"
                    ) || "Not available";

                document.getElementById(
                    "viewJournalNumber"
                ).textContent =
                    button.getAttribute(
                        "data-journal-number"
                    ) || "Not available";

                document.getElementById(
                    "viewJournalDate"
                ).textContent =
                    button.getAttribute(
                        "data-journal-date"
                    ) || "Not available";

                document.getElementById(
                    "viewJournalContent"
                ).textContent =
                    button.getAttribute(
                        "data-journal-content"
                    ) || "No journal content.";
            }
        );
    }

    const deleteModal = document.getElementById(
        "deleteJournalModal"
    );

    if (deleteModal) {
        deleteModal.addEventListener(
            "show.bs.modal",
            function (event) {
                const button = event.relatedTarget;

                if (!button) {
                    return;
                }

                document.getElementById(
                    "deleteJournalId"
                ).value =
                    button.getAttribute(
                        "data-journal-id"
                    ) || "";

                document.getElementById(
                    "deleteJournalTitle"
                ).textContent =
                    button.getAttribute(
                        "data-journal-title"
                    ) || "this journal entry";
            }
        );
    }
});
</script>

</body>
</html>