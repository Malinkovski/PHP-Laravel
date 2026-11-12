<?php

session_start();
require 'db.php';

// CHECK LOGIN
if (!isset($_SESSION['user'])) {
    $_SESSION['message'] = 'Немате пристап.';
    header('Location: login.php');
    exit;
}

$message = '';

if (isset($_POST['delete'])) {

    $id = $_POST['id'];

    $stmt = $db->prepare("SELECT * FROM memberships WHERE id = :id");
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);

    $membership = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    if ($membership) {

        $date = new DateTime($membership['start_date']);

        if ($membership['type'] == 'Monthly') {
            $date->modify('+1 month');
        } else {
            $date->modify('+1 year');
        }

        $today = new DateTime('today');

        if ($date >= $today) {

            $message = 'Активна членарина не може да се избрише.';

        } else {

            $stmt = $db->prepare("DELETE FROM memberships WHERE id = :id");
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmt->execute();

            $message = 'Membership deleted successfully.';  
        }
    }
}

if (isset($_POST['save'])) {

    $name = trim($_POST['name']);
    $type = $_POST['type'];
    $startDate = $_POST['start_date'];
    $price = $_POST['price'];
    $id = $_POST['id'];

    if (
        $name == '' ||
        !in_array($type, ['Monthly', 'Yearly']) ||
        $startDate == '' ||
        !is_numeric($price) ||
        $price <= 0
    ) {

        $message = 'Please enter valid information.';

    } else {

        if ($id == '') {

            $stmt = $db->prepare("
                INSERT INTO memberships (name, type, start_date, price)
                VALUES (:name, :type, :start_date, :price)
            ");

        } else {

            $stmt = $db->prepare("
                UPDATE memberships
                SET name = :name,
                    type = :type,
                    start_date = :start_date,
                    price = :price
                WHERE id = :id
            ");

            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        }

        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':type', $type, SQLITE3_TEXT);
        $stmt->bindValue(':start_date', $startDate, SQLITE3_TEXT);
        $stmt->bindValue(':price', $price, SQLITE3_FLOAT);

        $stmt->execute();

        header('Location: index.php');
        exit;
    }
}

$editMembership = null;

if (isset($_GET['edit'])) {

    $stmt = $db->prepare("SELECT * FROM memberships WHERE id = :id");
    $stmt->bindValue(':id', $_GET['edit'], SQLITE3_INTEGER);

    $editMembership = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
}

// GET ALL MEMBERSHIPS
$result = $db->query("SELECT * FROM memberships ORDER BY start_date DESC");

?>

<!DOCTYPE html>
<html>
<head>
    <title>Gym Memberships</title>
</head>
<body>

<h1>Gym Membership System</h1>

<p>
    Logged in as: <?= htmlspecialchars($_SESSION['user']) ?>
    |
    <a href="logout.php">Logout</a>
</p>

<p><?= htmlspecialchars($message) ?></p>

<hr>

<h2>Add / Update Membership</h2>

<form method="POST">

    <input type="hidden" name="id"
        value="<?= htmlspecialchars($editMembership['id'] ?? '') ?>">

    <label>Member name:</label>

    <input type="text" name="name"
        value="<?= htmlspecialchars($editMembership['name'] ?? '') ?>" required>

    <br><br>

    <label>Membership type:</label>

    <select name="type" required>

        <option value="">Select type</option>

        <option value="Monthly"
            <?= ($editMembership['type'] ?? '') == 'Monthly' ? 'selected' : '' ?>>
            Monthly
        </option>

        <option value="Yearly"
            <?= ($editMembership['type'] ?? '') == 'Yearly' ? 'selected' : '' ?>>
            Yearly
        </option>

    </select>

    <br><br>

    <label>Start date:</label>

    <input type="date" name="start_date"
        value="<?= htmlspecialchars($editMembership['start_date'] ?? '') ?>" required>

    <br><br>

    <label>Price:</label>

    <input type="number" step="any" name="price"
        value="<?= htmlspecialchars($editMembership['price'] ?? '') ?>" required>

    <br><br>

    <button type="submit" name="save">Save Membership</button>

</form>

<hr>

<h2>All Memberships</h2>

<table border="1" cellpadding="10">

    <tr>
        <th>Member Name</th>
        <th>Type</th>
        <th>Start Date</th>
        <th>Expiration Date</th>
        <th>Price</th>
        <th>Status</th>
        <th>Actions</th>
    </tr>

    <?php while ($row = $result->fetchArray(SQLITE3_ASSOC)): ?>

        <?php

        $date = new DateTime($row['start_date']);

        if ($row['type'] == 'Monthly') {
            $date->modify('+1 month');
        } else {
            $date->modify('+1 year');
        }

        $expirationDate = $date->format('Y-m-d');

        $today = new DateTime('today');

        if ($date >= $today) {
            $status = 'Active';
        } else {
            $status = 'Expired';
        }

        ?>

        <tr>

            <td><?= htmlspecialchars($row['name']) ?></td>

            <td><?= htmlspecialchars($row['type']) ?></td>

            <td><?= htmlspecialchars($row['start_date']) ?></td>

            <td><?= htmlspecialchars($expirationDate) ?></td>

            <td><?= htmlspecialchars($row['price']) ?></td>

            <td><?= $status ?></td>

            <td>

                <a href="index.php?edit=<?= $row['id'] ?>">
                    Update
                </a>

                <form method="POST">

                    <input type="hidden" name="id" value="<?= $row['id'] ?>">

                    <button type="submit" name="delete">
                        Delete
                    </button>

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
