
<?php

session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$message = '';

if (isset($_POST['delete'])) {

    $id = $_POST['id'];

    $stmt = $db->prepare("SELECT amount FROM expenses WHERE id = :id");
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);

    $expense = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    if ($expense) {

        if ($expense['amount'] > 100) {

            $message = 'Expenses over 100 cannot be deleted.';

        } else {

            $stmt = $db->prepare("DELETE FROM expenses WHERE id = :id");
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmt->execute();

            $message = 'Expense deleted successfully.';
        }
    }
}

if (isset($_POST['save'])) {

    $name = trim($_POST['name']);
    $date = $_POST['date'];
    $amount = $_POST['amount'];
    $payment = $_POST['payment'];
    $id = $_POST['id'];

    if (
        $name == '' ||
        $date == '' ||
        !is_numeric($amount) ||
        $amount <= 0 ||
        !in_array($payment, ['Cash', 'Card'])
    ) {

        $message = 'Please enter valid expense information.';

    } else {

        if ($id == '') {

            $stmt = $db->prepare("
                INSERT INTO expenses (name, date, amount, payment)
                VALUES (:name, :date, :amount, :payment)
            ");

        } else {

            $stmt = $db->prepare("
                UPDATE expenses
                SET name = :name,
                    date = :date,
                    amount = :amount,
                    payment = :payment
                WHERE id = :id
            ");

            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        }

        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':date', $date, SQLITE3_TEXT);
        $stmt->bindValue(':amount', $amount, SQLITE3_FLOAT);
        $stmt->bindValue(':payment', $payment, SQLITE3_TEXT);

        $stmt->execute();

        header('Location: index.php');
        exit;
    }
}

$editExpense = null;

if (isset($_GET['edit'])) {

    $stmt = $db->prepare("SELECT * FROM expenses WHERE id = :id");
    $stmt->bindValue(':id', $_GET['edit'], SQLITE3_INTEGER);

    $editExpense = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
}

$result = $db->query("SELECT * FROM expenses ORDER BY date DESC");

?>

<!DOCTYPE html>
<html>
<head>
    <title>Expense Management</title>
</head>
<body>

<h1>Expense Management System</h1>

<p>
    Logged in as: <?= htmlspecialchars($_SESSION['user']) ?>
    |
    <a href="logout.php">Logout</a>
</p>

<p><?= htmlspecialchars($message) ?></p>

<hr>

<h2>Add / Update Expense</h2>

<form method="POST">

    <input type="hidden" name="id"
        value="<?= htmlspecialchars($editExpense['id'] ?? '') ?>">

    <label>Expense name:</label>
    <input type="text" name="name"
        value="<?= htmlspecialchars($editExpense['name'] ?? '') ?>" required>

    <br><br>

    <label>Date:</label>
    <input type="date" name="date"
        value="<?= htmlspecialchars($editExpense['date'] ?? '') ?>" required>

    <br><br>

    <label>Amount:</label>
    <input type="number" step="any" name="amount"
        value="<?= htmlspecialchars($editExpense['amount'] ?? '') ?>" required>

    <br><br>

    <label>Payment method:</label>

    <select name="payment" required>
        <option value="">Select payment</option>

        <option value="Cash"
            <?= ($editExpense['payment'] ?? '') == 'Cash' ? 'selected' : '' ?>>
            Cash
        </option>

        <option value="Card"
            <?= ($editExpense['payment'] ?? '') == 'Card' ? 'selected' : '' ?>>
            Card
        </option>
    </select>

    <br><br>

    <button type="submit" name="save">Save Expense</button>

</form>

<hr>

<h2>All Expenses</h2>

<table border="1" cellpadding="10">

    <tr>
        <th>Name</th>
        <th>Date</th>
        <th>Amount</th>
        <th>Payment</th>
        <th>Actions</th>
    </tr>

    <?php while ($row = $result->fetchArray(SQLITE3_ASSOC)): ?>

        <tr>

            <td><?= htmlspecialchars($row['name']) ?></td>

            <td><?= htmlspecialchars($row['date']) ?></td>

            <td><?= htmlspecialchars($row['amount']) ?></td>

            <td><?= htmlspecialchars($row['payment']) ?></td>

            <td>

                <a href="index.php?edit=<?= $row['id'] ?>">
                    Update
                </a>

                <form method="POST">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <button type="submit" name="delete">Delete</button>
                </form>

            </td>

        </tr>

    <?php endwhile; ?>

</table>

</body>
</html>

<?php
$db->close();
?>
