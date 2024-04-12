<?php
function generateRandomString($length = 16) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

// 生成16位的随机字符串
$random_name = generateRandomString();

// 加密随机字符串
$encrypted_name = md5($random_name);

// 拼接数据库文件路径
$db_file = $encrypted_name . '.db';

// 创建 SQLite 数据库
$db = new SQLite3($db_file);

// 创建 users 表格，如果不存在的话
$db->exec('CREATE TABLE IF NOT EXISTS users (username VARCHAR PRIMARY KEY, password VARCHAR)');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    // 用户输入的用户名和密码
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // 添加用户到数据库
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT OR IGNORE INTO users (username, password) VALUES (:username, :password)');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $stmt->bindValue(':password', $hashed_password, SQLITE3_TEXT);
    $stmt->execute();

    // 安装完成后，重命名当前文件
    $new_file_name = 'installed_' . $encrypted_name . '.php';
    rename(__FILE__, $new_file_name);

    // 创建成功后重定向到登录页面
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>安装</title>
</head>
<body>
    <h2>创建管理员账户</h2>
    <form method="post">
        <label for="username">用户名:</label>
        <input type="text" id="username" name="username" required>
        <br><br>
        <label for="password">密码:</label>
        <input type="password" id="password" name="password" required>
        <br><br>
        <button type="submit" name="submit">创建</button>
    </form>
</body>
</html>
