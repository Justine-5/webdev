<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['LoggedIn']) || !$_SESSION['LoggedIn']) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['UserId'];
$deckId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Update last_studied
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_last_studied') {
    $cardId = intval($_POST['card_id']);
    
    try {
        $stmt = $conn->prepare("UPDATE cards 
                                SET last_studied = CURRENT_DATE() 
                                WHERE id = ? AND deck_id IN (SELECT id FROM decks WHERE account_id = ?)");
        $stmt->bind_param("ii", $cardId, $userId);
        $stmt->execute();
        $stmt->close();

        require_once 'functions.php';
        updateStreak($userId, $conn);

        echo json_encode(['success' => true, 'message' => 'Last studied updated successfully.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error updating last studied: ' . $e->getMessage()]);
    }
    exit;
}

try {
    // Get deck details
    $stmt = $conn->prepare("SELECT name FROM decks WHERE id = ? AND account_id = ?");
    $stmt->bind_param("ii", $deckId, $userId);
    $stmt->execute();
    $deckResult = $stmt->get_result();
    $deck = $deckResult->fetch_assoc();
    $stmt->close();

    if (!$deck) {
        die("Deck not found or access denied.");
    }

    // Get cards
    $stmt = $conn->prepare("SELECT id, front, back FROM cards WHERE deck_id = ?");
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

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/study.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Study</title>
</head>

<body>
    <main>
        <a class="back-link" href="deck-info.php?id=<?= $deckId ?>">
            <div class="back">
                <img src="icons/back-black.svg" alt="">
                <h2><?= htmlspecialchars($deck['name']); ?></h2>
            </div>
        </a>

        <?php if (!empty($cards)): ?>
            <div class="card">
                <a class="left" id="prev-card" href="#">
                    <img src="icons/back.svg" alt="Previous">
                </a>
                <div class="background">
                    <div class="card-holder" data-id="<?= $cards[0]['id']; ?>">
                        <div class="front-card">
                            <p><?= htmlspecialchars($cards[0]['front']); ?></p>
                        </div>
                        <div class="back-card">
                            <div class="front-text">
                                <p><?= htmlspecialchars($cards[0]['front']); ?></p>
                                <hr>
                            </div>
                            <div class="back-text">
                                <p><?= htmlspecialchars($cards[0]['back']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <a class="right" id="next-card" href="#">
                    <img src="icons/next.svg" alt="Next">
                </a>
            </div>

            <div class="progress">
                <div class="progress-bar">
                    <div class="progress-bar-fill"></div>
                </div>
                <p id="progress-text">1 / <?= count($cards); ?></p>
            </div>

            <script>
                $(document).ready(function() {
                    const cards = <?= json_encode($cards); ?>;
                    let currentIndex = 0;

                    function updateCard(index) {
                        const card = cards[index];
                        $('.card-holder').removeClass('card-click');
                        $('.card-holder').data('id', card.id);
                        $('.front-card p').text(card.front);
                        $('.front-text p').text(card.front);
                        $('.back-text p').text(card.back);
                        $('#progress-text').text(`${index + 1} / ${cards.length}`);
                        $('.progress-bar-fill').css('width', `${((index + 1) / cards.length) * 100}%`);
                    }

                    function updateLastStudied(cardId) {
                        $.ajax({
                            url: 'study.php',
                            method: 'POST',
                            data: {
                                action: 'update_last_studied',
                                card_id: cardId
                            },
                            success: function(response) {
                                const res = JSON.parse(response);
                                if (!res.success) {
                                    console.error(res.message);
                                }
                            },
                            error: function() {
                                console.error('Failed to update last studied.');
                            }
                        });
                    }

                    $('#next-card').on('click', function(e) {
                        e.preventDefault();
                        if (currentIndex < cards.length - 1) {
                            currentIndex++;
                            updateCard(currentIndex);
                            updateLastStudied(cards[currentIndex].id);
                        }
                    });

                    $('#prev-card').on('click', function(e) {
                        e.preventDefault();
                        if (currentIndex > 0) {
                            currentIndex--;
                            updateCard(currentIndex);
                            updateLastStudied(cards[currentIndex].id);
                        }
                    });

                    $('.card-holder').on('click', function() {
                        $(this).toggleClass('card-click');
                        updateLastStudied(cards[currentIndex].id);
                    });

                    updateCard(currentIndex);
                });
            </script>
        <?php else: ?>
            <p>No cards available for study. Please check your deck or add more cards.</p>
        <?php endif; ?>
    </main>
</body>

</html>