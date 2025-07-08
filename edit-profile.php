<?php
session_start();
include 'config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$user_id = $_SESSION['user_id'];

$msg = "";

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $change_pwd = !empty($_POST['new_password']);
    $success = true;

    // 邮箱修改
    $sql = "UPDATE `user` SET email='$email' WHERE id=$user_id";
    if (!$conn->query($sql)) {
        $success = false;
        $msg .= "邮箱修改失败: " . $conn->error . "<br>";
    }

    // 密码修改
    if ($change_pwd) {
        $old_pwd = $_POST['old_password'];
        $new_pwd = $_POST['new_password'];
        $confirm_pwd = $_POST['confirm_password'];

        // 检查旧密码
        $user = $conn->query("SELECT password FROM `user` WHERE id=$user_id")->fetch_assoc();
        if (!password_verify($old_pwd, $user['password'])) {
            $success = false;
            $msg .= "原密码不正确！<br>";
        } elseif ($new_pwd !== $confirm_pwd) {
            $success = false;
            $msg .= "新密码两次输入不一致！<br>";
        } elseif (strlen($new_pwd) < 6) {
            $success = false;
            $msg .= "新密码长度不能少于6位！<br>";
        } else {
            $hash = password_hash($new_pwd, PASSWORD_DEFAULT);
            if (!$conn->query("UPDATE `user` SET password='$hash' WHERE id=$user_id")) {
                $success = false;
                $msg .= "密码修改失败: " . $conn->error . "<br>";
            }
        }
    }

    if ($success) {
        echo "<script>alert('信息修改成功');location.href='user.php';</script>";
        exit;
    }
}

// 查询原信息
$sql = "SELECT username, email FROM `user` WHERE id=$user_id";
$result = $conn->query($sql);
$user = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>修改信息</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4" style="max-width:480px;">
    <h3>修改信息</h3>
    <?php if (!empty($msg)) echo "<div class='alert alert-danger'>$msg</div>"; ?>
    <form method="post" autocomplete="off">
        <div class="mb-2">用户名：<?php echo htmlspecialchars($user['username']); ?></div>
        <div class="mb-2">
            <label>邮箱：<input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required class="form-control"></label>
        </div>
        <hr>
        <div class="mb-2"><b>如需修改密码，请填写以下内容：</b></div>
        <div class="mb-2">
            <label>原密码：<input type="password" name="old_password" class="form-control" autocomplete="off"></label>
        </div>
        <div class="mb-2">
            <label>新密码：<input type="password" name="new_password" class="form-control" autocomplete="off"></label>
        </div>
        <div class="mb-2">
            <label>确认新密码：<input type="password" name="confirm_password" class="form-control" autocomplete="off"></label>
        </div>
        <button type="submit" class="btn btn-success">保存</button>
        <a href="user.php" class="btn btn-secondary">返回</a>
    </form>
</div>
</body>
</html>
