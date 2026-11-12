<?php

session_start();
require 'db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];

    if ($username == '') {
        $message = 'Username is required.';

    } elseif (strlen($password) < 6) {
        $message = 'Password must have at least 6 characters.';

    } elseif ($password != $confirm) {
        $message = 'Passwords do not match.';

    } else {

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("
            INSERT INTO users (username, password)
            VALUES (:username, :password)
        ");

        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $stmt->bindValue(':password', $hashedPassword, SQLITE3_TEXT);

        try {
            $stmt->execute();
            $message = 'Registration successful. <a href="login.php">Login</a>';
        } catch (Exception $e) {
            $message = 'Username already exists.';
        }
    }
}

?>

<h1>Register</h1>

<form method="POST">

    <input type="text" name="username" placeholder="Username" required>

    <input type="password" name="password" placeholder="Password" required>

    <input type="password" name="confirm" placeholder="Confirm password" required>

    <button type="submit">Register</button>

</form>

<p><?= $message ?></p>

<a href="login.php">Already have an account? Login</a>
