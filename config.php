<?php
$servername = "localhost";
$username = "root";
$password = "284507"; // 按你自己的设置填写
$dbname = "酷牌商城数据库"; // 你的数据库名

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("数据库连接失败: " . $conn->connect_error);
}
?>