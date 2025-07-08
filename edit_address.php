<?php
session_start();
include 'config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$user_id = $_SESSION['user_id'];
$id = intval($_GET['id'] ?? 0);

// 查询原地址
$sql = "SELECT * FROM address WHERE id=$id AND user_id=$user_id";
$addr = $conn->query($sql)->fetch_assoc();
if (!$addr) { echo "地址不存在"; exit; }

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $province = $conn->real_escape_string($_POST['province']);
    $city = $conn->real_escape_string($_POST['city']);
    $detail = $conn->real_escape_string($_POST['detail']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;

    if ($is_default) {
        $conn->query("UPDATE address SET is_default=0 WHERE user_id=$user_id");
    }
    $conn->query("UPDATE address SET name='$name', phone='$phone', province='$province', city='$city', detail='$detail', is_default=$is_default WHERE id=$id AND user_id=$user_id");
    header("Location: address.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <title>编辑地址</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
  <h3>编辑收货地址</h3>
  <form method="post">
    <div class="mb-2"><label>收件人：<input type="text" name="name" required value="<?php echo htmlspecialchars($addr['name']); ?>"></label></div>
    <div class="mb-2"><label>电话：<input type="text" name="phone" required value="<?php echo htmlspecialchars($addr['phone']); ?>"></label></div>
    <div class="mb-2"><label>省份：<input type="text" name="province" required value="<?php echo htmlspecialchars($addr['province']); ?>"></label></div>
    <div class="mb-2"><label>城市：<input type="text" name="city" required value="<?php echo htmlspecialchars($addr['city']); ?>"></label></div>
    <div class="mb-2"><label>详细地址：<input type="text" name="detail" required value="<?php echo htmlspecialchars($addr['detail']); ?>"></label></div>
    <div class="mb-2"><label><input type="checkbox" name="is_default" value="1" <?php if($addr['is_default']) echo 'checked'; ?>> 设为默认地址</label></div>
    <button type="submit" class="btn btn-success">保存</button>
    <a href="address.php" class="btn btn-secondary">返回</a>
  </form>
</div>
</body>
</html>
