<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'adopter' && $_SESSION['user_role'] !== 'user')) {
    header("Location: login.html");
    exit();
}

require 'config.php';

$success_msg = $error_msg = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['pet_id'])) {
    $user_id = $_SESSION['user_id'];
    $pet_id = intval($_POST['pet_id']);
    $status = 'Pending';
    $adoption_reason = isset($_POST['adoption_reason']) ? trim($_POST['adoption_reason']) : '';

    // Check if the user has already applied for the same pet
    $check_stmt = $conn->prepare("SELECT id FROM adoption_applications WHERE user_id = ? AND pet_id = ?");
    $check_stmt->bind_param("ii", $user_id, $pet_id);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        $error_msg = "You have already submitted an application for this pet.";
    } else {
        $stmt = $conn->prepare("INSERT INTO adoption_applications (user_id, pet_id, application_date, status, adoption_reason) VALUES (?, ?, NOW(), ?, ?)");
        $stmt->bind_param("iiss", $user_id, $pet_id, $status, $adoption_reason);

        if ($stmt->execute()) {
            $success_msg = "Your adoption application has been submitted successfully.";

            // Get the breed of the selected pet
            $breed_stmt = $conn->prepare("SELECT breed FROM pets WHERE pet_id = ?");
            $breed_stmt->bind_param("i", $pet_id);
            $breed_stmt->execute();
            $breed_stmt->bind_result($breed);
            $breed_stmt->fetch();
            $breed_stmt->close();

            // Check if preference already exists
            $pref_stmt = $conn->prepare("SELECT id FROM preferences WHERE user_id = ? AND breed = ?");
            $pref_stmt->bind_param("is", $user_id, $breed);
            $pref_stmt->execute();
            $pref_stmt->store_result();

            if ($pref_stmt->num_rows > 0) {
                // Update existing preference
                $pref_stmt->bind_result($pref_id);
                $pref_stmt->fetch();
                $update_stmt = $conn->prepare("UPDATE preferences SET preference_count = preference_count + 1 WHERE id = ?");
                $update_stmt->bind_param("i", $pref_id);
                $update_stmt->execute();
                $update_stmt->close();
            } else {
                // Insert new preference
                $insert_stmt = $conn->prepare("INSERT INTO preferences (user_id, breed, preference_count) VALUES (?, ?, 1)");
                $insert_stmt->bind_param("is", $user_id, $breed);
                $insert_stmt->execute();
                $insert_stmt->close();
            }
            $pref_stmt->close();
        } else {
            $error_msg = "Failed to submit the application. Please try again.";
        }
        $stmt->close();
    }
    $check_stmt->close();
}

// Fetch available pets
$pets_result = $conn->query("SELECT pet_id, name, breed, age FROM pets ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Adoption Application</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h1>Pet Adoption Portal</h1>
    <nav>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="submit_adoption_application.php">Adopt a Pet</a></li>
            <li><a href="my_applications.php">My Applications</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
</header>

<main>
    <h2>Submit Adoption Application</h2>

    <?php if ($success_msg): ?>
        <p class="success"><?= htmlspecialchars($success_msg) ?></p>
    <?php elseif ($error_msg): ?>
        <p class="error"><?= htmlspecialchars($error_msg) ?></p>
    <?php endif; ?>

    <form action="submit_adoption_application.php" method="post">
        <label for="pet_id">Select a Pet to Adopt:</label>
        <select name="pet_id" id="pet_id" required>
            <option value="">-- Choose a Pet --</option>
            <?php while ($pet = $pets_result->fetch_assoc()): ?>
                <option value="<?= $pet['pet_id'] ?>">
                    <?= htmlspecialchars($pet['name']) ?> (<?= htmlspecialchars($pet['breed']) ?>, <?= $pet['age'] ?> years)
                </option>
            <?php endwhile; ?>
        </select>
        <br><br>
        <label for="adoption_reason">Why do you want to adopt this pet?</label><br>
        <textarea name="adoption_reason" id="adoption_reason" rows="4" cols="50" required></textarea>
        <br><br>
        <button type="submit" class="button">Submit Application</button>
    </form>
</main>

</body>
</html>

<?php
$conn->close();
?>
