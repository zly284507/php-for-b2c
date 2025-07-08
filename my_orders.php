<?php
session_start();
include 'config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$user_id = $_SESSION['user_id'];

// 获取当前用户所有订单
$sql = "SELECT o.*, a.name as recv_name, a.phone, a.province, a.city, a.detail FROM `order` o JOIN address a ON o.address_id = a.id WHERE o.user_id=$user_id ORDER BY o.created_at DESC";
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
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>我的订单</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>我的订单</h2>
    <?php if (empty($orders)): ?>
        <div class="alert alert-info">暂无订单。</div>
    <?php else: ?>
    <div class="accordion" id="orderAccordion">
    <?php foreach ($orders as $k=>$order): ?>
      <div class="accordion-item">
        <h2 class="accordion-header" id="heading<?php echo $order['id']; ?>">
          <button class="accordion-button <?php if($k>0) echo 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $order['id']; ?>">
            订单号：<?php echo $order['id']; ?> 下单时间：<?php echo $order['created_at']; ?> 状态：<?php echo $order['status']; ?>
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
                <tr><td colspan="4" class="text-end">订单总额：</td><td class="text-danger">￥<?php echo number_format($order['total_amount'],2); ?></td></tr>
            </table>
            <div>订单状态：<b><?php echo $order['status']; ?></b>
            <?php if($order['status']=='已发货'): ?>
                <span class="text-success">，商家已发货</span>
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