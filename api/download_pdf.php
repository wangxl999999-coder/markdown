<?php
/**
 * 下载PDF文件API
 */

require_once __DIR__ . '/../config/autoload.php';

use Mpdf\Mpdf;
use Parsedown;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('请求方法错误');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['content'])) {
        throw new Exception('缺少内容参数');
    }
    
    $markdownContent = $input['content'];
    $title = isset($input['title']) && !empty($input['title']) ? $input['title'] : 'untitled';
    
    // 解析Markdown为HTML
    $parsedown = new Parsedown();
    $htmlContent = $parsedown->text($markdownContent);
    
    // 准备HTML模板
    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{$title}</title>
    <style>
        body {
            font-family: "Microsoft YaHei", "SimHei", sans-serif;
            line-height: 1.6;
            color: #333;
            padding: 20px;
        }
        h1, h2, h3, h4, h5, h6 {
            margin-top: 24px;
            margin-bottom: 16px;
            font-weight: 600;
            line-height: 1.25;
        }
        h1 { font-size: 2em; border-bottom: 1px solid #eaecef; padding-bottom: 0.3em; }
        h2 { font-size: 1.5em; border-bottom: 1px solid #eaecef; padding-bottom: 0.3em; }
        h3 { font-size: 1.25em; }
        h4 { font-size: 1em; }
        p { margin: 16px 0; }
        code {
            background-color: #f6f8fa;
            border-radius: 3px;
            font-size: 85%;
            margin: 0;
            padding: 0.2em 0.4em;
            font-family: "Courier New", monospace;
        }
        pre {
            background-color: #f6f8fa;
            border-radius: 3px;
            font-size: 85%;
            line-height: 1.45;
            overflow: auto;
            padding: 16px;
        }
        pre code {
            background-color: transparent;
            border: 0;
            padding: 0;
        }
        blockquote {
            border-left: 4px solid #dfe2e5;
            color: #6a737d;
            margin: 16px 0;
            padding: 0 16px;
        }
        ul, ol {
            padding-left: 2em;
            margin: 16px 0;
        }
        li { margin: 8px 0; }
        table {
            border-collapse: collapse;
            border-spacing: 0;
            margin: 16px 0;
            width: 100%;
        }
        th, td {
            border: 1px solid #dfe2e5;
            padding: 6px 13px;
        }
        th { background-color: #f6f8fa; font-weight: 600; }
        img {
            max-width: 100%;
            height: auto;
        }
        hr {
            background-color: #e1e4e8;
            border: 0;
            height: 0.25em;
            margin: 24px 0;
            padding: 0;
        }
        a {
            color: #0366d6;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    {$htmlContent}
</body>
</html>
HTML;
    
    // 清理标题中的非法字符
    $title = preg_replace('/[^\w\-_\x{4e00}-\x{9fa5}]/u', '_', $title);
    $filename = $title . '.pdf';
    
    // 创建PDF
    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'default_font_size' => 12,
        'default_font' => 'Microsoft YaHei',
        'autoScriptToLang' => true,
        'autoLangToFont' => true,
    ]);
    
    $mpdf->WriteHTML($html);
    
    // 输出PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $mpdf->Output($filename, 'D');
    
} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
