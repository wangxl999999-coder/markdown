<?php
/**
 * 微信公众号授权和发布API
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/autoload.php';

class WechatAPI {
    private $appId;
    private $appSecret;
    private $tokenFile;
    
    public function __construct() {
        $this->appId = WECHAT_APPID;
        $this->appSecret = WECHAT_APPSECRET;
        $this->tokenFile = __DIR__ . '/../storage/wechat_token.json';
        
        if (!is_dir(dirname($this->tokenFile))) {
            mkdir(dirname($this->tokenFile), 0755, true);
        }
    }
    
    /**
     * 获取access_token
     */
    public function getAccessToken() {
        if (file_exists($this->tokenFile)) {
            $tokenData = json_decode(file_get_contents($this->tokenFile), true);
            if ($tokenData && isset($tokenData['access_token']) && 
                isset($tokenData['expires_at']) && $tokenData['expires_at'] > time()) {
                return $tokenData['access_token'];
            }
        }
        
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->appId}&secret={$this->appSecret}";
        $response = $this->httpGet($url);
        $result = json_decode($response, true);
        
        if (isset($result['access_token'])) {
            $tokenData = [
                'access_token' => $result['access_token'],
                'expires_at' => time() + $result['expires_in'] - 300
            ];
            file_put_contents($this->tokenFile, json_encode($tokenData));
            return $result['access_token'];
        }
        
        throw new Exception('获取微信access_token失败: ' . ($result['errmsg'] ?? '未知错误'));
    }
    
    /**
     * 获取授权二维码URL
     */
    public function getAuthQrCode() {
        $accessToken = $this->getAccessToken();
        
        $data = [
            'expire_seconds' => 604800,
            'action_name' => 'QR_SCENE',
            'action_info' => [
                'scene' => [
                    'scene_id' => rand(100000, 999999)
                ]
            ]
        ];
        
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token={$accessToken}";
        $response = $this->httpPost($url, json_encode($data));
        $result = json_decode($response, true);
        
        if (isset($result['ticket'])) {
            $qrUrl = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=" . urlencode($result['ticket']);
            return [
                'qr_url' => $qrUrl,
                'ticket' => $result['ticket'],
                'expire_seconds' => $result['expire_seconds']
            ];
        }
        
        throw new Exception('生成二维码失败: ' . ($result['errmsg'] ?? '未知错误'));
    }
    
    /**
     * 发布素材到草稿箱
     */
    public function uploadDraft($title, $content, $thumbMediaId = '') {
        $accessToken = $this->getAccessToken();
        
        $data = [
            'articles' => [
                [
                    'title' => $title ?: '未命名文章',
                    'author' => '',
                    'digest' => '',
                    'content' => $content,
                    'content_source_url' => '',
                    'thumb_media_id' => $thumbMediaId,
                    'need_open_comment' => 0,
                    'only_fans_can_comment' => 0
                ]
            ]
        ];
        
        $url = "https://api.weixin.qq.com/cgi-bin/draft/add?access_token={$accessToken}";
        $response = $this->httpPost($url, json_encode($data, JSON_UNESCAPED_UNICODE));
        $result = json_decode($response, true);
        
        if (isset($result['media_id'])) {
            return [
                'media_id' => $result['media_id'],
                'success' => true
            ];
        }
        
        throw new Exception('发布草稿失败: ' . ($result['errmsg'] ?? '未知错误'));
    }
    
    /**
     * 上传图片素材
     */
    public function uploadImage($imagePath) {
        $accessToken = $this->getAccessToken();
        
        $url = "https://api.weixin.qq.com/cgi-bin/material/add_material?access_token={$accessToken}&type=image";
        
        $postData = [
            'media' => new CURLFile($imagePath)
        ];
        
        $response = $this->httpPost($url, $postData, true);
        $result = json_decode($response, true);
        
        if (isset($result['media_id'])) {
            return $result['media_id'];
        }
        
        throw new Exception('上传图片素材失败: ' . ($result['errmsg'] ?? '未知错误'));
    }
    
    private function httpGet($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
    
    private function httpPost($url, $data, $isMultipart = false) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        
        if ($isMultipart) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}

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

// 处理请求
try {
    // 检查 curl 扩展
    if (!extension_loaded('curl')) {
        throw new Exception('需要启用 curl 扩展才能使用微信公众号功能');
    }
    
    $wechat = new WechatAPI();
    
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_qrcode':
            $result = $wechat->getAuthQrCode();
            echo json_encode(['success' => true, 'data' => $result], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'publish':
            $input = parseInputData();
            
            if (!isset($input['content'])) {
                throw new Exception('缺少内容参数');
            }
            
            $title = $input['title'] ?? '未命名文章';
            $content = $input['content'];
            
            // 注意：微信公众号内容需要是HTML格式
            // 这里简化处理，实际使用时需要将Markdown转换为符合微信规范的HTML
            
            $result = $wechat->uploadDraft($title, $content);
            echo json_encode([
                'success' => true, 
                'message' => '文章已发布到草稿箱',
                'media_id' => $result['media_id']
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            throw new Exception('无效的操作');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
