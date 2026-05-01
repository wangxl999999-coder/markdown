<?php
/**
 * 下载MD文件API
 */

require_once __DIR__ . '/../config/autoload.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('请求方法错误');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['content'])) {
        throw new Exception('缺少内容参数');
    }
    
    $content = $input['content'];
    $title = isset($input['title']) && !empty($input['title']) ? $input['title'] : 'untitled';
    
    // 清理标题中的非法字符
    $title = preg_replace('/[^\w\-_\x{4e00}-\x{9fa5}]/u', '_', $title);
    $filename = $title . '.md';
    
    // 设置下载头
    header('Content-Type: text/markdown; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($content));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo $content;
    
} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
