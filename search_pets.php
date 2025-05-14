<?php
session_start();
require 'config.php';

// Fetch pets based on search criteria
$breed = isset($_GET['breed']) ? trim($_GET['breed']) : '';
if ($breed !== '') {
    $stmt = $conn->prepare("SELECT * FROM pets WHERE breed LIKE ?");
    $like_breed = "%$breed%";
    $stmt->bind_param("s", $like_breed);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $result = $conn->query("SELECT * FROM pets");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Pets - Pet Adoption Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Pet Adoption Portal</h1>
        <nav>
            <ul>
                <li><a href="index.html">Home</a></li>
                <li><a href="search_pets.php">Search Pets</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.html">Login</a></li>
                    <li><a href="register.html">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <h2>Search Available Pets</h2>
        <section class="search-options">
            <form method="GET" action="search_pets.php">
                <div class="form-group">
                    <label for="breed">Breed:</label>
                    <input type="text" id="breed" name="breed">
                </div>
                <button type="submit" class="button">Search</button>
            </form>
        </section>
        <section class="pet-listings">
            <?php
            if ($result->num_rows > 0) {
                echo "<div class='pet-grid'>";
                while ($row = $result->fetch_assoc()) {
                    echo "<div class='pet-card'>";
                    echo "<img src='images/pepe.jpg' alt='" . htmlspecialchars($row['name']) . "'>";
                    echo "<h4>" . htmlspecialchars($row['name']) . "</h4>";
                    echo "<p>Breed: " . htmlspecialchars($row['breed']) . "</p>";
                    echo "<p>Age: " . htmlspecialchars($row['age']) . "</p>";
                    echo "<a href='pet_details.php?id=" . $row['pet_id'] . "'>View Details</a>"; // You'll need a pet_details.php page
                    echo "</div>";
                }
                echo "</div>";
            } else {
                echo "<p>No pets found matching your criteria.</p>";
            }
            $conn->close();
            ?>
        </section>
    </main>


</body>
</html>