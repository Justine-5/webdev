<?php
require_once 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['LoggedIn']) || !$_SESSION['LoggedIn']) {
        echo json_encode(['success' => false, 'message' => 'User not logged in.']);
        exit;
    }

    $userId = $_SESSION['UserId'];
    $cardId = isset($_POST['card_id']) ? intval($_POST['card_id']) : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $answer = isset($_POST['answer']) ? $_POST['answer'] : '';

    if (!in_array($answer, ['forgot', 'hard', 'good', 'easy'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid input.']);
        exit;
    }

    try {
        // Get card details
        $stmt = $conn->prepare("SELECT card_interval, ease_factor, repetition_count FROM cards WHERE id = ? AND deck_id IN (SELECT id FROM decks WHERE account_id = ?)");
        $stmt->bind_param("ii", $cardId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $card = $result->fetch_assoc();
        $stmt->close();

        if (!$card) {
            echo json_encode(['success' => false, 'message' => 'Card not found or access denied.']);
            exit;
        }

        $oldInterval = $card['card_interval'];
        $easeFactor = $card['ease_factor'];
        $repetitionCount = $card['repetition_count'];

        switch ($answer) {
            case 'forgot':
                $interval = 0;
                $easeFactor = max(1.3, $easeFactor - 0.3);
                break;
            case 'hard':
                $interval = max(1, $oldInterval * 0.8);
                $easeFactor = max(1.3, $easeFactor - 0.15);
            case 'good':
                $interval = max(1, $oldInterval * $easeFactor);
                break;
            case 'easy':
                $interval = max(1, $oldInterval * $easeFactor * 1.4);
                $easeFactor = min(3, $easeFactor + 0.15);
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid answer provided.']);
                exit;
        }
        
        $repetitionCount++;

        $roundInterval = round($interval);
        // Calculate next review date
        $nextReviewDate = date('Y-m-d', strtotime("+$roundInterval days"));

        $interval = round($interval, 3);
        $easeFactor = round($easeFactor, 3);
        // Update card in the database
        $stmt = $conn->prepare("UPDATE cards SET card_interval = ?, ease_factor = ?, repetition_count = ?, next_review_date = ?, last_review_date = NOW() WHERE id = ?");
        $stmt->bind_param("ddisi", $interval, $easeFactor, $repetitionCount, $nextReviewDate, $cardId);
        $stmt->execute();
        $stmt->close();

        require_once 'functions.php';
        updateStreak($userId, $conn);

        echo json_encode(['success' => true, 'message' => 'Card updated successfully.', 'next_review_date' => $nextReviewDate]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error updating card: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
