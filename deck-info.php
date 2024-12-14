<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['LoggedIn']) || !$_SESSION['LoggedIn']) {
    header("Location: login.php");
    exit;
}

require_once 'nav.php';
require_once 'sidebar.php';

$userId = $_SESSION['UserId'];
$deckId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// edit last opened
try {
    $stmt = $conn->prepare("UPDATE decks 
                            SET last_opened = CURRENT_TIMESTAMP() 
                            WHERE id = ? AND account_id = ?");
    $stmt->bind_param("ii", $deckId, $userId);
    $stmt->execute();
    $stmt->close();
} catch (Exception $e) {
    error_log("Error updating last_opened: " . $e->getMessage());
}

// edit deck
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_deck') {
    $deckName = trim($_POST['deck-name']);
    $deckDescription = trim($_POST['deck-description']);

    if (empty($deckName)) {
        $_SESSION['Error'] = "Deck name is required.";
        header("Location: deck-info.php?id=$deckId");
        exit;
    }

    try {
        $stmt = $conn->prepare("UPDATE decks SET name = ?, description = ? WHERE id = ? AND account_id = ?");
        $stmt->bind_param("ssii", $deckName, $deckDescription, $deckId, $userId);
        $stmt->execute();
        $stmt->close();

        $_SESSION['Success'] = "Deck updated successfully!";
    } catch (Exception $e) {
        $_SESSION['Error'] = "Error updating deck: " . $e->getMessage();
    }

    header("Location: deck-info.php?id=$deckId");
    exit;
}

// delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_deck') {
    try {
        $stmt = $conn->prepare("DELETE FROM decks WHERE id = ? AND account_id = ?");
        $stmt->bind_param("ii", $deckId, $userId);
        $stmt->execute();
        $stmt->close();

        $_SESSION['Success'] = "Deck deleted successfully!";
    } catch (Exception $e) {
        $_SESSION['Error'] = "Error deleting deck: " . $e->getMessage();
    }

    header("Location: decks.php");
    exit;
}

// add cards
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_card') {
    $cardFront = trim($_POST['front']);
    $cardBack = trim($_POST['back']);

    if (empty($cardFront) || empty($cardBack)) {
        $_SESSION['Error'] = "Both front and back of the card are required.";
        header("Location: deck-info.php?id=$deckId");
        exit;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO cards (deck_id, front, back) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $deckId, $cardFront, $cardBack);
        $stmt->execute();
        $stmt->close();

        $_SESSION['Success'] = "Card added successfully!";
    } catch (Exception $e) {
        $_SESSION['Error'] = "Error adding card: " . $e->getMessage();
    }

    header("Location: deck-info.php?id=$deckId");
    exit;
}

