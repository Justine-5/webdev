<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['LoggedIn']) || !$_SESSION['LoggedIn']) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['UserId'];
$deckId = isset($_POST['deck_id']) ? intval($_POST['deck_id']) : 0;

if ($_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['csv_file']['tmp_name'];

    try {
        // Validate deck ownership
        $stmt = $conn->prepare("SELECT id FROM decks WHERE id = ? AND account_id = ?");
        $stmt->bind_param("ii", $deckId, $userId);
        $stmt->execute();
        $deck = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$deck) {
            die("Deck not found or access denied.");
        }

        // Read the CSV file
        $handle = fopen($file, 'r');
        fgetcsv($handle); // Skip the header row

        while (($data = fgetcsv($handle)) !== false) {
            $front = trim($data[0]);
            $back = trim($data[1]);

            if (!empty($front) && !empty($back)) {
                $stmt = $conn->prepare("INSERT INTO cards (deck_id, front, back) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $deckId, $front, $back);
                $stmt->execute();
                $stmt->close();
            }
        }

        fclose($handle);

        $_SESSION['Success'] = "Cards imported successfully!";
    } catch (Exception $e) {
        $_SESSION['Error'] = "Error importing cards: " . $e->getMessage();
    }
} else {
    $_SESSION['Error'] = "File upload error.";
}

header("Location: deck-info.php?id=$deckId");
exit;
?>
