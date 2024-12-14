<?php
function updateStreak($userId, $conn) {
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    // Fetch streak data for the user
    $stmt = $conn->prepare("SELECT current_streak, max_streak, last_login, week_activity FROM streaks WHERE account_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $streak = $result->fetch_assoc();
    $stmt->close();

    $currentStreak = $streak['current_streak'] ?? 0;
    $maxStreak = $streak['max_streak'] ?? 0;
    $lastLoginDate = $streak['last_login_date'] ?? null;
    $weekActivity = json_decode($streak['week_activity'] ?? '{}', true);

    if ($lastLoginDate === $today) {
        // No updates needed; already logged in today
        return;
    }

    if ($lastLoginDate === $yesterday) {
        $currentStreak++; // Increment streak
    } else {
        $currentStreak = 1; // Reset streak
    }

    // Update max streak if current streak exceeds it
    if ($currentStreak > $maxStreak) {
        $maxStreak = $currentStreak;
    }

    // Update week activity (current week starts from Sunday)
    $dayOfWeek = date('w'); // 0 (Sunday) to 6 (Saturday)
    $days = ['S', 'M', 'T', 'W', 'Th', 'F', 'Sa'];
    $weekActivity[$days[$dayOfWeek]] = true;

    // Save updates to the database
    $weekActivityJson = json_encode($weekActivity);
    $stmt = $conn->prepare("
        INSERT INTO streaks (account_id, current_streak, max_streak, last_login, week_activity)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            current_streak = VALUES(current_streak),
            max_streak = VALUES(max_streak),
            last_login = VALUES(last_login),
            week_activity = VALUES(week_activity)
    ");
    $stmt->bind_param("iiiss", $userId, $currentStreak, $maxStreak, $today, $weekActivityJson);
    $stmt->execute();
    $stmt->close();
}
?>
