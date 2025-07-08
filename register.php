<?php
// 启动session，必须放在最前面
session_start();
// 引入数据库配置
include 'config.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') { // 如果有表单提交
    // 1. 获取表单数据并去除两端空格
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $email = trim($_POST['email']);
    $captcha = isset($_POST['captcha']) ? trim($_POST['captcha']) : '';

    // 2. 表单校验
    if ($username === '' || $password === '' || $email === '') {
        $msg = "用户名、密码、邮箱都不能为空！";
    }
    // 3. 校验邮箱格式
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "邮箱格式错误！";
    }
    // 4. 校验验证码
    elseif ($captcha === '') {
        $msg = "验证码不能为空！";
    } elseif (!isset($_SESSION['captcha']) || strtolower($captcha) !== strtolower($_SESSION['captcha'])) {
        $msg = "验证码错误！";
    } else {
        // 5. 检查用户名或邮箱是否重复
        $stmt = $conn->prepare("SELECT id FROM user WHERE username=? OR email=?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $msg = "用户名或邮箱已被注册！";
        } else {
            // 6. 密码加密后写入数据库
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO user (username, password, email) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $hash, $email);
            if ($stmt->execute()) {
                // 7. 注册成功，自动登录并跳转首页
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['username'] = $username;
                header("Location: KupaiShop.php");
                exit;
            } else {
                $msg = "注册失败，请重试！";
            }
        }
        $stmt->close();
    }
    // 8. 每次注册后销毁验证码
    unset($_SESSION['captcha']);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>注册 - 酷牌商城 KupaiShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<!-- 顶部导航栏，仅显示logo -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="KupaiShop.php">酷牌商城 KupaiShop</a>
    </div>
</nav>
<div class="container mt-5" style="max-width: 400px;">
    <h2 class="mb-4">注册</h2>
    <!-- 提示信息 -->
    <?php if($msg): ?>
        <div class="alert alert-danger"><?php echo $msg; ?></div>
    <?php endif; ?>
    <!-- 注册表单 -->
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
            <label for="email" class="form-label">邮箱</label>
            <input type="email" class="form-control" id="email" name="email" required autocomplete="off">
        </div>
        <div class="mb-3">
            <label for="captcha" class="form-label">验证码</label>
            <div class="d-flex align-items-center">
                <input type="text" class="form-control me-2" id="captcha" name="captcha" maxlength="4" required autocomplete="off">
                <!-- 验证码图片，点击可刷新 -->
                <img src="captcha.php" onclick="this.src='captcha.php?'+Math.random();" title="看不清？点击换一张" style="cursor:pointer;height:36px;">
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100">注册</button>
    </form>
    <div class="mt-3 text-center">
        已有账号？<a href="login.php">去登录</a>
    </div>
</div>
<footer class="bg-primary text-white text-center py-3 mt-4">
    &copy; 2025 酷牌商城 KupaiShop. 保留所有权利。
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>