(function() {
    'use strict';
    
    // DOM元素
    const editor = document.getElementById('markdown-editor');
    const preview = document.getElementById('markdown-preview');
    const articleTitle = document.getElementById('article-title');
    const btnUploadImage = document.getElementById('btn-upload-image');
    const imageUpload = document.getElementById('image-upload');
    const btnDownloadMd = document.getElementById('btn-download-md');
    const btnDownloadPdf = document.getElementById('btn-download-pdf');
    const btnPublishWechat = document.getElementById('btn-publish-wechat');
    const wechatModal = document.getElementById('wechat-modal');
    const modalClose = document.getElementById('modal-close');
    const stepQrcode = document.getElementById('step-qrcode');
    const stepPublish = document.getElementById('step-publish');
    const qrcodeContainer = document.getElementById('qrcode-container');
    const btnConfirmPublish = document.getElementById('btn-confirm-publish');
    const publishTitle = document.getElementById('publish-title');
    const publishChars = document.getElementById('publish-chars');
    const toast = document.getElementById('toast');
    
    // 分隔线拖拽
    const divider = document.getElementById('divider');
    const editorPanel = document.querySelector('.editor-panel');
    const previewPanel = document.querySelector('.preview-panel');
    let isDragging = false;
    
    // 初始化
    function init() {
        renderPreview();
        bindEvents();
    }
    
    // 绑定事件
    function bindEvents() {
        // 编辑器输入事件
        editor.addEventListener('input', debounce(renderPreview, 100));
        
        // 图片上传按钮
        btnUploadImage.addEventListener('click', () => {
            imageUpload.click();
        });
        
        // 文件选择
        imageUpload.addEventListener('change', handleImageUpload);
        
        // 下载MD
        btnDownloadMd.addEventListener('click', downloadMd);
        
        // 下载PDF
        btnDownloadPdf.addEventListener('click', downloadPdf);
        
        // 微信发布
        btnPublishWechat.addEventListener('click', openWechatModal);
        
        // 关闭弹窗
        modalClose.addEventListener('click', closeWechatModal);
        wechatModal.addEventListener('click', (e) => {
            if (e.target === wechatModal) {
                closeWechatModal();
            }
        });
        
        // 确认发布
        btnConfirmPublish.addEventListener('click', publishToWechat);
        
        // 分隔线拖拽
        divider.addEventListener('mousedown', startDrag);
        document.addEventListener('mousemove', drag);
        document.addEventListener('mouseup', endDrag);
    }
    
    // 渲染预览
    function renderPreview() {
        const markdown = editor.value;
        try {
            preview.innerHTML = marked.parse(markdown);
        } catch (e) {
            preview.innerHTML = '<p style="color: red;">解析错误: ' + e.message + '</p>';
        }
    }
    
    // 防抖函数
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }
    
    // 处理图片上传
    async function handleImageUpload(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        // 检查文件类型
        if (!file.type.startsWith('image/')) {
            showToast('请选择图片文件', 'error');
            return;
        }
        
        // 检查文件大小 (10MB)
        if (file.size > 10 * 1024 * 1024) {
            showToast('图片大小不能超过10MB', 'error');
            return;
        }
        
        const formData = new FormData();
        formData.append('image', file);
        
        try {
            showToast('正在上传...', '');
            
            const response = await fetch('api/upload.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // 将图片链接插入到编辑器光标位置
                const cursorPos = editor.selectionStart;
                const textBefore = editor.value.substring(0, cursorPos);
                const textAfter = editor.value.substring(cursorPos);
                const imageMarkdown = `![${file.name}](${result.url})`;
                
                editor.value = textBefore + imageMarkdown + textAfter;
                editor.selectionStart = editor.selectionEnd = cursorPos + imageMarkdown.length;
                editor.focus();
                
                renderPreview();
                showToast('图片上传成功', 'success');
            } else {
                showToast(result.message || '上传失败', 'error');
            }
        } catch (error) {
            console.error('上传错误:', error);
            showToast('上传失败: ' + error.message, 'error');
        }
        
        // 重置文件输入
        e.target.value = '';
    }
    
    // 下载MD文件
    function downloadMd() {
        const title = articleTitle.value.trim() || '未命名文章';
        const content = editor.value;
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'api/download_md.php';
        form.target = '_blank';
        
        const titleInput = document.createElement('input');
        titleInput.type = 'hidden';
        titleInput.name = 'title';
        titleInput.value = title;
        
        const contentInput = document.createElement('input');
        contentInput.type = 'hidden';
        contentInput.name = 'content';
        contentInput.value = content;
        
        form.appendChild(titleInput);
        form.appendChild(contentInput);
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
        
        showToast('开始下载MD文件', 'success');
    }
    
    // 下载PDF文件
    function downloadPdf() {
        const title = articleTitle.value.trim() || '未命名文章';
        const content = editor.value;
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'api/download_pdf.php';
        form.target = '_blank';
        
        const titleInput = document.createElement('input');
        titleInput.type = 'hidden';
        titleInput.name = 'title';
        titleInput.value = title;
        
        const contentInput = document.createElement('input');
        contentInput.type = 'hidden';
        contentInput.name = 'content';
        contentInput.value = content;
        
        form.appendChild(titleInput);
        form.appendChild(contentInput);
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
        
        showToast('开始下载PDF文件', 'success');
    }
    
    // 打开微信发布弹窗
    async function openWechatModal() {
        wechatModal.style.display = 'flex';
        stepQrcode.style.display = 'block';
        stepPublish.style.display = 'none';
        
        // 生成二维码
        try {
            qrcodeContainer.innerHTML = '<div class="loading">正在生成二维码...</div>';
            
            const response = await fetch('api/wechat.php?action=get_qrcode');
            const result = await response.json();
            
            if (result.success) {
                qrcodeContainer.innerHTML = `<img src="${result.data.qr_url}" alt="扫码授权">`;
                
                // 模拟轮询授权状态
                startPollingAuth();
            } else {
                qrcodeContainer.innerHTML = '<div class="loading" style="color: red;">生成二维码失败</div>';
                showToast(result.message || '生成二维码失败', 'error');
            }
        } catch (error) {
            qrcodeContainer.innerHTML = '<div class="loading" style="color: red;">请求失败</div>';
            showToast('请求失败: ' + error.message, 'error');
        }
    }
    
    // 关闭微信发布弹窗
    function closeWechatModal() {
        wechatModal.style.display = 'none';
    }
    
    // 模拟轮询授权状态
    function startPollingAuth() {
        // 这里简化处理，实际应该轮询微信服务器
        // 演示用：3秒后自动跳转到发布确认页面
        setTimeout(() => {
            stepQrcode.style.display = 'none';
            stepPublish.style.display = 'block';
            
            // 更新发布信息
            publishTitle.textContent = articleTitle.value || '未命名文章';
            publishChars.textContent = editor.value.length;
        }, 3000);
    }
    
    // 发布到微信
    async function publishToWechat() {
        const title = articleTitle.value.trim() || '未命名文章';
        const content = editor.value;
        
        // 转换为HTML
        let htmlContent;
        try {
            htmlContent = marked.parse(content);
        } catch (e) {
            htmlContent = '<p>' + content + '</p>';
        }
        
        try {
            btnConfirmPublish.disabled = true;
            btnConfirmPublish.textContent = '发布中...';
            
            const response = await fetch('api/wechat.php?action=publish', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    title: title,
                    content: htmlContent
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showToast('发布成功！文章已保存到公众号草稿箱', 'success');
                closeWechatModal();
            } else {
                showToast(result.message || '发布失败', 'error');
            }
        } catch (error) {
            showToast('发布失败: ' + error.message, 'error');
        } finally {
            btnConfirmPublish.disabled = false;
            btnConfirmPublish.textContent = '发布到公众号草稿箱';
        }
    }
    
    // 显示Toast提示
    function showToast(message, type) {
        toast.textContent = message;
        toast.className = 'toast show';
        if (type === 'success') {
            toast.classList.add('success');
        } else if (type === 'error') {
            toast.classList.add('error');
        }
        
        setTimeout(() => {
            toast.className = 'toast';
        }, 3000);
    }
    
    // 分隔线拖拽功能
    function startDrag(e) {
        isDragging = true;
        divider.classList.add('dragging');
        document.body.style.cursor = 'col-resize';
        document.body.style.userSelect = 'none';
    }
    
    function drag(e) {
        if (!isDragging) return;
        
        const container = document.querySelector('.main-content');
        const containerRect = container.getBoundingClientRect();
        const totalWidth = containerRect.width;
        const mouseX = e.clientX - containerRect.left;
        
        // 计算百分比
        let percentage = (mouseX / totalWidth) * 100;
        
        // 限制范围 (20% - 80%)
        percentage = Math.max(20, Math.min(80, percentage));
        
        editorPanel.style.flex = percentage;
        previewPanel.style.flex = 100 - percentage;
    }
    
    function endDrag() {
        isDragging = false;
        divider.classList.remove('dragging');
        document.body.style.cursor = '';
        document.body.style.userSelect = '';
    }
    
    // 启动应用
    init();
    
})();