// get deck infos
try {
    $stmt = $conn->prepare("SELECT * FROM decks WHERE id = ? AND account_id = ?");
    $stmt->bind_param("ii", $deckId, $userId);
    $stmt->execute();
    $deckResult = $stmt->get_result();
    $deck = $deckResult->fetch_assoc();
    $stmt->close();

    if (!$deck) {
        die("Deck not found or access denied.");
    }

    // get cards
    $stmt = $conn->prepare("SELECT * FROM cards WHERE deck_id = ?");
    $stmt->bind_param("i", $deckId);
    $stmt->execute();
    $cardsResult = $stmt->get_result();
    $cards = [];
    while ($row = $cardsResult->fetch_assoc()) {
        $cards[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    die("Error fetching deck or cards: " . $e->getMessage());
}

$todayStats = [
    'studied' => 0,
    'total_studied' => 0,
    'reviewed' => 0,
    'total_reviewed' => 0
];

try {
    $stmt = $conn->prepare("SELECT COUNT(*) AS studied_today FROM cards WHERE deck_id = ? AND last_studied = CURDATE()");
    $stmt->bind_param("i", $deckId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $todayStats['studied'] = $row['studied_today'];
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) as total_studied FROM cards WHERE deck_id = ?");
    $stmt->bind_param("i", $deckId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $todayStats['total_studied'] = $row['total_studied'];
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) AS reviewed_today FROM cards WHERE deck_id = ? AND last_review_date = CURDATE()");
    $stmt->bind_param("i", $deckId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $todayStats['reviewed'] = $row['reviewed_today'];
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) AS total_reviewed FROM cards WHERE deck_id = ? AND (next_review_date <= CURDATE() OR last_review_date = CURDATE())");
    $stmt->bind_param("i", $deckId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $todayStats['total_reviewed'] = $row['total_reviewed'];
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching today's stats: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/general.css">
    <link rel="stylesheet" href="styles/decks.css">
    <!-- <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.css" /> -->
    <link rel="stylesheet" href="styles/card-info.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rammetto+One&display=swap" rel="stylesheet">
    <!-- <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css"> -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="script/home.js" defer></script>
    <script src="script/deck-info.js" defer></script>

    <title><?= htmlspecialchars($deck['name']); ?> - Deck Info</title>
</head>
<body>
    <?php renderNav(true); ?>
    <?php renderSidebar('decks'); ?>

    <main>
        <section class="deck-description section">
            <div class="top-container">
                <div class="description">
                    <h2><?= htmlspecialchars($deck['name']); ?></h2>
                    <p class="card-number"><?= count($cards); ?> cards</p>
                    <p><?= !empty($deck['description']) ? htmlspecialchars($deck['description']) : "No description available."; ?></p>
                </div>

                <div class="top-buttons-container">
                    <form action="export-cards.php" method="get" style="display: inline;">
                        <input type="hidden" name="id" value="<?= $deckId; ?>">
                        <button type="submit">
                            <img src="icons/export.svg" alt="Export Deck">
                        </button>
                    </form>

                    <form action="import-cards.php" method="post" enctype="multipart/form-data" style="display: inline;">
                        <input type="hidden" name="deck_id" value="<?= $deckId; ?>">
                        <button type="button" class="import-button">
                            <img src="icons/import.svg" alt="Import Deck">
                        </button>
                        <input id="csvFile" type="file" name="csv_file" accept=".csv" onchange="this.form.submit()" style="display: none;">
                    </form>
                </div>

            </div>

            <div class="bottom-buttons-container">
                <button class="bottom-buttons" onclick="window.location.href='download-pdf.php?id=<?= $deckId; ?>'">
                    <img src="icons/pdf.svg" alt="Download PDF">
                    <p>Download PDF</p>
                </button>
                <div class="edit-delete">
                    <button class="bottom-buttons edit" onclick="editDeck(<?= $deckId; ?>, '<?= htmlspecialchars($deck['name']); ?>', '<?= htmlspecialchars($deck['description']); ?>')">
                        <img src="icons/edit.svg" alt="Edit">
                        <p>Edit</p>
                    </button>
                    <form id="delete-deck-form" action="deck-info.php?id=<?= $deckId; ?>" method="post" style="display: none;">
                        <input type="hidden" name="action" value="delete_deck">
                    </form>
                    <button class="bottom-buttons delete" onclick="confirmDelete()">
                        <img src="icons/delete.svg" alt="Delete">
                        <p>Delete</p>
                    </button>
                </div>
            </div>
        </section>
            
        <section class="section">
            <h3 class="stats">Today's Stats</h3>
            <div class="study-review">
                <div>
                    <p class="studied">Studied</p>
                    <p class="number"><?= $todayStats['studied']; ?>/<?= $todayStats['total_studied']; ?></p>
                    <button class="bottom-buttons study" onclick="window.location.href='study.php?id=<?= $deckId; ?>'">
                        <img src="icons/study.svg" alt="Study">
                        <p>Study</p>
                    </button>
                </div>

                <div>
                    <p class="studied">Reviewed</p>
                    <p class="number"><?= $todayStats['reviewed']; ?>/<?= $todayStats['total_reviewed']; ?></p>
                    <button class="bottom-buttons review" onclick="window.location.href='review.php?id=<?= $deckId; ?>'">
                        <img src="icons/review.svg" alt="Review">
                        <p>Review</p>
                    </button>
                </div>
            </div>
            
        </section>

        <section class="cards-list section">
            <form class="add-cards" action="deck-info.php?id=<?= $deckId; ?>" method="post">
                <h3>Add new card</h3>
                <input type="hidden" name="action" value="add_card">
                <div class="card-inputs">
                    <div>
                        <label for="inputFront">Front</label>
                        <input id="inputFront" name="front" type="text" placeholder="Front of card" required>
                    </div>
                    <div>
                        <label for="inputBack">Back</label>
                        <input id="inputBack" name="back" type="text" placeholder="Back of card" required>
                    </div>
                </div>
                <button type="submit">Add</button>
            </form>

            <div class="table-wrapper">
                <h3>All Cards</h3>
                <table id="cardsTable" class="display">
                    <thead>
                        <tr>
                            <th>Front</th>
                            <th>Back</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($cards)): ?>
                            <?php foreach ($cards as $card): ?>
                                <tr>
                                    <td>
                                        <p>Front</p>
                                        <div contenteditable="true" class="editable front-container" data-field="front" data-id="<?= $card['id'] ?>">
                                            <?= htmlspecialchars($card['front']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <p>Back</p>
                                        <div class="back-container">
                                            <div contenteditable="true" class="editable" data-field="back" data-id="<?= $card['id'] ?>">
                                                <?= htmlspecialchars($card['back']) ?>
                                            </div>
                                            <button class="delete-card-btn" data-id="<?= $card['id'] ?>">
                                                <img src="icons/delete.svg" alt="delete">
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </section>
    </main>
</body>
</html>
