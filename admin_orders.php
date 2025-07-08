<?php
session_start();
include 'config.php';
// 可加管理员身份判断，比如 if (!isset($_SESSION['is_admin'])) { header('Location: login.php'); exit; }

// 发货操作
if (isset($_GET['ship']) && is_numeric($_GET['ship'])) {
    $oid = intval($_GET['ship']);
    // 只允许"已支付"订单发货，发货后状态设为"已发货"
    $conn->query("UPDATE `orders` SET status='已发货', ship_time=NOW() WHERE id=$oid AND status='已支付'");
    header("Location: admin_orders.php");
    exit;
}

// 查询所有订单（注意表名为orders，状态显示需映射）
$sql = "SELECT o.*, u.username, a.name as recv_name, a.phone, a.province, a.city, a.detail 
        FROM `orders` o 
        JOIN user u ON o.user_id = u.id 
        JOIN address a ON o.address_id = a.id 
        ORDER BY o.created_at DESC";
$result = $conn->query($sql);
$orders = [];
while ($row = $result->fetch_assoc()) $orders[] = $row;

// 获取订单商品
$order_items = [];
if ($orders) {
    $order_ids = implode(',', array_column($orders, 'id'));
    $sql = "SELECT oi.*, p.name, p.image FROM order_item oi JOIN product p ON oi.product_id=p.id WHERE oi.order_id IN ($order_ids)";
    $res = $conn->query($sql);
    while ($row = $res->fetch_assoc()) {
        $order_items[$row['order_id']][] = $row;
    }
}

// 状态映射（可根据实际需要调整）
$status_map = [
    '待付款'   => '待付款',
    '已支付'   => '已支付',
    '已发货'   => '已发货',
    '已签收'   => '已签收',
    '已完成'   => '已完成',
    '已取消'   => '已取消',
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>订单管理</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>订单管理</h2>
    <?php if (empty($orders)): ?>
        <div class="alert alert-info">暂无订单。</div>
    <?php else: ?>
    <div class="accordion" id="orderAccordion">
    <?php foreach ($orders as $k=>$order): ?>
      <div class="accordion-item">
        <h2 class="accordion-header" id="heading<?php echo $order['id']; ?>">
          <button class="accordion-button <?php if($k>0) echo 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $order['id']; ?>">
            订单号：<?php echo $order['id']; ?>
            用户：<?php echo htmlspecialchars($order['username']); ?>
            下单时间：<?php echo $order['created_at']; ?>
            状态：<?php echo $status_map[$order['status']] ?? $order['status']; ?>
          </button>
        </h2>
        <div id="collapse<?php echo $order['id']; ?>" class="accordion-collapse collapse <?php if($k==0) echo 'show'; ?>">
          <div class="accordion-body">
            <div>收货人：<?php echo htmlspecialchars($order['recv_name']); ?>，<?php echo htmlspecialchars($order['phone']); ?>，
                <?php echo htmlspecialchars($order['province'].$order['city'].$order['detail']); ?>
            </div>
            <table class="table table-sm mt-2">
                <tr><th>商品</th><th>图片</th><th>单价</th><th>数量</th><th>小计</th></tr>
                <?php foreach($order_items[$order['id']]??[] as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td><img src="<?php echo $item['image']; ?>" style="height:40px;"></td>
                    <td>￥<?php echo number_format($item['price'],2); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td>￥<?php echo number_format($item['price']*$item['quantity'],2); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="4" class="text-end">订单总额：</td>
                    <td class="text-danger">￥<?php echo number_format($order['total'],2); ?></td>
                </tr>
            </table>
            <div>
                订单状态：<b><?php echo $status_map[$order['status']] ?? $order['status']; ?></b>
                <?php if($order['status']=='已支付'): ?>
                    <a href="admin_orders.php?ship=<?php echo $order['id']; ?>" class="btn btn-success btn-sm ms-3" onclick="return confirm('确认发货？')">发货</a>
                <?php elseif($order['status']=='已发货'): ?>
                    <span class="text-success">，已发货</span>
                <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>