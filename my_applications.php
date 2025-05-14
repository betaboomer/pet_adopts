<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

require 'config.php';
$user_id = $_SESSION['user_id'];

// Fetch user's adoption applications
$sql = "SELECT aa.application_date, aa.status, p.name AS pet_name, p.breed, p.age
        FROM adoption_applications aa
        LEFT JOIN pets p ON aa.pet_id = p.pet_id
        WHERE aa.user_id = ?
        ORDER BY aa.application_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch user's questions and responses
$q_sql = "SELECT question, response, created_at FROM questions WHERE user_id = ? ORDER BY created_at DESC";
$q_stmt = $conn->prepare($q_sql);
$q_stmt->bind_param("i", $user_id);
$q_stmt->execute();
$q_result = $q_stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Updates - Pet Adoption Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h1>Pet Adoption Portal</h1>
    <nav>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <li><a href="my_applications.php">Updates</a></li>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="submit_adoption_application.php">Adopt a Pet</a></li>
                <li><a href="my_applications.php">Updates</a></li>
                <li><a href="logout.php">Logout</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>
<main>
    <h2>Updates</h2>
    <?php if ($result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Pet Name</th>
                    <th>Breed</th>
                    <th>Age</th>
                    <th>Application Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['pet_name'] ?? 'Deleted Pet') ?></td>
                        <td><?= htmlspecialchars($row['breed'] ?? '-') ?></td>
                        <td><?= isset($row['age']) ? htmlspecialchars($row['age']) . ' years' : '-' ?></td>
                        <td><?= htmlspecialchars($row['application_date']) ?></td>
                        <td><?= htmlspecialchars($row['status']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>You have not submitted any adoption applications yet.</p>
    <?php endif; ?>

    <h2>My Questions & Responses</h2>
    <?php if ($q_result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Question</th>
                    <th>Response</th>
                    <th>Asked At</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($q = $q_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($q['question']) ?></td>
                        <td><?= $q['response'] ? htmlspecialchars($q['response']) : '<em>Pending</em>' ?></td>
                        <td><?= htmlspecialchars($q['created_at']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>You have not asked any questions yet.</p>
    <?php endif; ?>
</main>
</body>
</html>
<?php
$stmt->close();
$q_stmt->close();
$conn->close();
?>
