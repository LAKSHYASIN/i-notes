<?php
/*
 * Root index.php — orchestrates the app using includes and views.
 * Keeps all application logic (DB handlers) intact and loads the view.
 */
require_once __DIR__ . '/includes/config.php';

// Handle POST AJAX actions (insert/update/delete) — keep logic identical to previous implementation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $sno = isset($_POST['sno']) ? intval($_POST['sno']) : 0;
    $isAjax = isset($_POST['ajax']) && $_POST['ajax'] === '1';

    if ($action === 'insert') {
        $sql = "INSERT INTO notes (`title`, `description`, `category`, `tstamp`) VALUES (?, ?, ?, current_timestamp())";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            if ($isAjax) send_json(['success' => false, 'message' => mysqli_error($conn)]);
            die();
        }
        mysqli_stmt_bind_param($stmt, 'sss', $title, $description, $category);
        $ok = mysqli_stmt_execute($stmt);
        if ($isAjax) {
            if ($ok) send_json(['success' => true, 'message' => 'Note added.']);
            else send_json(['success' => false, 'message' => mysqli_stmt_error($stmt)]);
        }
        mysqli_stmt_close($stmt);
        if (!$isAjax) header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    if ($action === 'update') {
        $sql = "UPDATE notes SET title = ?, description = ?, category = ? WHERE sno = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            if ($isAjax) send_json(['success' => false, 'message' => mysqli_error($conn)]);
            die();
        }
        mysqli_stmt_bind_param($stmt, 'sssi', $title, $description, $category, $sno);
        $ok = mysqli_stmt_execute($stmt);
        if ($isAjax) {
            if ($ok) send_json(['success' => true, 'message' => 'Note updated.']);
            else send_json(['success' => false, 'message' => mysqli_stmt_error($stmt)]);
        }
        mysqli_stmt_close($stmt);
        if (!$isAjax) header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    if ($action === 'delete') {
        $sql = "DELETE FROM notes WHERE sno = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            if ($isAjax) send_json(['success' => false, 'message' => mysqli_error($conn)]);
            die();
        }
        mysqli_stmt_bind_param($stmt, 'i', $sno);
        $ok = mysqli_stmt_execute($stmt);
        if ($isAjax) {
            if ($ok) send_json(['success' => true, 'message' => 'Note deleted.']);
            else send_json(['success' => false, 'message' => mysqli_stmt_error($stmt)]);
        }
        mysqli_stmt_close($stmt);
        if (!$isAjax) header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Fetch notes
$notes = [];
$sql = "SELECT sno, title, description, category, tstamp FROM notes ORDER BY tstamp DESC";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $rsno, $rtitle, $rdescription, $rcategory, $rtstamp);
    while (mysqli_stmt_fetch($stmt)) {
        $notes[] = [
            'sno' => $rsno,
            'title' => $rtitle,
            'description' => $rdescription,
            'category' => $rcategory,
            'tstamp' => $rtstamp,
        ];
    }
    mysqli_stmt_close($stmt);
} else {
    $res = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($res)) $notes[] = $row;
}

// Render view
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/views/home.php';
require_once __DIR__ . '/includes/footer.php';

?>