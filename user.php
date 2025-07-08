<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$user_id = $_SESSION['user_id'];

// 查询用户基本信息和余额
$user_res = $conn->query("SELECT username, email, balance FROM user WHERE id = $user_id");
$user = $user_res->fetch_assoc();

// 订单状态映射（只保留已支付、已发货、已签收）
$status_map = [
    '已支付' => '已支付',
    '已发货' => '已发货',
    '已签收' => '已签收'
];

// 根据筛选条件获取订单
$status_filter = $_GET['status'] ?? '';
$orders = [];
if ($status_filter) {
    $filter = $status_filter;
    $sql = "SELECT * FROM orders WHERE user_id = $user_id AND status='$filter' ORDER BY created_at DESC";
    $res = $conn->query($sql);
    while ($row = $res->fetch_assoc()) $orders[] = $row;
} else {
    $sql = "SELECT * FROM orders WHERE user_id = $user_id ORDER BY created_at DESC";
    $res = $conn->query($sql);
    while ($row = $res->fetch_assoc()) $orders[] = $row;
}

// 获取订单商品详情
foreach ($orders as &$order) {
    $item_sql = "SELECT oi.*, p.name AS product_name, p.image FROM order_item oi LEFT JOIN product p ON oi.product_id = p.id WHERE oi.order_id = {$order['id']}";
    $item_res = $conn->query($item_sql);
    $order['items'] = [];
    while ($item = $item_res->fetch_assoc()) $order['items'][] = $item;
}
unset($order);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>个人中心 - 酷牌商城</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .order-table ul { margin:0; padding:0; list-style:none; }
        .order-table ul li { margin-bottom:3px; }
        .order-table img { height:40px; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="KupaiShop.php">酷牌商城 KupaiShop</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="KupaiShop.php">首页</a></li>
                <li class="nav-item"><a class="nav-link" href="product-list.php">全部商品</a></li>
                <li class="nav-item"><a class="nav-link" href="cart.php">购物车</a></li>
                <li class="nav-item"><a class="nav-link active" href="user.php">个人中心</a></li>
                <li class="nav-item"><a class="nav-link" href="contact.html">联系我们</a></li>
            </ul>
            <?php if(isset($_SESSION['user_id'])): ?>
                <span class="navbar-text me-2">欢迎，<?php echo htmlspecialchars($user['username']); ?></span>
                <a class="btn btn-outline-light" href="logout.php">退出</a>
            <?php else: ?>
                <a class="btn btn-outline-light me-2" href="login.php">登录</a>
                <a class="btn btn-light" href="register.php">注册</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <?php if (isset($_GET['signed'])): ?>
        <div class="alert alert-success">签收成功！</div>
    <?php endif; ?>
    <?php if (isset($_GET['pay_success'])): ?>
        <div class="alert alert-success">支付成功！</div>
    <?php endif; ?>
    <h2>个人中心</h2>
    <div class="mb-3">
        <b>用户名：</b><?php echo htmlspecialchars($user['username']); ?><br>
        <b>邮箱：</b><?php echo htmlspecialchars($user['email']); ?><br>
        <b>账户余额：</b>
        <span class="text-success">￥<?php echo number_format($user['balance'],2); ?></span>
        <span class="text-muted ms-2">(余额由管理员发放或充值)</span>
        <a href="edit-profile.php" class="btn btn-warning btn-sm ms-2">修改信息</a>
    </div>

    <div class="mb-3">
        <a href="address.php" class="btn btn-info btn-sm">收货地址管理</a>
    </div>

    <div class="mb-4">
        <a href="user.php" class="btn btn-outline-primary btn-sm<?php if(!$status_filter)echo' active';?>">全部订单</a>
        <a href="user.php?status=已支付" class="btn btn-outline-primary btn-sm<?php if($status_filter=='已支付')echo' active';?>">已支付</a>
        <a href="user.php?status=已发货" class="btn btn-outline-primary btn-sm<?php if($status_filter=='已发货')echo' active';?>">已发货</a>
        <a href="user.php?status=已签收" class="btn btn-outline-primary btn-sm<?php if($status_filter=='已签收')echo' active';?>">已签收</a>
    </div>

    <h4>我的订单</h4>
    <?php if (empty($orders)): ?>
        <div class="alert alert-info">暂无订单记录。</div>
    <?php else: ?>
    <div class="table-responsive">
    <table class="table table-bordered align-middle order-table">
        <thead class="table-light">
            <tr>
                <th>订单编号</th>
                <th>下单时间</th>
                <th>商品</th>
                <th>图片</th>
                <th>数量</th>
                <th>总价</th>
                <th>状态</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($orders as $order): ?>
            <?php $rowspan = count($order['items']) ?: 1; ?>
            <?php foreach($order['items'] as $i => $item): ?>
            <tr>
                <?php if ($i == 0): ?>
                <td rowspan="<?php echo $rowspan; ?>"><?php echo htmlspecialchars($order['id']); ?></td>
                <td rowspan="<?php echo $rowspan; ?>"><small><?php echo $order['created_at']; ?></small></td>
                <?php endif; ?>
                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                <td>
                    <?php if (!empty($item['image'])): ?>
                        <img src="<?php echo $item['image']; ?>" alt="商品图">
                    <?php endif; ?>
                </td>
                <td><?php echo intval($item['quantity']); ?></td>
                <?php if ($i == 0): ?>
                <td rowspan="<?php echo $rowspan; ?>" class="text-danger align-middle">￥<?php echo number_format($order['total'], 2); ?></td>
                <td rowspan="<?php echo $rowspan; ?>" class="align-middle">
                    <?php
                        $status = $order['status'];
                        echo $status_map[$status] ?? htmlspecialchars($status);
                    ?>
                </td>
                <td rowspan="<?php echo $rowspan; ?>" class="align-middle">
                    <a href="order-detail.php?order_id=<?php echo $order['id']; ?>" class="btn btn-info btn-sm mb-1">详情</a>
                    <?php if ($status == '已支付'): ?>
                        <span class="text-secondary">待发货</span>
                    <?php elseif ($status == '已发货'): ?>
                        <a href="confirm_receive.php?order_id=<?php echo $order['id']; ?>" class="btn btn-primary btn-sm mb-1" onclick="return confirm('确认签收？')">签收</a>
                    <?php endif; ?>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
<footer class="bg-primary text-white text-center py-3">&copy; <?php echo date('Y'); ?> 酷牌商城</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>