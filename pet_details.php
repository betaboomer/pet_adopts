<?php
session_start();
require 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p>Invalid pet ID.</p>";
    exit();
}
$pet_id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM pets WHERE pet_id = ?");
$stmt->bind_param("i", $pet_id);
$stmt->execute();
$result = $stmt->get_result();
$pet = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$pet) {
    echo "<p>Pet not found.</p>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pet['name']) ?> - Pet Details</title>
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
    <h2><?= htmlspecialchars($pet['name']) ?> - Details</h2>
    <div class="pet-card">
        <img src="images/pepe.jpg" alt="<?= htmlspecialchars($pet['name']) ?>" style="width:500px; height:auto;">
        <p><strong>Breed:</strong> <?= htmlspecialchars($pet['breed']) ?></p>
        <p><strong>Age:</strong> <?= htmlspecialchars($pet['age']) ?></p>
        <p><strong>Gender:</strong> <?= htmlspecialchars($pet['gender']) ?></p>
        <p><strong>Color:</strong> <?= htmlspecialchars($pet['color']) ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars($pet['status']) ?></p>
        <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($pet['description'])) ?></p>
    </div>
    <a href="search_pets.php" class="button">Back to Search</a>
</main>
</body>
</html>
