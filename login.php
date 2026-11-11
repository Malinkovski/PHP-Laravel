<?php

session_start();
require 'db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $db->prepare("
        SELECT * FROM users WHERE username = :username
    ");

    $stmt->bindValue(':username', $username, SQLITE3_TEXT);

    $user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    if ($user && password_verify($password, $user['password'])) {

        session_regenerate_id(true);

        $_SESSION['user'] = $user['username'];

        header('Location: index.php');
        exit;

    } else {

        $message = 'Invalid username or password.';
    }
}

?>

<h1>Login</h1>

<form method="POST">

    <input type="text" name="username" placeholder="Username" required>

    <input type="password" name="password" placeholder="Password" required>

    <button type="submit">Login</button>

</form>

<p><?= $message ?></p>

<a href="register.php">Create an account</a>
