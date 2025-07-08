<?php
// 必须有session_start才能识别登录状态
session_start();

// 1. 引入数据库配置，连接数据库
include 'config.php';

// 2. 获取筛选参数（搜索关键字、分类名）
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

// 3. 分页参数初始化
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$pageSize = 8; // 每页显示商品数量
$offset = ($page - 1) * $pageSize;

// 4. 拼接SQL的WHERE条件和JOIN语句，支持关键词和分类筛选
$where = "WHERE 1";
$params = [];
$join = "";
if ($keyword !== '') {
    // 模糊搜索商品名
    $where .= " AND p.name LIKE ?";
    $params[] = "%{$keyword}%";
}
if ($category !== '' && $category !== '全部分类') {
    // 分类筛选，增加JOIN
    $join = "JOIN product_category pc ON p.id = pc.product_id JOIN category c ON pc.category_id = c.id";
    $where .= " AND c.name = ?";
    $params[] = $category;
}

// 5. 获取总商品数量，用于分页
$countSql = "SELECT COUNT(DISTINCT p.id) as total FROM product p $join $where";
$stmt = $conn->prepare($countSql);
if (!empty($params)) {
    $types = str_repeat("s", count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$countResult = $stmt->get_result();
$totalRow = $countResult->fetch_assoc();
$total = $totalRow ? $totalRow['total'] : 0;
$stmt->close();

// 6. 查询当前页商品数据
$listSql = "SELECT DISTINCT p.* FROM product p $join $where ORDER BY p.created_at DESC LIMIT ?, ?";
$stmt = $conn->prepare($listSql);
if (!empty($params)) {
    $types = str_repeat("s", count($params)) . "ii";
    $all_params = array_merge($params, [$offset, $pageSize]);
    $stmt->bind_param($types, ...$all_params);
} else {
    $stmt->bind_param("ii", $offset, $pageSize);
}
$stmt->execute();
$result = $stmt->get_result();

// 7. 获取所有商品分类，用于筛选下拉菜单
$categoryResult = $conn->query("SELECT * FROM category");
$categories = [];
while ($row = $categoryResult->fetch_assoc()) {
    $categories[] = $row['name'];
}

// 8. 计算总页数，供分页按钮使用
$totalPages = ceil($total / $pageSize);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>全部商品 - 酷牌商城 KupaiShop</title>
    <!-- 引入Bootstrap样式 -->
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
                <li class="nav-item"><a class="nav-link active" href="product-list.php">全部商品</a></li>
                <li class="nav-item"><a class="nav-link" href="cart.php">购物车</a></li>
                <li class="nav-item"><a class="nav-link" href="user.php">个人中心</a></li>
                <li class="nav-item"><a class="nav-link" href="contact.html">联系我们</a></li>
            </ul>
            <!-- 登录/注册 或 用户信息 -->
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
    <h2>全部商品</h2>
    <!-- 搜索与筛选表单 -->
    <form class="row g-3 align-items-center mb-4" method="get" action="product-list.php">
        <div class="col-auto">
            <!-- 商品名搜索框 -->
            <input type="text" class="form-control" name="keyword" placeholder="搜索商品..." value="<?php echo htmlspecialchars($keyword); ?>">
        </div>
        <div class="col-auto">
            <!-- 分类筛选下拉 -->
            <select class="form-select" name="category">
                <option <?php if ($category == '' || $category == '全部分类') echo 'selected'; ?>>全部分类</option>
                <?php foreach($categories as $cat): ?>
                    <option <?php if ($category == $cat) echo 'selected'; ?>><?php echo htmlspecialchars($cat); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">搜索</button>
        </div>
    </form>
    <!-- 商品列表 -->
    <div class="row">
        <?php if ($total == 0): ?>
            <!-- 没有商品时提示 -->
            <div class="col-12">
                <div class="alert alert-warning">没有找到相关商品。</div>
            </div>
        <?php endif; ?>
        <?php while ($row = $result->fetch_assoc()): ?>
        <div class="col-md-3 mb-4">
            <div class="card">
                <a href="product-detail.php?id=<?php echo $row['id']; ?>">
                    <img src="<?php echo $row['image']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['name']); ?>" style="height:180px;object-fit:cover;">
                </a>
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($row['name']); ?></h5>
                    <p class="card-text">￥<?php echo $row['price']; ?></p>
                    <a href="product-detail.php?id=<?php echo $row['id']; ?>" class="btn btn-primary">查看详情</a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <!-- 分页按钮 -->
    <?php if ($totalPages > 1): ?>
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => max(1, $page-1)])); ?>">上一页</a>
            </li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?php if ($page == $i) echo 'active'; ?>">
                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?php if ($page >= $totalPages) echo 'disabled'; ?>">
                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => min($totalPages, $page+1)])); ?>">下一页</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>
<!-- 页脚版权 -->
<footer class="bg-primary text-white text-center py-3 mt-4">
    &copy; 2025 酷牌商城 KupaiShop. 保留所有权利。
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>