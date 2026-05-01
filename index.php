<?php
/**
 * Markdown在线编辑器主页
 */
require_once __DIR__ . '/config/autoload.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Markdown在线编辑器</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
</head>
<body>
    <div class="container">
        <!-- 顶部工具栏 -->
        <header class="toolbar">
            <div class="toolbar-left">
                <h1>Markdown Editor</h1>
                <input type="text" id="article-title" placeholder="文章标题..." value="未命名文章">
            </div>
            <div class="toolbar-right">
                <button id="btn-upload-image" class="btn btn-secondary">
                    <span class="icon">📷</span> 上传图片
                </button>
                <button id="btn-download-md" class="btn btn-secondary">
                    <span class="icon">📝</span> 下载MD
                </button>
                <button id="btn-download-pdf" class="btn btn-secondary">
                    <span class="icon">📄</span> 下载PDF
                </button>
                <button id="btn-publish-wechat" class="btn btn-primary">
                    <span class="icon">💬</span> 发布到公众号
                </button>
            </div>
        </header>
        
        <!-- 主内容区 -->
        <main class="main-content">
            <!-- 左侧编辑器 -->
            <section class="editor-panel">
                <div class="panel-header">
                    <span class="panel-title">编辑器</span>
                </div>
                <textarea id="markdown-editor" placeholder="在这里输入Markdown内容...">
# 欢迎使用Markdown编辑器

这是一个功能强大的在线Markdown编辑器。

## 功能特点

- **实时预览**：左侧编辑，右侧实时预览
- **图片上传**：支持本地图片上传，自动生成链接
- **导出功能**：支持导出为MD文件和PDF文件
- **微信发布**：支持直接发布到微信公众号

## 示例

### 代码块

```php
<?php
echo "Hello, World!";
?>
```

### 表格

| 功能 | 状态 |
|------|------|
| 编辑器 | ✅ |
| 预览 | ✅ |
| 图片上传 | ✅ |
| 下载MD | ✅ |
| 下载PDF | ✅ |
| 微信发布 | ✅ |

### 引用

> 这是一段引用文本。

### 链接和图片

[访问GitHub](https://github.com)

![示例图片](https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=beautiful%20landscape%20with%20mountains%20and%20lake&image_size=square)

---

开始你的创作吧！
</textarea>
            </section>
            
            <!-- 分隔线 -->
            <div class="divider" id="divider"></div>
            
            <!-- 右侧预览 -->
            <section class="preview-panel">
                <div class="panel-header">
                    <span class="panel-title">预览</span>
                </div>
                <div id="markdown-preview" class="preview-content"></div>
            </section>
        </main>
    </div>
    
    <!-- 隐藏的文件输入 -->
    <input type="file" id="image-upload" accept="image/*" style="display: none;">
    
    <!-- 微信发布弹窗 -->
    <div id="wechat-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>发布到微信公众号</h3>
                <button class="modal-close" id="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="wechat-steps">
                    <div class="step" id="step-qrcode">
                        <h4>步骤1：扫码授权</h4>
                        <div class="qrcode-container" id="qrcode-container">
                            <div class="loading">正在生成二维码...</div>
                        </div>
                        <p class="tip">请使用微信扫描二维码完成授权</p>
                    </div>
                    <div class="step" id="step-publish" style="display: none;">
                        <h4>步骤2：确认发布</h4>
                        <div class="publish-info">
                            <p><strong>文章标题：</strong><span id="publish-title"></span></p>
                            <p><strong>字符数：</strong><span id="publish-chars"></span></p>
                        </div>
                        <button id="btn-confirm-publish" class="btn btn-primary btn-lg">
                            发布到公众号草稿箱
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 消息提示 -->
    <div id="toast" class="toast"></div>
    
    <script src="assets/js/app.js"></script>
</body>
</html>
