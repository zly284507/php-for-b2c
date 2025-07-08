<?php
// 1. 启动会话，保证后续用到$_SESSION时不会报错
session_start();
// 2. 引入数据库连接配置
include 'config.php';

// 3. 获取商品ID（从URL参数接收），并做安全处理（转为整数）
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    // 商品ID非法，直接提示并终止
    echo "非法商品ID";
    exit;
}

// 4. 查询商品基本信息
$sql = "SELECT * FROM product WHERE id = $id";
$result = $conn->query($sql);
if (!$result || $result->num_rows === 0) {
    // 商品不存在，给出提示
    echo "找不到该商品";
    exit;
}
// 5. 将查到的商品作为关联数组保存
$product = $result->fetch_assoc();

// 6. 查询商品所属的所有分类（可能有多个）
$category_sql = "SELECT c.name FROM product_category pc 
                 LEFT JOIN category c ON pc.category_id = c.id 
                 WHERE pc.product_id = {$product['id']}";
$category_result = $conn->query($category_sql);
// 用数组保存所有分类名
$product_categories = [];
while($cat = $category_result->fetch_assoc()) {
    $product_categories[] = $cat['name'];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($product['name']); ?> - 商品详情 - 酷牌商城 KupaiShop</title>
    <!-- 引入Bootstrap样式库 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* 商品图片样式 */
        .product-img {
            max-width: 100%;
            max-height: 440px;
            border-radius: 16px;
            background: #fff;
            display: block;
            margin: 0 auto;
        }
        /* 数量输入框 */
        .buy-cart-form input[type="number"] {
            width: 70px;
            display: inline-block;
        }
        /* 购买按钮宽度 */
        .buy-cart-form .btn {
            min-width: 120px;
        }
    </style>
</head>
<body>
<!-- 顶部导航栏：显示常用页面入口和登录状态 -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="KupaiShop.php">酷牌商城 KupaiShop</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="切换导航">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="KupaiShop.php">首页</a></li>
                <li class="nav-item"><a class="nav-link" href="product-list.php">全部商品</a></li>
                <li class="nav-item"><a class="nav-link" href="cart.php">购物车</a></li>
                <li class="nav-item"><a class="nav-link" href="user.php">个人中心</a></li>
            </ul>
            <?php if(isset($_SESSION['user_id'])): ?>
                <!-- 已登录，显示用户名和退出按钮 -->
                <span class="navbar-text me-2">欢迎，<?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a class="btn btn-outline-light" href="logout.php">退出</a>
            <?php else: ?>
                <!-- 未登录，显示登录和注册按钮 -->
                <a class="btn btn-outline-light me-2" href="login.php">登录</a>
                <a class="btn btn-light" href="register.php">注册</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<!-- 主体内容区 -->
<div class="container mt-4 mb-5">
    <div class="row">
        <!-- 左侧：商品图片 -->
        <div class="col-md-6 text-center">
            <img src="<?php echo htmlspecialchars($product['image']); ?>" class="product-img" alt="<?php echo htmlspecialchars($product['name']); ?>">
        </div>
        <!-- 右侧：商品信息和购买操作 -->
        <div class="col-md-6">
            <!-- 商品名称 -->
            <h2><?php echo htmlspecialchars($product['name']); ?></h2>
            <!-- 商品分类标签（若有） -->
            <?php if (!empty($product_categories)): ?>
                <div class="mb-2">
                    分类:
                    <?php foreach($product_categories as $cat): ?>
                        <span class="badge bg-secondary"><?php echo htmlspecialchars($cat); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <!-- 商品价格 -->
            <h4 class="text-danger mb-3">￥<?php echo number_format($product['price'], 2); ?></h4>
            <!-- 商品库存显示 -->
            <p class="mb-2"><b>库存：</b><?php echo intval($product['stock']); ?></p>
            <!-- 商品描述 -->
            <p><?php echo nl2br(htmlspecialchars($product['description'] ?? '暂无描述')); ?></p>
            <!-- 购物操作表单 -->
            <div class="buy-cart-form mt-3">
                <!-- 加入购物车表单 -->
                <form method="post" action="add_to_cart.php" class="d-inline">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <label for="cart-qty" class="me-1">数量:</label>
                    <input type="number" id="cart-qty" name="quantity" value="1" min="1" max="<?php echo intval($product['stock']); ?>">
                    <button type="submit" class="btn btn-primary ms-2" <?php if($product['stock'] <= 0) echo 'disabled'; ?>>加入购物车</button>
                </form>
                <!-- 立即购买表单 -->
                <form method="post" action="buy_now.php" class="d-inline ms-2">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <input type="hidden" name="quantity" value="1" id="buy-now-qty">
                    <button type="submit" class="btn btn-success" <?php if($product['stock'] <= 0) echo 'disabled'; ?>>立即购买</button>
                </form>
            </div>
            <?php if($product['stock'] <= 0): ?>
                <div class="alert alert-danger mt-3">该商品已售罄！</div>
            <?php endif; ?>
            <script>
                // 下面JS保证“立即购买”数量和“加入购物车”同步
                document.getElementById('cart-qty').addEventListener('input', function() {
                    document.getElementById('buy-now-qty').value = this.value;
                });
            </script>
        </div>
    </div>
</div>
<!-- 页脚版权 -->
<footer class="bg-primary text-white text-center py-3">
    &copy; 2025 酷牌商城 KupaiShop. 保留所有权利。
</footer>
<!-- 引入Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>