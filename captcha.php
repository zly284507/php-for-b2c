<?php
// 启动会话，验证码要存到session里，供登录/注册时校验用
session_start();
// 设置响应类型为PNG图片
header('Content-type: image/png');

// ========== 1. 生成验证码字符串 ==========
$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz123456789'; // 可用字符（不含易混淆的0和O等）
$code = '';
for ($i = 0; $i < 4; $i++) { // 验证码为4位
    $code .= $chars[mt_rand(0, strlen($chars)-1)]; // 随机选一个字符拼到$code末尾
}
// 把验证码存进session，方便后续校验
$_SESSION['captcha'] = $code;

// ========== 2. 创建图片资源 ==========
$im = imagecreatetruecolor(100, 36); // 新建100x36的真彩色图像
$bg = imagecolorallocate($im, 255, 255, 255); // 白色背景
$font_color = imagecolorallocate($im, 0, 0, 0); // 黑色字体
imagefilledrectangle($im, 0, 0, 100, 36, $bg); // 填充背景

// ========== 3. 添加干扰线 ==========
for($i=0;$i<4;$i++){
    $line_color = imagecolorallocate($im, mt_rand(100,255), mt_rand(100,255), mt_rand(100,255)); // 随机颜色
    imageline($im, mt_rand(0,100), mt_rand(0,36), mt_rand(0,100), mt_rand(0,36), $line_color);  // 随机位置画线
}

// ========== 4. 写入验证码文本 ==========
$fontfile = __DIR__ . '/assets/fonts/arial.ttf'; // 字体文件路径
if(file_exists($fontfile)){
    // 有ttf字体文件就用更好看的字体，并随机旋转角度
    imagettftext($im, 20, mt_rand(-10,10), 15, 28, $font_color, $fontfile, $code);
} else {
    // 没字体文件就用内置字体
    imagestring($im, 5, 25, 10, $code, $font_color);
}

// ========== 5. 输出图片并销毁资源 ==========
imagepng($im); // 输出为png格式
imagedestroy($im); // 释放内存