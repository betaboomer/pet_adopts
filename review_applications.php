<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

require 'config.php';

// Handle status update form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['application_id'], $_POST['new_status'])) {
    $app_id = intval($_POST['application_id']);
    $new_status = $_POST['new_status'];

    // Fetch previous status and user_id
    $stmt = $conn->prepare("SELECT status, user_id FROM adoption_applications WHERE id = ?");
    $stmt->bind_param("i", $app_id);
    $stmt->execute();
    $stmt->bind_result($prev_status, $user_id);
    $stmt->fetch();
    $stmt->close();

    // Only update if status actually changed
    if ($prev_status !== $new_status) {
        // Update the application status
        $stmt = $conn->prepare("UPDATE adoption_applications SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $app_id);
        $stmt->execute();
        $stmt->close();

        // Update user score
        if ($new_status === "Approved") {
            $stmt = $conn->prepare("UPDATE users SET score = score + 10 WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
        } elseif ($new_status === "Rejected") {
            $stmt = $conn->prepare("UPDATE users SET score = score - 5 WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
        }

        // If approved, update the pet's status to 'Not Available'
        if ($new_status === "Approved") {
            $stmt = $conn->prepare("SELECT pet_id FROM adoption_applications WHERE id = ?");
            $stmt->bind_param("i", $app_id);
            $stmt->execute();
            $stmt->bind_result($pet_id);
            $stmt->fetch();
            $stmt->close();

            $stmt = $conn->prepare("UPDATE pets SET status = 'Not Available' WHERE pet_id = ?");
            $stmt->bind_param("i", $pet_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    header("Location: review_applications.php");
    exit();
}

// If viewing a specific application
$view_application = null;
if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
    $app_id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT a.*, p.name AS pet_name, u.name AS user_name, u.email, u.user_id 
                            FROM adoption_applications a
                            JOIN pets p ON a.pet_id = p.pet_id
                            JOIN users u ON a.user_id = u.user_id
                            WHERE a.id = ?");
    $stmt->bind_param("i", $app_id);
    $stmt->execute();
    $view_application = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Fetch all applications
$sql = "SELECT a.id, p.name AS pet_name, u.name AS user_name, a.application_date, a.status
        FROM adoption_applications a
        JOIN pets p ON a.pet_id = p.pet_id
        JOIN users u ON a.user_id = u.user_id
        ORDER BY a.application_date DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review Applications - Pet Adoption Portal (Admin)</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h1>Pet Adoption Portal (Admin)</h1>
    <nav>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="update_pets.php">Update Pets</a></li>
            <li><a href="review_applications.php">Review Applications</a></li>
            <li><a href="answer_questions.php">Answer Questions</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
</header>

<main>
    <h2>Review Adoption Applications</h2>

    <?php if ($view_application): ?>
        <section>
            <h3>Application Details</h3>
            <p><strong>Application ID:</strong> <?= $view_application['id'] ?></p>
            <p><strong>Pet Name:</strong> <?= htmlspecialchars($view_application['pet_name']) ?></p>
            <p><strong>User Name:</strong> <?= htmlspecialchars($view_application['user_name']) ?></p>
            <p><strong>User Email:</strong> <?= htmlspecialchars($view_application['email']) ?></p>
            <p><strong>Application Date:</strong> <?= htmlspecialchars($view_application['application_date']) ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($view_application['status']) ?></p>
            <p><strong>Reason for Adoption:</strong> <?= !empty($view_application['adoption_reason']) ? nl2br(htmlspecialchars($view_application['adoption_reason'])) : '<em>No reason provided.</em>' ?></p>
            <?php
            // Fetch and display user score
            $score_stmt = $conn->prepare("SELECT score FROM users WHERE user_id = ?");
            $score_stmt->bind_param("i", $view_application['user_id']);
            $score_stmt->execute();
            $score_stmt->bind_result($user_score);
            $score_stmt->fetch();
            $score_stmt->close();
            ?>
            <p><strong>User Score:</strong> <?= isset($user_score) ? intval($user_score) : 'N/A' ?></p>
            <?php
            // Fetch and display user preferences
            $pref_stmt = $conn->prepare("SELECT breed, preference_count FROM preferences WHERE user_id = ? ORDER BY preference_count DESC, breed ASC");
            $pref_stmt->bind_param("i", $view_application['user_id']);
            $pref_stmt->execute();
            $pref_result = $pref_stmt->get_result();
            if ($pref_result->num_rows > 0): ?>
                <h4>User Breed Preferences</h4>
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
                <p>User has not shown a preference for any breed yet.</p>
            <?php endif;
            $pref_stmt->close();
            ?>
            <h4>Update Status</h4>
            <form method="post">
                <input type="hidden" name="application_id" value="<?= $view_application['id'] ?>">
                <label for="new_status">New Status:</label>
                <select name="new_status" id="new_status">
                    <option value="Pending" <?= $view_application['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Approved" <?= $view_application['status'] === 'Approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="Rejected" <?= $view_application['status'] === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
                <button type="submit" class="button">Update</button>
            </form>
        </section>
        <hr>
    <?php endif; ?>

    <section>
        <h3>All Applications</h3>
        <?php
        if ($result->num_rows > 0) {
            echo "<table>";
            echo "<thead><tr><th>ID</th><th>Pet</th><th>User</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead><tbody>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>{$row['id']}</td>
                        <td>" . htmlspecialchars($row['pet_name']) . "</td>
                        <td>" . htmlspecialchars($row['user_name']) . "</td>
                        <td>" . htmlspecialchars($row['application_date']) . "</td>
                        <td>" . htmlspecialchars($row['status']) . "</td>
                        <td>
                            <a href='review_applications.php?action=view&id={$row['id']}'>View / Update</a>
                        </td>
                      </tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<p>No applications found.</p>";
        }
        ?>
    </section>
</main>

</body>
</html>

<?php $conn->close(); ?>
