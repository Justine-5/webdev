<?php

session_start();
if (!isset($_SESSION['LoggedIn']) || $_SESSION['LoggedIn'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'nav.php';
require_once 'sidebar.php';
require_once "db.php";

$userId = $_SESSION['UserId'];
$username = $_SESSION['Username'];
$email = $_SESSION['Email'];

// streaks
$stmt = $conn->prepare("SELECT current_streak, week_activity FROM streaks WHERE account_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$streak = $result->fetch_assoc();
$stmt->close();

$currentStreak = $streak['current_streak'] ?? 0;
$weekActivity = json_decode($streak['week_activity'] ?? '{}', true);

// recents
$stmt = $conn->prepare("
    SELECT d.id, d.name, COUNT(c.id) AS card_count
    FROM decks d
    LEFT JOIN cards c ON d.id = c.deck_id
    WHERE d.account_id = ?
    GROUP BY d.id
    ORDER BY d.last_opened DESC
    LIMIT 6
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$recentDecks = [];
while ($row = $result->fetch_assoc()) {
    $recentDecks[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/general.css">
    <link rel="stylesheet" href="styles/cards-wrapper.css">
    <link rel="stylesheet" href="styles/home.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rammetto+One&display=swap" rel="stylesheet">
    <script src="script/home.js" defer></script>
    <title>Home</title>
</head>

<body>
    <?php renderNav(true); ?>
    <?php renderSidebar('home'); ?>

    <main>
        <section class="tools">
            <h2 class="greetings">Hello, <?= $username ?></h2>

            <div class="streak-wrapper">
                <div class="streaks">
                    <div class="streak-top">
                        <img src="icons/fire.svg">
                        <div class="login-streak">
                            <h3>Login Streak</h3>
                            <p><?= $currentStreak ?></p>
                        </div>
                    </div>

                    <div class="streak-bottom">
                        <?php
                        $days = ['S', 'M', 'T', 'W', 'Th', 'F', 'Sa'];
                        $currentDayIndex = (int) date('w');

                        foreach ($days as $index => $day) {
                            $filled = isset($weekActivity[$day]) && $weekActivity[$day] ? 'filled' : '';
                            $isToday = ($index === $currentDayIndex) ? 'current-day' : '';
                            echo "<div class='day $isToday'>
                                    <p>$day</p>
                                    <div class='streak-fill $filled'></div>
                                </div>";
                        }
                        ?>
                    </div>
                </div>
            </div>

        </section>

        <section class="recents">
            <h2>Recent Decks</h2>
            <div class="cards-wrapper home-wrapper">

                <?php if (!empty($recentDecks)) : ?>
                    <?php foreach ($recentDecks as $deck) : ?>
                        <a href="deck-info.php?id=<?= htmlspecialchars($deck['id']) ?>" class="card-link">
                            <div class="card">
                                <div class="card-img"></div>
                                <div class="card-info">
                                    <h3><?= htmlspecialchars($deck['name']) ?></h3>
                                    <p><?= htmlspecialchars($deck['card_count']) ?> cards</p>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>
        </section>

    </main>


</body>

</html>