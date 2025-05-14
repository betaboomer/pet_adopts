<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

require 'config.php';

// Handle Add Pet
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = $_POST['name'];
    $breed = $_POST['breed'];
    $age = isset($_POST['age']) ? intval($_POST['age']) : null;
    $status = $_POST['status'];  // New field
    $gender = $_POST['gender'];  // New field
    $color = $_POST['color'];    // New field
    $description = $_POST['description']; // New field

    if (!empty($name) && !empty($breed)) {
        $stmt = $conn->prepare("INSERT INTO pets (name, breed, age, status, gender, color, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssissss", $name, $breed, $age, $status, $gender, $color, $description);
        $stmt->execute();
        $stmt->close();
        header("Location: update_pets.php");
        exit();
    }
}

// Handle Delete Pet
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $pet_id = intval($_GET['id']);
    // First, delete all adoption applications for this pet
    $del_apps = $conn->prepare("DELETE FROM adoption_applications WHERE pet_id = ?");
    $del_apps->bind_param("i", $pet_id);
    $del_apps->execute();
    $del_apps->close();
    // Now delete the pet
    $stmt = $conn->prepare("DELETE FROM pets WHERE pet_id = ?");
    $stmt->bind_param("i", $pet_id);
    $stmt->execute();
    $stmt->close();
    header("Location: update_pets.php");
    exit();
}

// Handle Update Pet
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'update') {
    $pet_id = intval($_POST['id']);
    $name = $_POST['name'];
    $breed = $_POST['breed'];
    $age = isset($_POST['age']) ? intval($_POST['age']) : null;
    $status = $_POST['status'];  // New field
    $gender = $_POST['gender'];  // New field
    $color = $_POST['color'];    // New field
    $description = $_POST['description']; // New field

    if (!empty($name) && !empty($breed)) {
        $stmt = $conn->prepare("UPDATE pets SET name = ?, breed = ?, age = ?, status = ?, gender = ?, color = ?, description = ? WHERE pet_id = ?");
        $stmt->bind_param("ssissssi", $name, $breed, $age, $status, $gender, $color, $description, $pet_id);
        $stmt->execute();
        $stmt->close();
        header("Location: update_pets.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Pets - Pet Adoption Portal (Admin)</title>
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
    <h2>Manage Pet Listings</h2>

    <?php
    // If editing, fetch existing data
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])):
        $edit_id = intval($_GET['id']);
        $result = $conn->query("SELECT * FROM pets WHERE pet_id = $edit_id");
        if ($result && $result->num_rows > 0):
            $pet = $result->fetch_assoc();
    ?>
    <section>
        <h3>Edit Pet</h3>
        <form method="post">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= $pet['pet_id'] ?>">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" name="name" value="<?= htmlspecialchars($pet['name']) ?>" required>
            </div>
            <div class="form-group">
                <label for="breed">Breed:</label>
                <input type="text" name="breed" value="<?= htmlspecialchars($pet['breed']) ?>" required>
            </div>
            <div class="form-group">
                <label for="age">Age:</label>
                <input type="number" name="age" value="<?= htmlspecialchars($pet['age']) ?>">
            </div>
            <div class="form-group">
                <label for="status">Status:</label>
                <select name="status" required>
                    <option value="Available" <?= $pet['status'] == 'Available' ? 'selected' : '' ?>>Available</option>
                    <option value="Not Available" <?= $pet['status'] == 'Not Available' ? 'selected' : '' ?>>Not Available</option>
                </select>
            </div>
            <div class="form-group">
                <label for="gender">Gender:</label>
                <select name="gender" required>
                    <option value="Male" <?= $pet['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= $pet['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
                </select>
            </div>
            <div class="form-group">
                <label for="color">Color:</label>
                <input type="text" name="color" value="<?= htmlspecialchars($pet['color']) ?>">
            </div>
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea name="description" rows="3"><?= htmlspecialchars($pet['description']) ?></textarea>
            </div>
            <button type="submit" class="button">Update Pet</button>
        </form>
    </section>
    <?php
        endif;
    else:
    ?>
    <section>
        <h3>Add New Pet</h3>
        <form method="post">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label for="breed">Breed:</label>
                <input type="text" name="breed" required>
            </div>
            <div class="form-group">
                <label for="age">Age:</label>
                <input type="number" name="age">
            </div>
            <div class="form-group">
                <label for="status">Status:</label>
                <select name="status" required>
                    <option value="Available">Available</option>
                    <option value="Not Available">Not Available</option>
                </select>
            </div>
            <div class="form-group">
                <label for="gender">Gender:</label>
                <select name="gender" required>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>
            <div class="form-group">
                <label for="color">Color:</label>
                <input type="text" name="color">
            </div>
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea name="description" rows="3"></textarea>
            </div>
            <button type="submit" class="button">Add Pet</button>
        </form>
    </section>
    <?php endif; ?>

    <section>
        <h3>Existing Pets</h3>
        <?php
        $pets = $conn->query("SELECT pet_id, name, breed FROM pets");
        if ($pets && $pets->num_rows > 0) {
            echo "<table><thead><tr><th>Pet ID</th><th>Name</th><th>Breed</th><th>Actions</th></tr></thead><tbody>";
            while ($row = $pets->fetch_assoc()) {
                echo "<tr>
                        <td>{$row['pet_id']}</td>
                        <td>" . htmlspecialchars($row['name']) . "</td>
                        <td>" . htmlspecialchars($row['breed']) . "</td>
                        <td>
                            <a href='update_pets.php?action=edit&id={$row['pet_id']}'>Edit</a> | 
                            <a href='update_pets.php?action=delete&id={$row['pet_id']}' onclick=\"return confirm('Are you sure?')\">Delete</a>
                        </td>
                      </tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<p>No pets available.</p>";
        }
        ?>
    </section>
</main>
</body>
</html>

<?php
$conn->close();
?>
