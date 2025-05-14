<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

require 'config.php';

$user_id = $_SESSION['user_id'];
// Fetch user info including score
$stmt = $conn->prepare("SELECT name, email, role, score FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Pet Adoption Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Pet Adoption Portal</h1>
        <nav>
            <ul>
                <li><a href="index.html">Home</a></li>
                <?php if ($user['role'] === 'admin'): ?>
                    <li><a href="search_pets.php">Search Pets</a></li>
                    <li><a href="update_pets.php">Manage Pet Listings</a></li>
                    <li><a href="review_applications.php">Review Adoption Applications</a></li>
                    <li><a href="answer_questions.php">Answer User Questions</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="search_pets.php">Search Available Pets</a></li>
                    <li><a href="submit_adoption_application.php">Submit Adoption Application</a></li>
                    <li><a href="ask_question.php">Ask a Question about a Pet</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <h2>Welcome to your Dashboard, <?php echo htmlspecialchars($user['name']); ?>!</h2>
        <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
        <p>Role: <?php echo htmlspecialchars($user['role']); ?></p>
        <?php if ($user['role'] !== 'admin'): ?>
        <p><strong>Your Score:</strong> <?php echo isset($user['score']) ? intval($user['score']) : 'N/A'; ?></p>
        <?php 
        // Show user breed preferences
        $pref_stmt = $conn->prepare("SELECT breed, preference_count FROM preferences WHERE user_id = ? ORDER BY preference_count DESC, breed ASC");
        $pref_stmt->bind_param("i", $user_id);
        $pref_stmt->execute();
        $pref_result = $pref_stmt->get_result();
        if ($pref_result->num_rows > 0): ?>
            <h3>Your Breed Preferences</h3>
            <table>
                <thead><tr><th>Breed</th><th>Preference Count</th></tr></thead>
                <tbody>
                <?php while ($pref = $pref_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($pref['breed']) ?></td>
                        <td><?= intval($pref['preference_count']) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>You have not shown a preference for any breed yet.</p>
        <?php endif;
        $pref_stmt->close();
        ?>
        <?php endif; ?>

        <?php if ($user['role'] === 'admin'): ?>
            <section>
                <h3>Admin Actions</h3>
                <ul>
                    <li><a href="update_pets.php">Manage Pet Listings</a></li>
                    <li><a href="review_applications.php">Review Adoption Applications</a></li>
                    <li><a href="answer_questions.php">Answer User Questions</a></li>
                </ul>
            </section>
        <?php else: ?>
            <section>
                <h3>Adopter Actions</h3>
                <ul>
                    <li><a href="search_pets.php">Search Available Pets</a></li>
                    <li><a href="submit_adoption_application.php">Submit Adoption Application</a></li>
                    <li><a href="ask_question.php">Ask a Question about a Pet</a></li>
                    <li><a href="my_applications.php">Updates</a></li>
                </ul>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
