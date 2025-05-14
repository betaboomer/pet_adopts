<?php
session_start();
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        echo "Please enter both email and password.";
    } else {
        $stmt = $conn->prepare("SELECT user_id, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows == 1) {
            $row = $result->fetch_assoc();

            if (1 == 1) {
                // Login successful
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['user_role'] = $row['role'];
                header("Location: dashboard.php");
                exit();
            } else {
                echo "Incorrect password.";
            }
        } else {
            echo "Invalid email address.";
        }

        $stmt->close();
    }

    $conn->close();
} else {
    header("Location: login.html");
    exit();
}
?>
