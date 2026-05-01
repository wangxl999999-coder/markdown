<?php
/**
 * 图片上传API
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/autoload.php';

/**
 * 获取文件扩展名 - 兼容多种方法
 * @param string $filePath 临时文件路径
 * @param string $originalName 原始文件名
 * @return string 扩展名（包含点）
 */
function getFileExtension($filePath, $originalName = '') {
    $mimeToExt = [
        'image/jpeg' => '.jpg',
        'image/png' => '.png',
        'image/gif' => '.gif',
        'image/webp' => '.webp'
    ];
    
    // 方法1：使用 getimagesize（比 exif 更普遍支持）
    if (function_exists('getimagesize')) {
        $imageInfo = @getimagesize($filePath);
        if ($imageInfo && isset($imageInfo[2])) {
            $ext = image_type_to_extension($imageInfo[2]);
            if ($ext) {
                return $ext;
            }
        }
    }
    
    // 方法2：使用 Finfo
    if (class_exists('Finfo')) {
        $finfo = new Finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($filePath);
        if (isset($mimeToExt[$mime])) {
            return $mimeToExt[$mime];
        }
    }
    
    // 方法3：从原始文件名获取
    if ($originalName) {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($ext) {
            return '.' . $ext;
        }
    }
    
    // 默认返回 .jpg
    return '.jpg';
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('请求方法错误');
    }
    
    if (!isset($_FILES['image'])) {
        throw new Exception('请选择要上传的图片');
    }
    
    $file = $_FILES['image'];
    
    // 检查上传错误
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => '文件大小超过服务器限制',
            UPLOAD_ERR_FORM_SIZE => '文件大小超过表单限制',
            UPLOAD_ERR_PARTIAL => '文件上传不完整',
            UPLOAD_ERR_NO_FILE => '没有文件被上传',
            UPLOAD_ERR_NO_TMP_DIR => '缺少临时文件夹',
            UPLOAD_ERR_CANT_WRITE => '文件写入失败',
            UPLOAD_ERR_EXTENSION => '文件上传被扩展程序阻止',
        ];
        throw new Exception($errors[$file['error']] ?? '上传失败');
    }
    
    // 检查文件大小
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        throw new Exception('文件大小不能超过10MB');
    }
    
    // 检查文件类型
    $finfo = new Finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!in_array($mimeType, UPLOAD_ALLOWED_TYPES)) {
        throw new Exception('只支持上传JPG、PNG、GIF、WEBP格式的图片');
    }
    
    // 生成文件名 - 使用更兼容的方式获取扩展名
    $extension = getFileExtension($file['tmp_name'], $file['name']);
    $filename = date('YmdHis') . '_' . uniqid() . $extension;
    
    // 按月分目录存储
    $subDir = date('Ym') . '/';
    $targetDir = UPLOAD_DIR . $subDir;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    $targetPath = $targetDir . $filename;
    
    // 移动文件
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('文件保存失败');
    }
    
    // 返回访问URL
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $scriptPath = dirname(dirname($_SERVER['SCRIPT_NAME']));
    $url = $protocol . $host . $scriptPath . '/uploads/' . $subDir . $filename;
    
    echo json_encode([
        'success' => true,
        'url' => $url,
        'filename' => $filename
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
