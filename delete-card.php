<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['LoggedIn']) || !$_SESSION['LoggedIn']) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$userId = $_SESSION['UserId'];
$cardId = isset($_POST['card_id']) ? intval($_POST['card_id']) : 0;

if ($cardId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid card ID']);
    exit;
}

try {
    // delete card
    $stmt = $conn->prepare("DELETE FROM cards WHERE id = ? AND deck_id IN (SELECT id FROM decks WHERE account_id = ?)");
    $stmt->bind_param("ii", $cardId, $userId);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Card deleted successfully!']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error deleting card: ' . $e->getMessage()]);
}
?>
