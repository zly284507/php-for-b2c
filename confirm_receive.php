<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$order_id = intval($_GET['order_id']);
$user_id = intval($_SESSION['user_id']);

// 只允许已发货状态签收，签收后变成已签收（中文）
$sql = "UPDATE `orders` SET status='已签收', confirm_time=NOW() WHERE id=$order_id AND user_id=$user_id AND status='已发货'";
$conn->query($sql);
header("Location: user.php?signed=1");
exit;
?>