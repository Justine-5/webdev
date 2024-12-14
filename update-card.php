<?php
require_once 'db.php';

session_start();

if (!isset($_SESSION['LoggedIn']) || !$_SESSION['LoggedIn']) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['UserId'];


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_card') {
    $cardId = intval($_POST['card_id']);
    $field = $_POST['field'];
    $newValue = $_POST['value'];

    if (empty($cardId) || empty($field) || empty($newValue)) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit;
    }

    // update card
    $stmt = $conn->prepare("UPDATE cards SET $field = ? WHERE id = ? AND deck_id IN (SELECT id FROM decks WHERE account_id = ?)");
    $stmt->bind_param("sii", $newValue, $cardId, $userId);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating card']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
