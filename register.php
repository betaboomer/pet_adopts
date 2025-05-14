<?php
session_start();
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['Name'];
    $email = $_POST['Email'];
    $password = $_POST['Password'];
    $phone = $_POST['Phone'];
    $address = $_POST['Address'];

    // Basic validation
    if (empty($name) || empty($email) || empty($password)) {
        echo "Name, email, and password are required.";
    } else {
        $role = 'adopter'; // Default role
        $score = 100;

        // Use plain password (only for testing)
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, address, role, score) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssi", $name, $email, $password, $phone, $address, $role, $score);

        if ($stmt->execute()) {
            echo "✅ Registration successful! <a href='login.html'>Login here</a>.";
        } else {
            echo "❌ Error during registration: " . $conn->error;
        }

        $stmt->close();
    }

    $conn->close();
} else {
    header("Location: register.html");
    exit();
}
?>
