<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// 获取当前订单使用的地址
$address = null;
if (isset($_SESSION['order_address_id'])) {
    $aid = intval($_SESSION['order_address_id']);
    $sql = "SELECT * FROM address WHERE id=$aid AND user_id=$user_id";
    $result = $conn->query($sql);
    $address = $result->fetch_assoc();
}
if (!$address) {
    $sql = "SELECT * FROM address WHERE user_id=$user_id AND is_default=1 LIMIT 1";
    $result = $conn->query($sql);
    $address = $result->fetch_assoc();
}

// 获取购物车商品
$cart = $_SESSION['cart'] ?? [];
$cart_products = [];
$total = 0;
if ($cart) {
    $ids = implode(',', array_map('intval', array_keys($cart)));
    $sql = "SELECT * FROM product WHERE id IN ($ids)";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $pid = $row['id'];
        $row['qty'] = $cart[$pid];
        $row['subtotal'] = $row['price'] * $row['qty'];
        $cart_products[] = $row;
        $total += $row['subtotal'];
    }
}

// 下单逻辑
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay'])) {
    if (!$address) {
        $err = "请先选择收货地址！";
    } elseif (empty($cart_products)) {
        $err = "购物车为空，无法下单！";
    } else {
        // 1. 检查库存
        $stock_ok = true;
        foreach ($cart_products as $item) {
            $pid = $item['id'];
            $qty = $item['qty'];
            $sql = "SELECT stock FROM product WHERE id=$pid";
            $r = $conn->query($sql);
            $row = $r->fetch_assoc();
            if ($row['stock'] < $qty) {
                $stock_ok = false;
                $err = "商品【{$item['name']}】库存不足，无法下单！";
                break;
            }
        }
        if ($stock_ok) {
            // 开启事务（前提：你的MySQL连接用的InnoDB引擎）
            $conn->begin_transaction();

            try {
                // 2. 新增订单主表
                $address_id = $address['id'];
                $now = date('Y-m-d H:i:s');
                $status = '待付款';
                $sql = "INSERT INTO `orders` (user_id, address_id, total, status, created_at) VALUES ($user_id, $address_id, $total, '$status', '$now')";
                $res = $conn->query($sql);
                if (!$res) throw new Exception("订单插入失败: " . $conn->error);

                $order_id = $conn->insert_id;

                // 3. 新增订单商品表和扣减库存
                foreach ($cart_products as $item) {
                    $pid = $item['id'];
                    $qty = $item['qty'];
                    $price = $item['price'];
                    $res = $conn->query("INSERT INTO order_item (order_id, product_id, quantity, price) VALUES ($order_id, $pid, $qty, $price)");
                    if (!$res) throw new Exception("订单商品插入失败: " . $conn->error);

                    // 扣减库存（stock字段）
                    $res = $conn->query("UPDATE product SET stock = stock - $qty WHERE id = $pid AND stock >= $qty");
                    if ($conn->affected_rows == 0) {
                        throw new Exception("商品【{$item['name']}】库存不足，无法下单！");
                    }
                }

                // 4. 清空购物车与本次地址
                unset($_SESSION['cart']);
                unset($_SESSION['order_address_id']);

                // 提交事务
                $conn->commit();

                // 5. 跳转到支付页
                header("Location: pay.php?order_id=$order_id");
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $err = $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>确认订单</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4" style="max-width:900px;">
    <h2>确认订单</h2>
    <h5>收货地址</h5>
    <?php if ($address): ?>
        <div class="mb-3">
            <?php echo htmlspecialchars($address['name']); ?>，<?php echo htmlspecialchars($address['phone']); ?>，
            <?php echo htmlspecialchars($address['province'] . $address['city'] . $address['detail']); ?>
        </div>
    <?php else: ?>
        <div class="alert alert-warning d-flex justify-content-between align-items-center">
            <span>未设置收货地址，请先添加！</span>
            <a href="add_address.php" class="btn btn-sm btn-primary ms-3">去添加地址</a>
        </div>
    <?php endif; ?>

    <h5>商品列表</h5>
    <?php if ($cart_products): ?>
    <table class="table">
        <thead><tr><th>商品</th><th>单价</th><th>数量</th><th>小计</th></tr></thead>
        <tbody>
        <?php foreach ($cart_products as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['name']); ?></td>
                <td>￥<?php echo number_format($item['price'], 2); ?></td>
                <td><?php echo $item['qty']; ?></td>
                <td>￥<?php echo number_format($item['subtotal'], 2); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="text-end mb-3">
        <b>订单总额：</b><span class="text-danger">￥<?php echo number_format($total,2); ?></span>
    </div>
    <?php else: ?>
        <div class="alert alert-warning">购物车无商品！</div>
    <?php endif; ?>

    <?php if (!empty($err)): ?>
        <div class="alert alert-danger"><?php echo $err; ?></div>
    <?php endif; ?>

    <form method="post">
        <button name="pay" class="btn btn-primary" <?php if(!$address||!$cart_products)echo'disabled';?>>提交并去支付</button>
        <a href="cart.php" class="btn btn-secondary">返回购物车</a>
    </form>
</div>
</body>
</html>