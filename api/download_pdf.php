<?php
/**
 * 下载PDF文件API
 * 修复mPDF的Undefined offset问题
 */

require_once __DIR__ . '/../config/autoload.php';

use Mpdf\Mpdf;
use Parsedown;

/**
 * 解析输入数据 - 同时支持 JSON 和表单提交
 * @return array
 */
function parseInputData() {
    $data = [];
    
    if (!empty($_POST)) {
        $data = array_merge($data, $_POST);
    }
    
    $input = file_get_contents('php://input');
    if ($input !== false && !empty($input)) {
        $jsonData = json_decode($input, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
            $data = array_merge($data, $jsonData);
        }
    }
    
    return $data;
}

/**
 * 生成PDF样式表 - 使用简单的CSS格式避免mPDF解析错误
 * @return string
 */
function getPdfStyles() {
    return '
        body {
            font-family: "Microsoft YaHei", "SimHei", sans-serif;
            font-size: 14px;
            line-height: 1.8;
            color: #333333;
        }
        h1 {
            font-size: 28px;
            font-weight: bold;
            margin-top: 24px;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eaecef;
        }
        h2 {
            font-size: 22px;
            font-weight: bold;
            margin-top: 24px;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eaecef;
        }
        h3 {
            font-size: 18px;
            font-weight: bold;
            margin-top: 24px;
            margin-bottom: 16px;
        }
        h4 {
            font-size: 16px;
            font-weight: bold;
            margin-top: 24px;
            margin-bottom: 16px;
        }
        p {
            margin-top: 16px;
            margin-bottom: 16px;
        }
        code {
            background-color: #f6f8fa;
            font-family: "Courier New", monospace;
            font-size: 12px;
            padding: 2px 6px;
            border-radius: 3px;
        }
        pre {
            background-color: #f6f8fa;
            padding: 16px;
            border-radius: 4px;
            overflow: auto;
        }
        pre code {
            background-color: transparent;
            padding: 0;
        }
        blockquote {
            border-left: 4px solid #dfe2e5;
            color: #6a737d;
            padding-left: 16px;
            margin-top: 16px;
            margin-bottom: 16px;
        }
        ul {
            padding-left: 32px;
            margin-top: 16px;
            margin-bottom: 16px;
        }
        ol {
            padding-left: 32px;
            margin-top: 16px;
            margin-bottom: 16px;
        }
        li {
            margin-top: 8px;
            margin-bottom: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
            margin-bottom: 16px;
        }
        th {
            background-color: #f6f8fa;
            font-weight: bold;
            border: 1px solid #dfe2e5;
            padding: 6px 13px;
        }
        td {
            border: 1px solid #dfe2e5;
            padding: 6px 13px;
        }
        img {
            max-width: 100%;
            height: auto;
        }
        hr {
            height: 1px;
            background-color: #e1e4e8;
            border: none;
            margin-top: 24px;
            margin-bottom: 24px;
        }
        a {
            color: #0366d6;
            text-decoration: none;
        }
    ';
}

/**
 * 清理HTML内容，移除可能导致mPDF错误的内容
 * @param string $html
 * @return string
 */
function cleanHtmlForPdf($html) {
    if (empty($html)) {
        return '<p></p>';
    }
    
    // 移除空标签
    $html = preg_replace('/<(\w+)\s*><\/\1>/', '', $html);
    
    // 确保标签正确闭合（简单处理）
    $html = trim($html);
    
    // 如果没有内容，返回空段落
    if (empty(strip_tags($html))) {
        return '<p>文档内容为空</p>';
    }
    
    return $html;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('请求方法错误');
    }
    
    $input = parseInputData();
    
    if (!isset($input['content'])) {
        throw new Exception('缺少内容参数');
    }
    
    if (!class_exists('Parsedown')) {
        throw new Exception('缺少依赖库 Parsedown，请运行 composer install 安装依赖');
    }
    if (!class_exists('Mpdf\Mpdf')) {
        throw new Exception('缺少依赖库 mPDF，请运行 composer install 安装依赖');
    }
    
    $markdownContent = $input['content'];
    $title = isset($input['title']) && !empty($input['title']) ? $input['title'] : 'untitled';
    
    // 解析Markdown为HTML
    $parsedown = new Parsedown();
    $htmlContent = $parsedown->text($markdownContent);
    
    // 清理HTML内容
    $htmlContent = cleanHtmlForPdf($htmlContent);
    
    // 清理标题中的非法字符
    $safeTitle = preg_replace('/[^\w\-_\x{4e00}-\x{9fa5}]/u', '_', $title);
    $filename = $safeTitle . '.pdf';
    
    // 抑制错误显示（mPDF可能会产生警告）
    $errorLevel = error_reporting();
    error_reporting($errorLevel & ~E_NOTICE & ~E_WARNING);
    
    try {
        // 创建PDF实例
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font_size' => 12,
            'default_font' => 'Microsoft YaHei',
            'margin_left' => 20,
            'margin_right' => 20,
            'margin_top' => 20,
            'margin_bottom' => 20,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);
        
        // 先写入CSS
        $styles = getPdfStyles();
        $mpdf->WriteHTML($styles, \Mpdf\HTMLParserMode::HEADER_CSS);
        
        // 然后写入HTML内容
        $mpdf->WriteHTML($htmlContent, \Mpdf\HTMLParserMode::HTML_BODY);
        
    } catch (Exception $pdfException) {
        error_reporting($errorLevel);
        throw new Exception('PDF生成失败: ' . $pdfException->getMessage());
    }
    
    // 恢复错误级别
    error_reporting($errorLevel);
    
    // 输出PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $mpdf->Output($filename, 'D');
    
} catch (Exception $e) {
    if (isset($errorLevel)) {
        error_reporting($errorLevel);
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
