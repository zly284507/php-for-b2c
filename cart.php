<?php
session_start();
include 'config.php';

// 处理操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove']) && isset($_POST['product_id'])) {
        unset($_SESSION['cart'][$_POST['product_id']]);
        header("Location: cart.php"); exit;
    }
    if (isset($_POST['update']) && isset($_POST['product_id']) && isset($_POST['quantity'])) {
        $product_id = $_POST['product_id'];
        $qty = max(1, intval($_POST['quantity']));
        $_SESSION['cart'][$product_id] = $qty;
        header("Location: cart.php"); exit;
    }
}

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
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>购物车 - 酷牌商城 KupaiShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<!-- 顶部导航栏 -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="KupaiShop.php">酷牌商城 KupaiShop</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="KupaiShop.php">首页</a></li>
                <li class="nav-item"><a class="nav-link" href="product-list.php">全部商品</a></li>
                <li class="nav-item"><a class="nav-link active" href="cart.php">购物车</a></li>
                <li class="nav-item"><a class="nav-link" href="user.php">个人中心</a></li>
                <li class="nav-item"><a class="nav-link" href="contact.html">联系我们</a></li>
            </ul>
            <?php if(isset($_SESSION['user_id'])): ?>
                <span class="navbar-text me-2">欢迎，<?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a class="btn btn-outline-light" href="logout.php">退出</a>
            <?php else: ?>
                <a class="btn btn-outline-light me-2" href="login.php">登录</a>
                <a class="btn btn-light" href="register.php">注册</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<div class="container mt-4">
    <h2>我的购物车</h2>
    <?php if (empty($cart_products)): ?>
        <div class="alert alert-info mt-4">您的购物车是空的，快去选购商品吧！</div>
    <?php else: ?>
    <table class="table table-bordered align-middle mt-3">
        <thead class="table-light">
            <tr>
                <th>商品图片</th>
                <th>商品名称</th>
                <th>单价</th>
                <th>数量</th>
                <th>小计</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($cart_products as $item): ?>
            <tr>
                <td><img src="<?php echo htmlspecialchars($item['image']); ?>" style="height:60px;" alt="<?php echo htmlspecialchars($item['name']); ?>"></td>
                <td><?php echo htmlspecialchars($item['name']); ?></td>
                <td>￥<?php echo number_format($item['price'],2); ?></td>
                <td>
                    <!-- onchange自动提交 -->
                    <form method="post" action="cart.php" style="display:inline;">
                        <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                        <input type="number" name="quantity" value="<?php echo $item['qty']; ?>" min="1" style="width:60px;" onchange="this.form.submit()">
                        <input type="hidden" name="update" value="1">
                    </form>
                </td>
                <td>￥<?php echo number_format($item['subtotal'],2); ?></td>
                <td>
                    <form method="post" action="cart.php" style="display:inline;">
                        <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                        <button class="btn btn-danger btn-sm" name="remove" value="1" type="submit" onclick="return confirm('确定要删除吗？');">删除</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="text-end">
        <h5>总计：<span class="text-danger">￥<?php echo number_format($total,2); ?></span></h5>
        <a href="checkout.php" class="btn btn-success btn-lg mt-2">去结算</a>
    </div>
    <?php endif; ?>
</div>
<footer class="bg-primary text-white text-center py-3 mt-4">
    &copy; 2025 酷牌商城 KupaiShop. 保留所有权利。
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>