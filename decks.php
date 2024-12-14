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

// add decks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
  $deckName = trim($_POST['deck-name']);
  $deckDescription = trim($_POST['deck-description']);

  if (empty($deckName)) {
      $_SESSION['Error'] = "Deck name is required.";
      header("Location: decks.php");
      exit;
  }

  try {
      $originalDeckName = $deckName;
      $counter = 0;

      do {
        $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM decks WHERE account_id = ? AND name = ?");
        $stmt->bind_param("is", $userId, $deckName);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row['count'] > 0) {
            $counter++;
            $deckName = $originalDeckName . " ($counter)";
        }
      } while ($row['count'] > 0);

      $stmt = $conn->prepare("INSERT INTO decks (account_id, name, description) VALUES (?, ?, ?)");
      $stmt->bind_param("iss", $userId, $deckName, $deckDescription);
      $stmt->execute();
      $stmt->close();

      $_SESSION['Success'] = "Deck added successfully!";
  } catch (Exception $e) {
      $_SESSION['Error'] = "Error adding deck: " . $e->getMessage();
  }

  header("Location: decks.php");
  exit;
}

// get decks
try {
    $stmt = $conn->prepare("
        SELECT d.id, d.name, COUNT(c.id) AS card_count
        FROM decks d
        LEFT JOIN cards c ON d.id = c.deck_id
        WHERE d.account_id = ?
        GROUP BY d.id, d.name
        ORDER BY d.date_created DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $decks = [];
    while ($row = $result->fetch_assoc()) {
        $decks[] = $row;
    }

    $stmt->close();
} catch (Exception $e) {
    die("Error fetching decks: " . $e->getMessage());
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/decks.css">
    <link rel="stylesheet" href="styles/general.css">
    <link rel="stylesheet" href="styles/cards-wrapper.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rammetto+One&display=swap" rel="stylesheet">
    <script src="script/home.js" defer></script>
    <script src="script/decks.js" defer></script>
    <title>Decks</title>
</head>
<body>
    <?php renderNav(true); ?>
    <?php renderSidebar('decks'); ?>

    <main>
        <section class="all-decks">
            <div class="add-deck">
                <h2>All Decks</h2>
                <button class="add-button showOverlay">+ Add Deck</button>
            </div>

            <?php if (!empty($decks)): ?>
                <div class="cards-wrapper">
                    <?php foreach ($decks as $deck): ?>
                        <a href="deck-info.php?id=<?= $deck['id']; ?>" class="card-link">
                            <div class="card">
                                <div class="card-info">
                                    <h3><?= htmlspecialchars($deck['name']); ?></h3>
                                    <p><strong><?= $deck['card_count']; ?> cards</strong></p>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No decks available. Start by adding one!</p>
            <?php endif; ?>

        </section>

        <div class="add-deck-overlay hide-overlay">
          <form id="overlay-form" action="decks.php" method="post">
            <input type="hidden" name="action" value="create">
            <h3>Add Deck</h3>
            <label for="deck-name">Name</label>
            <input id="deck-name" name="deck-name" type="text" placeholder="Name" required>
            <label for="deck-description">Description</label>
            <input id="deck-description" name="deck-description" type="text" placeholder="Description (optional)">
            <button type="submit">add</button>
            <button id="cancel">cancel</button>
          </form>
        </div>
    </main>
</body>
</html>
