<?php
/**
 * 自动加载类
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';

// 包含第三方库
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}
