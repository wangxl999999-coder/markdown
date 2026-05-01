<?php
/**
 * 下载MD文件API
 */

require_once __DIR__ . '/../config/autoload.php';

/**
 * 解析输入数据 - 同时支持 JSON 和表单提交
 * @return array
 */
function parseInputData() {
    $data = [];
    
    // 首先合并表单数据
    if (!empty($_POST)) {
        $data = array_merge($data, $_POST);
    }
    
    // 然后解析 JSON 数据（php://input 只能读取一次）
    $input = file_get_contents('php://input');
    if ($input !== false && !empty($input)) {
        $jsonData = json_decode($input, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
            $data = array_merge($data, $jsonData);
        }
    }
    
    return $data;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('请求方法错误');
    }
    
    $input = parseInputData();
    
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
