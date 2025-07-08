<?php
// 启动会话，购物车数据存储在session中
session_start();

// 1. 获取商品ID和数量，并进行安全处理
$product_id = intval($_POST['product_id']);           // 商品ID强制转为整数，防止非法输入
$quantity = max(1, intval($_POST['quantity']));       // 最小数量为1，防止恶意提交0或负数

// 2. 初始化购物车（如果还没有购物车则新建一个空数组）
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// 3. 添加或累加当前商品进购物车
if (isset($_SESSION['cart'][$product_id])) {
    // 如果购物车已存在该商品，则数量累加
    $_SESSION['cart'][$product_id] += $quantity;
} else {
    // 否则直接设置该商品的数量
    $_SESSION['cart'][$product_id] = $quantity;
}

// 4. 跳转到购物车页面
header("Location: cart.php");
exit;