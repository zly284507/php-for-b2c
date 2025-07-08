<?php
session_start();
include 'config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$user_id = $_SESSION['user_id'];

// 处理删除
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM address WHERE id=$id AND user_id=$user_id");
    header("Location: address.php");
    exit;
}

// 处理设为默认
if (isset($_GET['default'])) {
    $id = intval($_GET['default']);
    $conn->query("UPDATE address SET is_default=0 WHERE user_id=$user_id");
    $conn->query("UPDATE address SET is_default=1 WHERE id=$id AND user_id=$user_id");
    header("Location: address.php");
    exit;
}

// 处理“使用”按钮
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['use_address_id'])) {
    $_SESSION['order_address_id'] = intval($_POST['use_address_id']);
    header("Location: checkout.php");
    exit;
}

// 查询所有地址
$sql = "SELECT * FROM address WHERE user_id = $user_id ORDER BY is_default DESC, id DESC";
$result = $conn->query($sql);
$addresses = [];
while ($row = $result->fetch_assoc()) $addresses[] = $row;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <title>收货地址管理</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
  <h3>收货地址管理</h3>
  <a href="add_address.php" class="btn btn-primary btn-sm mb-3">新增地址</a>
  <table class="table">
    <tr><th>收件人</th><th>电话</th><th>地址</th><th>默认</th><th>操作</th></tr>
    <?php foreach($addresses as $addr): ?>
      <tr>
        <td><?php echo htmlspecialchars($addr['name']); ?></td>
        <td><?php echo htmlspecialchars($addr['phone']); ?></td>
        <td><?php echo htmlspecialchars($addr['province'].$addr['city'].$addr['detail']); ?></td>
        <td>
            <?php echo $addr['is_default'] ? '<span class="text-success">是</span>' : ''; ?>
        </td>
        <td>
          <a href="edit_address.php?id=<?php echo $addr['id']; ?>" class="btn btn-secondary btn-sm">编辑</a>
          <a href="address.php?delete=<?php echo $addr['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('确认删除？')">删除</a>
          <?php if (!$addr['is_default']): ?>
            <a href="address.php?default=<?php echo $addr['id']; ?>" class="btn btn-success btn-sm">设为默认</a>
          <?php endif; ?>
          <!-- 使用按钮 -->
          <form method="post" action="address.php" style="display:inline;">
            <input type="hidden" name="use_address_id" value="<?php echo $addr['id']; ?>">
            <button type="submit" class="btn btn-info btn-sm">使用</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
</body>
</html>