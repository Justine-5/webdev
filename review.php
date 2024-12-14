<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['LoggedIn']) || !$_SESSION['LoggedIn']) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['UserId'];
$deckId = isset($_GET['id']) ? intval($_GET['id']) : 0;

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
    $stmt = $conn->prepare("SELECT id, front, back, card_interval, ease_factor FROM cards WHERE deck_id = ? AND next_review_date <= CURDATE() ORDER BY next_review_date ASC");
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
    <link rel="stylesheet" href="styles/review.css">
    
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
                            <div class="answers">
                                <button class="forgot">
                                  <p class="answer-name">Forgot</p>
                                  <p class="next-review"></p>
                                </button>
                                <button class="hard">
                                  <p class="answer-name">Hard</p>
                                  <p class="next-review"></p>
                                </button>
                                <button class="good">
                                  <p class="answer-name">Good</p>
                                  <p class="next-review"></p>
                                </button>
                                <button class="easy">
                                  <p class="answer-name">Easy</p>
                                  <p class="next-review"></p>
                                </button>
                              </div>
                        </div>
                    </div>
                </div>
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

                    function calculateNextReviewDays(card, answer) {
                        let interval = parseFloat(card.card_interval);
                        let easeFactor = parseFloat(card.ease_factor);
                        let nextInterval;

                        switch (answer) {
                            case 'forgot':
                                nextInterval = 0;
                                break;
                            case 'hard':
                                nextInterval = Math.max(1, Math.round(interval * 0.8));
                                break;
                            case 'good':
                                nextInterval = Math.max(1, Math.round(interval * easeFactor));
                                break;
                            case 'easy':
                                nextInterval = Math.max(1, Math.round(interval * easeFactor * 1.4));
                                break;
                            default:
                                nextInterval = 0;
                        }

                        return nextInterval;
                    }

                    function updateAnswerButtons(card) {
                        const answers = ['forgot', 'hard', 'good', 'easy'];
                        answers.forEach(function(answer) {
                            const days = calculateNextReviewDays(card, answer);
                            $('.' + answer + ' .next-review').text(`${days}d`);
                        });
                    }

                    function updateCard(index) {
                        const card = cards[index];
                        $('.card-holder').data('id', card.id);
                        $('.front-card p').text(card.front);
                        $('.back-text p').text(card.back);
                        $('#progress-text').text(`${index + 1} / ${cards.length}`);
                        $('.progress-bar-fill').css('width', `${((index + 1) / cards.length) * 100}%`);
                        updateAnswerButtons(card);
                    }

                    $('.card-holder').on('click', function() {
                        $(this).toggleClass('card-click');
                    });

                    $('.answers button').on('click', function () {
                        const action = $(this).find('.answer-name').text().toLowerCase();
                        const cardId = $('.card-holder').data('id');

                        $.ajax({
                            url: 'spaced_repetition.php',
                            method: 'POST',
                            data: { action: 'review', card_id: cardId, answer: action },
                            success: function (response) {
                                const res = JSON.parse(response);
                                if (res.success) {
                                    if (currentIndex < cards.length - 1) {
                                        currentIndex++;
                                        updateCard(currentIndex);
                                    } else {
                                        location.reload();
                                    }
                                } else {
                                    alert(res.message);
                                }
                            },
                            error: function () {
                                alert('Failed to update card.');
                            }
                        });
                    });

                    updateCard(currentIndex);
                });
            </script>
        <?php else: ?>
            <p>No cards available for review. Please check your deck or add more cards.</p>
        <?php endif; ?>
    </main>
</body>

</html>