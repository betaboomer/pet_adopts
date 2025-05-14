<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

require 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pet_id = $_POST['pet_id'];
    $question = $_POST['question'];
    $user_id = $_SESSION['user_id'];

    if (!empty($pet_id) && !empty($question)) {
        $stmt = $conn->prepare("INSERT INTO questions (user_id, pet_id, question, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $user_id, $pet_id, $question);

        if ($stmt->execute()) {
            echo "<p>Your question has been submitted.</p>";
        } else {
            echo "<p>Error submitting your question: " . $conn->error . "</p>";
        }

        $stmt->close();
    } else {
        echo "<p>Please select a pet and enter your question.</p>";
    }
}

// Fetch list of pets for the dropdown using correct column names
$pets_result = $conn->query("SELECT pet_id, name FROM pets");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ask a Question - Pet Adoption Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h1>Pet Adoption Portal</h1>
    <nav>
        <ul>
            <li><a href="index.html">Home</a></li>
            <li><a href="search_pets.php">Search Pets</a></li>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
</header>

<main>
    <h2>Ask a Question about a Pet</h2>
    <form method="post" action="ask_question.php">
        <div class="form-group">
            <label for="pet_id">Select a Pet:</label>
            <select id="pet_id" name="pet_id" required>
                <option value="">-- Select Pet --</option>
                <?php
                if ($pets_result->num_rows > 0) {
                    while ($pet_row = $pets_result->fetch_assoc()) {
                        echo "<option value='" . $pet_row['pet_id'] . "'>" . htmlspecialchars($pet_row['name']) . "</option>";
                    }
                } else {
                    echo "<option value=''>No pets available</option>";
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="question">Your Question:</label>
            <textarea id="question" name="question" rows="5" required></textarea>
        </div>
        <div class="form-group">
            <button type="submit" class="button">Submit Question</button>
        </div>
    </form>
</main>

</body>
</html>

<?php
$conn->close();
?>
