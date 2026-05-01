<?php
/**
 * 图片上传API
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/autoload.php';

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
    
    // 生成文件名
    $extension = image_type_to_extension(exif_imagetype($file['tmp_name']));
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
