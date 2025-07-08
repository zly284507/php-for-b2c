<?php
session_start();
include 'config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$user_id = $_SESSION['user_id'];

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
    $conn->query("INSERT INTO address (user_id, name, phone, province, city, detail, is_default) VALUES ($user_id, '$name', '$phone', '$province', '$city', '$detail', $is_default)");
    header("Location: address.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <title>新增地址</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
  <h3>新增收货地址</h3>
  <form method="post">
    <div class="mb-2"><label>收件人：<input type="text" name="name" required></label></div>
    <div class="mb-2"><label>电话：<input type="text" name="phone" required></label></div>
    <div class="mb-2"><label>省份：<input type="text" name="province" required></label></div>
    <div class="mb-2"><label>城市：<input type="text" name="city" required></label></div>
    <div class="mb-2"><label>详细地址：<input type="text" name="detail" required></label></div>
    <div class="mb-2"><label><input type="checkbox" name="is_default" value="1"> 设为默认地址</label></div>
    <button type="submit" class="btn btn-success">保存</button>
    <a href="address.php" class="btn btn-secondary">返回</a>
  </form>
</div>
</body>
</html>