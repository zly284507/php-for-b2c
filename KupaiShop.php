<?php
// 启动 session，用于用户登录状态等功能
session_start();
// 引入数据库配置和连接
include 'config.php';

// 获取所有分类用于首页搜索框
$categoryResult = $conn->query("SELECT * FROM category");
$categories = [];
while ($row = $categoryResult->fetch_assoc()) {
    $categories[] = $row['name'];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>酷牌商城首页 - KupaiShop</title>
    <!-- 引入Bootstrap样式库 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* 轮播图相关样式 */
        .carousel-flex-row {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            gap: 0;
        }
        .carousel-side-img {
            height: 460px;
            flex: 1 1 0;
            min-width: 0;
            object-fit: cover;
            border-radius: 18px 0 0 18px;
            background: #eee;
            border: none;
            display: block;
        }
        .carousel-side-img.right {
            border-radius: 0 18px 18px 0;
        }
        .carousel-main-img {
            height: 460px;
            width: 600px;
            min-width: 320px;
            object-fit: contain;
            border-radius: 0;
            box-shadow: 0 8px 24px #0002;
            background: #fff;
            border: none;
            display: block;
        }
        @media (max-width: 1200px) {
            .carousel-main-img { width: 40vw; min-width: 160px; }
            .carousel-side-img { height: 240px; }
        }
        @media (max-width: 700px) {
            .carousel-main-img, .carousel-side-img { height: 120px; }
        }
        /* 搜索框样式 */
        .top-search-box {
            position: relative;
            margin-bottom: 10px;
            min-height: 40px;
        }
        .top-search-form {
            position: absolute;
            top: 0;
            right: 0;
            z-index: 10;
            width: 370px;
            background: transparent;
        }
        .top-search-form select {
            border-radius: 20px 0 0 20px;
        }
        .top-search-form input[type="text"] {
            border-radius: 0;
        }
        .top-search-form button[type="submit"] {
            border-radius: 0 20px 20px 0;
        }
        @media (max-width: 700px) {
            .top-search-form {
                position: static;
                margin-top: 10px;
                width: 100%;
            }
        }
    </style>
</head>
<body>

<!-- 顶部导航栏 -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="KupaiShop.php">酷牌商城 KupaiShop</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="切换导航">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <!-- 导航菜单 -->
                <li class="nav-item"><a class="nav-link active" href="KupaiShop.php">首页</a></li>
                <li class="nav-item"><a class="nav-link" href="product-list.php">全部商品</a></li>
                <li class="nav-item"><a class="nav-link" href="cart.php">购物车</a></li>
                <li class="nav-item"><a class="nav-link" href="user.php">个人中心</a></li>
                <li class="nav-item"><a class="nav-link" href="contact.html">联系我们</a></li>
            </ul>
            <!-- 登录状态显示 -->
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


   <!-- 搜索框区域，轮播图上方，居中显示 -->
<div class="top-search-box d-flex justify-content-center mb-4">
    <form class="row gx-0 align-items-center shadow-sm p-2 bg-white"
          action="product-list.php" method="get" autocomplete="off"
          style="max-width:480px;width:100%;border-radius:22px;box-shadow:0 2px 8px #0001;background:#fff;">
        <div class="col-5">
            <select class="form-select border-0" name="category"
                    style="border-radius:20px 0 0 20px;height:42px;">
                <option value="" selected>全部分类</option>
                <?php foreach($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-5">
            <input type="text" class="form-control border-0" name="keyword" placeholder="搜索商品..."
                   style="border-radius:0;height:42px;">
        </div>
        <div class="col-2">
            <button class="btn btn-primary w-100" type="submit"
                    style="border-radius:0 20px 20px 0;height:42px;">搜索</button>
        </div>
    </form>
</div>

    <!-- 轮播图区域：动态显示最新3个商品及其前两个分类图片 -->
    <?php
    // 获取最新3件商品
    $carouselProducts = [];
    $carouselSql = "SELECT id, image, name FROM product ORDER BY created_at DESC LIMIT 3";
    $carouselResult = $conn->query($carouselSql);
    if (!$carouselResult) {
        die("SQL Error: " . $conn->error . "<br>SQL: " . $carouselSql);
    }
    while($row = $carouselResult->fetch_assoc()) {
        $carouselProducts[] = $row;
    }

    // 对每个商品获取前两个分类及其图片
    $newCarouselProducts = [];
    foreach($carouselProducts as $prod) {
        // 用LEFT JOIN查找商品对应的前2个分类及图片
        $catSql = "SELECT c.name, c.image FROM product_category pc LEFT JOIN category c ON pc.category_id = c.id WHERE pc.product_id = {$prod['id']} LIMIT 2";
        $catResult = $conn->query($catSql);
        $cats = [];
        while($catResult && ($catRow = $catResult->fetch_assoc())) {
            $catRow['image'] = str_replace("\\", "/", $catRow['image']); // 统一路径分隔符
            $cats[] = $catRow;
        }
        $prod['categories'] = $cats;
        $newCarouselProducts[] = $prod;
    }
    $carouselProducts = $newCarouselProducts;
    ?>
    <!-- 轮播图结构，显示商品主图片和两侧分类图片 -->
    <div id="carouselExampleIndicators" class="carousel slide mb-4" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <?php foreach($carouselProducts as $idx => $prod): ?>
            <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="<?php echo $idx; ?>" <?php if($idx==0) echo 'class="active" aria-current="true"'; ?> aria-label="幻灯片<?php echo $idx+1; ?>"></button>
            <?php endforeach; ?>
        </div>
        <div class="carousel-inner">
            <?php foreach($carouselProducts as $idx => $prod): ?>
            <div class="carousel-item <?php if($idx==0) echo 'active'; ?>">
                <div class="carousel-flex-row">
                    <!-- 左分类图片 -->
                    <?php if(isset($prod['categories'][0]['image']) && $prod['categories'][0]['image']): ?>
                        <img src="<?php echo htmlspecialchars($prod['categories'][0]['image']); ?>" class="carousel-side-img" alt="<?php echo htmlspecialchars($prod['categories'][0]['name']); ?>">
                    <?php else: ?>
                        <div class="carousel-side-img" style="background:#f6f6f6"></div>
                    <?php endif; ?>
                    <!-- 商品主图片 -->
                    <a href="product-detail.php?id=<?php echo $prod['id']; ?>">
                        <img src="<?php echo $prod['image']; ?>" class="carousel-main-img" alt="<?php echo htmlspecialchars($prod['name']); ?>">
                    </a>
                    <!-- 右分类图片（若没有第二个分类用第一个填充，否则留空色块） -->
                    <?php if(isset($prod['categories'][1]['image']) && $prod['categories'][1]['image']): ?>
                        <img src="<?php echo htmlspecialchars($prod['categories'][1]['image']); ?>" class="carousel-side-img right" alt="<?php echo htmlspecialchars($prod['categories'][1]['name']); ?>">
                    <?php elseif(isset($prod['categories'][0]['image']) && $prod['categories'][0]['image']): ?>
                        <img src="<?php echo htmlspecialchars($prod['categories'][0]['image']); ?>" class="carousel-side-img right" alt="<?php echo htmlspecialchars($prod['categories'][0]['name']); ?>">
                    <?php else: ?>
                        <div class="carousel-side-img right" style="background:#f6f6f6"></div>
                    <?php endif; ?>
                </div>
                <div class="text-center pt-3">
                    <h5><?php echo htmlspecialchars($prod['name']); ?></h5>
                    <!-- 分类标签 -->
                    <div>
                        <?php foreach($prod['categories'] as $cat): ?>
                        <span class="badge bg-secondary mx-1"><?php echo htmlspecialchars($cat['name']); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <!-- 轮播图左右切换按钮 -->
        <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleIndicators"
                data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">上一个</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleIndicators"
                data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">下一个</span>
        </button>
    </div>

    <!-- 欢迎语块 -->
    <div class="jumbotron p-4 mb-4 bg-light rounded-3">
        <h1 class="display-5">欢迎来到 <span style="color:#0d6efd;">酷牌商城</span>！</h1>
        <p class="lead">精选好物，品质保证，优惠多多，尽在酷牌！</p>
    </div>

    <!-- 推荐商品区域：取最新4件商品 -->
    <h2 class="mb-3">推荐商品</h2>
    <div class="row">
        <?php
        // 查询最新4件商品
        $sql = "SELECT * FROM product ORDER BY created_at DESC LIMIT 4";
        $result = $conn->query($sql);
        if (!$result) {
            die("SQL Error: " . $conn->error . "<br>SQL: " . $sql);
        }
        while($row = $result->fetch_assoc()):
        ?>
        <div class="col-md-3 mb-4">
            <div class="card">
                <a href="product-detail.php?id=<?php echo $row['id']; ?>">
                    <img src="<?php echo $row['image']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['name']); ?>">
                </a>
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($row['name']); ?></h5>
                    <p class="card-text">￥<?php echo $row['price']; ?></p>
                    <p class="card-text text-muted mb-2">库存：<?php echo intval($row['stock']); ?></p>
                    <a href="product-detail.php?id=<?php echo $row['id']; ?>" class="btn btn-primary">查看详情</a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <!-- 查看更多商品按钮 -->
    <div class="text-center mb-5">
        <a href="product-list.php" class="btn btn-outline-primary btn-lg">查看更多商品</a>
    </div>
</div>

<!-- 底部版权 -->
<footer class="bg-primary text-white text-center py-3">
    &copy; 2025 酷牌商城 KupaiShop. 保留所有权利。
</footer>

<!-- 引入Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>