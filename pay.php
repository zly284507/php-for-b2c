<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$order_id = intval($_GET['order_id'] ?? 0);
$user_id = $_SESSION['user_id'];

// 查订单
$sql = "SELECT * FROM orders WHERE id = $order_id AND user_id = $user_id";
$result = $conn->query($sql);
if (!$result || $result->num_rows == 0) {
    echo "订单不存在或无权操作！";
    exit;
}
$order = $result->fetch_assoc();

// 查余额
$user_res = $conn->query("SELECT balance FROM user WHERE id = $user_id");
$user = $user_res->fetch_assoc();
$balance = $user['balance'];

// 校验余额
if ($balance < $order['total']) {
    echo "<div class='alert alert-danger'>余额不足，请充值！当前余额：￥".number_format($balance,2)."</div>";
    echo '<a href="user.php" class="btn btn-secondary mt-3">返回个人中心</a>';
    exit;
}

// 开始事务，防止并发扣多次
$conn->begin_transaction();
try {
    // 扣钱
    $conn->query("UPDATE user SET balance = balance - {$order['total']} WHERE id = $user_id");
    // 改订单状态（用中文）
    $conn->query("UPDATE orders SET status='已支付', pay_time=NOW() WHERE id=$order_id");
    $conn->commit();
} catch(Exception $e) {
    $conn->rollback();
    echo "支付失败，请重试！";
    exit;
}

header("Location: user.php?pay_success=1");
exit;
?>