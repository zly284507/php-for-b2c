<?php
// 引入数据库配置
include 'config.php';
// $msg 用于存放错误或提示信息，便于页面显示
$msg = '';
// 启动session，便于登录状态和验证码校验
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') { // 如果是POST请求，说明用户提交了登录表单
    // 1. 获取表单数据，并去除两端空格
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $captcha = isset($_POST['captcha']) ? trim($_POST['captcha']) : '';

    // 2. 表单校验（用户名和密码不能为空）
    if ($username === '' || $password === '') {
        $msg = "用户名和密码不能为空！";
    }
    // 3. 校验验证码是否填写
    elseif ($captcha === '') {
        $msg = "验证码不能为空！";
    }
    // 4. 校验验证码是否正确（不区分大小写）
    elseif (!isset($_SESSION['captcha']) || strtolower($captcha) !== strtolower($_SESSION['captcha'])) {
        $msg = "验证码错误！";
    }
    // 5. 通过校验，开始查数据库验证用户
    else {
        // 用prepare防止SQL注入
        $stmt = $conn->prepare("SELECT id, password FROM user WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        // 6. 检查用户是否存在
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($uid, $hash);
            $stmt->fetch();
            // 7. 验证密码（数据库存的是hash）
            if (password_verify($password, $hash)) {
                // 8. 登录成功，保存用户id和用户名到session
                $_SESSION['user_id'] = $uid;
                $_SESSION['username'] = $username;
                header("Location: KupaiShop.php"); // 跳转到首页
                exit;
            } else {
                $msg = "密码错误！";
            }
        } else {
            $msg = "用户不存在！";
        }
        $stmt->close();
    }
    // 9. 每次登录尝试后销毁验证码，防止重复利用
    unset($_SESSION['captcha']);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>登录 - 酷牌商城 KupaiShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<!-- 顶部导航，只显示商城logo -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="KupaiShop.php">酷牌商城 KupaiShop</a>
    </div>
</nav>
<div class="container mt-5" style="max-width: 400px;">
    <h2 class="mb-4">登录</h2>
    <!-- 若有提示信息，显示出来 -->
    <?php if($msg): ?>
        <div class="alert alert-danger"><?php echo $msg; ?></div>
    <?php endif; ?>
    <!-- 登录表单 -->
    <form method="post" autocomplete="off">
        <div class="mb-3">
            <label for="username" class="form-label">用户名</label>
            <input type="text" class="form-control" id="username" name="username" required autocomplete="off">
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">密码</label>
            <input type="password" class="form-control" id="password" name="password" required autocomplete="off">
        </div>
        <div class="mb-3">
            <label for="captcha" class="form-label">验证码</label>
            <div class="d-flex align-items-center">
                <!-- 输入验证码 -->
                <input type="text" class="form-control me-2" id="captcha" name="captcha" maxlength="4" required autocomplete="off">
                <!-- 显示验证码图片，点击可刷新 -->
                <img src="captcha.php" onclick="this.src='captcha.php?'+Math.random();" title="看不清？点击换一张" style="cursor:pointer;height:36px;">
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100">登录</button>
    </form>
    <div class="mt-3 text-center">
        还没有账号？<a href="register.php">立即注册</a>
    </div>
</div>
<footer class="bg-primary text-white text-center py-3 mt-4">
    &copy; 2025 酷牌商城 KupaiShop. 保留所有权利。
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>