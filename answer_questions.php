<?php
session_start();
require 'config.php';

// Handle submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['question_id']) && isset($_POST['answer'])) {
    $question_id = $_POST['question_id'];
    $answer = $_POST['answer'];

    $stmt = $conn->prepare("UPDATE questions SET response = ?, respond_date = NOW() WHERE id = ?");
    $stmt->bind_param("si", $answer, $question_id);

    if ($stmt->execute()) {
        echo "<p>Answer submitted successfully.</p>";
    } else {
        echo "<p>Error submitting answer: " . $conn->error . "</p>";
    }

    $stmt->close();
}

// Fetch unanswered questions
$sql = "SELECT q.id, u.name AS user_name, p.name AS pet_name, q.question, q.response, q.question_date
        FROM questions q
        JOIN users u ON q.user_id = u.user_id
        JOIN pets p ON q.pet_id = p.pet_id
        WHERE q.response IS NULL";


$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Answer Questions - Pet Adoption Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Answer Questions</h1>

    <?php if ($result->num_rows > 0): ?>
        <table>
            <tr>
                <th>User</th>
                <th>Pet</th>
                <th>Question</th>
                <th>Asked At</th>
                <th>Answer</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['user_name']) ?></td>
                    <td><?= htmlspecialchars($row['pet_name']) ?></td>
                    <td><?= htmlspecialchars($row['question']) ?></td>
                    <td><?= $row['question_date'] ?></td>
                    <td>
                        <form method="post" action="answer_questions.php">
                            <input type="hidden" name="question_id" value="<?= $row['id'] ?>">
                            <textarea name="answer" rows="3" required></textarea><br>
                            <button type="submit">Submit</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No unanswered questions at the moment.</p>
    <?php endif; ?>

</body>
</html>

<?php
$conn->close();
?>
