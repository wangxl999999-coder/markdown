<?php
/**
 * 配置文件
 */

// 数据库配置
define('DB_HOST', 'localhost');
define('DB_NAME', 'markdown');
define('DB_USER', 'root');
define('DB_PASS', '123123');
define('DB_CHARSET', 'utf8mb4');

// 上传配置
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('UPLOAD_ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// 微信公众号配置
define('WECHAT_APPID', 'your_wechat_appid');
define('WECHAT_APPSECRET', 'your_wechat_appsecret');
define('WECHAT_TOKEN', 'your_wechat_token');

// 会话配置
session_start();

// 错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);
