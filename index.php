<?php
// Amazon RDS MySQL connection settings
$host = 'mysql-single.c470eai86395.us-east-1.rds.amazonaws.com';
$db   = 'devops_class_db';
$user = 'admin';
$pass = 'Camilamours_2026!';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}

function sanitize($value)
{
    return trim(filter_var($value, FILTER_SANITIZE_STRING));
}

$action = $_REQUEST['action'] ?? '';
$message = '';
$editUser = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($action === 'create') {
        if ($name === '' || $email === '') {
            $message = 'Name and email are required.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO users (name, email) VALUES (:name, :email)');
            $stmt->execute(['name' => $name, 'email' => $email]);
            header('Location: ' . $_SERVER['PHP_SELF'] . '?message=' . urlencode('User created successfully.'));
            exit;
        }
    }

    if ($action === 'update') {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0 || $name === '' || $email === '') {
            $message = 'Invalid input for update.';
        } else {
            $stmt = $pdo->prepare('UPDATE users SET name = :name, email = :email WHERE id = :id');
            $stmt->execute(['name' => $name, 'email' => $email, 'id' => $id]);
            header('Location: ' . $_SERVER['PHP_SELF'] . '?message=' . urlencode('User updated successfully.'));
            exit;
        }
    }
}

if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    if ($id > 0) {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        header('Location: ' . $_SERVER['PHP_SELF'] . '?message=' . urlencode('User deleted successfully.'));
        exit;
    }
}

if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $editUser = $stmt->fetch();
    }
}

if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}

$users = $pdo->query('SELECT id, name, email FROM users ORDER BY id ASC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Users CRUD - Amazon RDS MySQL</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f6f6f6; }
        h1 { margin-bottom: 0.5rem; }
        .container { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        table th, table td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        table th { background: #f0f0f0; }
        form { display: grid; gap: 12px; max-width: 520px; }
        label { display: block; font-weight: bold; margin-bottom: 4px; }
        input[type="text"], input[type="email"] { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        input[type="submit"], .button { display: inline-block; padding: 10px 16px; background: #007bff; color: #fff; border: none; border-radius: 4px; text-decoration: none; cursor: pointer; }
        .button.delete { background: #dc3545; }
        .message { padding: 10px 14px; margin-bottom: 16px; background: #e9ffe9; border: 1px solid #b2d8b2; border-radius: 4px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Users CRUD</h1>
    <?php if ($message): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>

    <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?action=<?= $editUser ? 'update' : 'create' ?>">
        <?php if ($editUser): ?>
            <input type="hidden" name="id" value="<?= $editUser['id'] ?>">
        <?php endif; ?>
        <div>
            <label for="name">Name</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($editUser['name'] ?? '') ?>" required>
        </div>
        <div>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($editUser['email'] ?? '') ?>" required>
        </div>
        <input type="submit" value="<?= $editUser ? 'Update User' : 'Create User' ?>">
        <?php if ($editUser): ?>
            <a class="button" href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">Cancel</a>
        <?php endif; ?>
    </form>

    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php if (count($users) === 0): ?>
            <tr><td colspan="4">No users found.</td></tr>
        <?php else: ?>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <a class="button" href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?action=edit&id=<?= $user['id'] ?>">Edit</a>
                        <a class="button delete" href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?action=delete&id=<?= $user['id'] ?>" onclick="return confirm('Delete this user?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
        <tfoot>
        <tr>
            <td colspan="2">Total users: <?= count($users) ?></td>
            <td colspan="2">
                <?php
                // IMDSv2: First get a session token, then use it to fetch metadata
                function get_imds_token() {
                    $ctx = stream_context_create([
                        'http' => [
                            'method' => 'PUT',
                            'header' => "X-aws-ec2-metadata-token-ttl-seconds: 21600\r\n",
                            'timeout' => 2,
                        ]
                    ]);
                    return @file_get_contents('http://169.254.169.254/latest/api/token', false, $ctx);
                }

                function get_metadata($path, $token) {
                    $ctx = stream_context_create([
                        'http' => [
                            'method' => 'GET',
                            'header' => "X-aws-ec2-metadata-token: $token\r\n",
                            'timeout' => 2,
                        ]
                    ]);
                    return @file_get_contents('http://169.254.169.254/latest/meta-data/' . $path, false, $ctx);
                }

                $token = get_imds_token();
                if ($token) {
                    $instance_id       = get_metadata('instance-id', $token);
                    $public_ip         = get_metadata('public-ipv4', $token);
                    $availability_zone = get_metadata('placement/availability-zone', $token);

                    if ($instance_id && $public_ip && $availability_zone) {
                        echo "Instance ID: " . htmlspecialchars($instance_id) . "<br>";
                        echo "Public IP: "   . htmlspecialchars($public_ip)   . "<br>";
                        echo "AZ: "          . htmlspecialchars($availability_zone);
                    } else {
                        echo "Metadata not available (not running on EC2)";
                    }
                } else {
                    echo "Metadata not available (not running on EC2)";
                }
                ?>
            </td>
        </tr>
        </tfoot>
    </table>
</div>
</body>
</html>
