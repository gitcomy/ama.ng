<?php
session_start();

// 用户注销
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// 用户登录验证
function authenticate($username, $password) {
    // 连接到 SQLite 数据库
    $db = new SQLite3('users.db');

    // 查询用户
    $query = $db->prepare('SELECT * FROM users WHERE username=:username');
    $query->bindValue(':username', $username, SQLITE3_TEXT);
    $result = $query->execute();

    // 验证用户凭据
    if ($row = $result->fetchArray()) {
        if (password_verify($password, $row['password'])) {
            return true; // 验证成功
        }
    }
    return false; // 验证失败
}

// 用户登录处理
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (authenticate($username, $password)) {
        $_SESSION['username'] = $username; // 记录登录状态
        header("Location: ".$_SERVER['PHP_SELF']); // 重新加载页面以刷新登录状态
        exit();
    } else {
        $login_error = "用户名或密码不正确";
    }
}

// 如果用户已登录，则显示欢迎消息和注销按钮
if (isset($_SESSION['username'])) {
    echo "<p>欢迎, ".$_SESSION['username']."!</p>";
    echo "<form method='post'><button type='submit' name='logout'>注销</button></form>";

    // 选择 Yourls API
    echo "<h2>选择 Yourls API</h2>";
    echo "<form method='post'>";
    echo "<label for='api'>选择 API:</label>";
    echo "<select id='api' name='api'>";
    echo "<option value='http://yourls_site1.com/yourls-api'>Yourls API 1</option>";
    echo "<option value='http://yourls_site2.com/yourls-api'>Yourls API 2</option>";
    echo "</select>";
    echo "<br><br>";

    // 生成短网址
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate'])) {
        $long_url = $_POST['long_url'];
        $api_url = $_POST['api'];
        
        // 使用 Yourls API 生成短网址
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'url' => $long_url,
            'signature' => 'your_yourls_signature', // 请替换为你的 Yourls API 签名
            'action' => 'shorturl',
            'format' => 'json'
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $short_url = json_decode($response)->shorturl;
        echo "<p>短网址: <span id='short-url'>$short_url</span> <button onclick='copyToClipboard()'>复制</button></p>";
    }

    // 显示生成短网址的表单
    echo "<h2>生成短网址</h2>";
    echo "<form method='post'>";
    echo "<input type='text' name='long_url' placeholder='输入长网址' required>";
    echo "<br><br>";
    echo "<button type='submit' name='generate'>生成</button>";
    echo "</form>";
} else {
    // 如果用户未登录，则显示登录表单
?>
<!DOCTYPE html>
<html>
<head>
    <title>登录</title>
    <script>
        function copyToClipboard() {
            var copyText = document.getElementById("short-url");
            var range = document.createRange();
            range.selectNode(copyText);
            window.getSelection().removeAllRanges();
            window.getSelection().addRange(range);
            document.execCommand("copy");
            window.getSelection().removeAllRanges();
            alert("已复制到剪贴板: " + copyText.innerText);
        }
    </script>
</head>
<body>
    <h2>登录</h2>
    <form method="post">
        <label for="username">用户名:</label>
        <input type="text" id="username" name="username" required>
        <br><br>
        <label for="password">密码:</label>
        <input type="password" id="password" name="password" required>
        <br><br>
        <button type="submit" name="login">登录</button>
        <?php if(isset($login_error)) echo "<p>$login_error</p>"; ?>
    </form>
</body>
</html>
<?php
}
?>
