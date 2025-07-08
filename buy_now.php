<?php
// 启动会话，购物车数据存储在session中
session_start();

// 1. 获取商品ID和数量，做类型安全处理
$product_id = intval($_POST['product_id']);           // 商品ID强制转为整数
$quantity = max(1, intval($_POST['quantity']));       // 最小数量为1

// 2. “立即购买”逻辑：只买当前商品，先清空购物车只保留当前商品
$_SESSION['cart'] = [];                              // 清空购物车
$_SESSION['cart'][$product_id] = $quantity;          // 只添加当前商品及数量

// 3. 跳转到结算页面
header("Location: checkout.php");
exit;