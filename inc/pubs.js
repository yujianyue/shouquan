/**
 * 本文件功能: 公共JavaScript函数库
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

// AJAX通信函数
function ajax(options) {
    options = options || {};
    options.type = (options.type || 'GET').toUpperCase();
    options.dataType = options.dataType || 'json';
    options.async = options.async !== false;
    
    const params = formatParams(options.data);
    let xhr = new XMLHttpRequest();
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            const status = xhr.status;
            if (status >= 200 && status < 300) {
                let result;
                try {
                    result = options.dataType === 'json' ? JSON.parse(xhr.responseText) : xhr.responseText;
                } catch (e) {
                    result = xhr.responseText;
                }
                options.success && options.success(result, xhr.statusText);
            } else {
                options.error && options.error(status, xhr.statusText);
            }
        }
    };
    
    if (options.type === 'GET') {
        xhr.open('GET', options.url + (params ? '?' + params : ''), options.async);
        xhr.send(null);
    } else if (options.type === 'POST') {
        xhr.open('POST', options.url, options.async);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send(params);
    }
}

// 格式化参数
function formatParams(data) {
    if (!data) return '';
    let arr = [];
    for (let name in data) {
        arr.push(encodeURIComponent(name) + '=' + encodeURIComponent(data[name]));
    }
    return arr.join('&');
}

// 获取指定ID表单的所有值
function getFormValues(formId) {
    const form = document.getElementById(formId);
    if (!form) return {};
    
    const formData = {};
    const elements = form.elements;
    
    for (let i = 0; i < elements.length; i++) {
        const element = elements[i];
        if (!element.name) continue;
        
        if (element.type === 'checkbox' || element.type === 'radio') {
            if (element.checked) {
                formData[element.name] = element.value;
            }
        } else if (element.type !== 'button' && element.type !== 'submit' && element.type !== 'reset') {
            formData[element.name] = element.value;
        }
    }
    
    return formData;
}

// AJAX分页函数
function ajaxPagination(options) {
    const container = document.getElementById(options.container);
    if (!container) return;
    
    // 加载数据
    function loadData(page) {
        showLoading();
        
        const data = options.data || {};
        data.page = page || 1;
        
        ajax({
            url: options.url,
            data: data,
            type: options.type || 'GET',
            success: function(res) {
                hideLoading();
                
                if (res.code === 0) {
                    // 渲染内容
                    if (options.renderContent) {
                        options.renderContent(res.data.data);
                    }
                    
                    // 渲染分页
                    renderPagination(res.data);
                } else {
                    showToast(res.msg || '加载失败');
                }
            },
            error: function() {
                hideLoading();
                showToast('网络错误，请稍后重试');
            }
        });
    }
    
    // 渲染分页
    function renderPagination(data) {
        const page = parseInt(data.page) || 1;
        const totalPages = parseInt(data.total_pages) || 1;
        
        let html = '<div class="pagination">';
        
        // 首页和上一页
        if (totalPages <= 1) {
            html += '<span class="page-item disabled">首页</span>';
            html += '<span class="page-item disabled">上一页</span>';
        } else {
            if (page === 1) {
                html += '<span class="page-item disabled">首页</span>';
                html += '<span class="page-item disabled">上一页</span>';
            } else {
                html += '<a class="page-item" href="javascript:;" data-page="1">首页</a>';
                html += '<a class="page-item" href="javascript:;" data-page="' + (page - 1) + '">上一页</a>';
            }
        }
        
        // 页码下拉
        html += '<select class="page-select">';
        for (let i = 1; i <= totalPages; i++) {
            html += '<option value="' + i + '"' + (i === page ? ' selected' : '') + '>' + i + '</option>';
        }
        html += '</select>';
        
        // 下一页和尾页
        if (totalPages <= 1) {
            html += '<span class="page-item disabled">下一页</span>';
            html += '<span class="page-item disabled">尾页</span>';
        } else {
            if (page === totalPages) {
                html += '<span class="page-item disabled">下一页</span>';
                html += '<span class="page-item disabled">尾页</span>';
            } else {
                html += '<a class="page-item" href="javascript:;" data-page="' + (page + 1) + '">下一页</a>';
                html += '<a class="page-item" href="javascript:;" data-page="' + totalPages + '">尾页</a>';
            }
        }
        
        html += '</div>';
        
        const paginationContainer = document.getElementById(options.paginationContainer || 'pagination');
        if (paginationContainer) {
            paginationContainer.innerHTML = html;
            
            // 绑定页码点击事件
            const pageItems = paginationContainer.querySelectorAll('.page-item');
            for (let i = 0; i < pageItems.length; i++) {
                const item = pageItems[i];
                if (!item.classList.contains('disabled')) {
                    item.addEventListener('click', function() {
                        const page = parseInt(this.getAttribute('data-page'));
                        loadData(page);
                    });
                }
            }
            
            // 绑定下拉选择事件
            const pageSelect = paginationContainer.querySelector('.page-select');
            if (pageSelect) {
                pageSelect.addEventListener('change', function() {
                    const page = parseInt(this.value);
                    loadData(page);
                });
            }
        }
    }
    
    // 初始加载
    loadData(options.page || 1);
}

// 遮罩层
let overlay = null;
function showOverlay(content, title, buttons) {
    // 创建遮罩层
    overlay = document.createElement('div');
    overlay.className = 'overlay';
    
    // 创建内容容器
    const container = document.createElement('div');
    container.className = 'overlay-container';
    
    // 标题栏
    const titleBar = document.createElement('div');
    titleBar.className = 'overlay-title';
    titleBar.innerHTML = title || '提示';
    
    // 关闭按钮
    const closeBtn = document.createElement('span');
    closeBtn.className = 'overlay-close';
    closeBtn.innerHTML = '&times;';
    closeBtn.addEventListener('click', hideOverlay);
    titleBar.appendChild(closeBtn);
    container.appendChild(titleBar);
    
    // 内容区
    const contentDiv = document.createElement('div');
    contentDiv.className = 'overlay-content';
    contentDiv.innerHTML = content;
    container.appendChild(contentDiv);
    
    // 按钮区
    if (buttons && buttons.length) {
        const btnArea = document.createElement('div');
        btnArea.className = 'overlay-buttons';
        
        for (let i = 0; i < buttons.length; i++) {
            const btn = document.createElement('button');
            btn.innerHTML = buttons[i].text;
            btn.className = 'btn ' + (buttons[i].className || '');
            
            if (buttons[i].click) {
                btn.addEventListener('click', function() {
                    buttons[i].click();
                });
            } else {
                btn.addEventListener('click', hideOverlay);
            }
            
            btnArea.appendChild(btn);
        }
        
        container.appendChild(btnArea);
    }
    
    overlay.appendChild(container);
    document.body.appendChild(overlay);
    
    // 禁止滚动
    document.body.style.overflow = 'hidden';
}

function hideOverlay() {
    if (overlay) {
        document.body.removeChild(overlay);
        overlay = null;
        document.body.style.overflow = '';
    }
}

// 吐司提示
let toast = null;
let toastTimer = null;
function showToast(message, duration) {
    hideToast();
    
    duration = duration || 3000;
    
    toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = message;
    
    document.body.appendChild(toast);
    
    toastTimer = setTimeout(function() {
        hideToast();
    }, duration);
}

function hideToast() {
    if (toast) {
        document.body.removeChild(toast);
        toast = null;
    }
    
    if (toastTimer) {
        clearTimeout(toastTimer);
        toastTimer = null;
    }
}

// 加载提示
let loading = null;
function showLoading() {
    if (loading) return;
    
    loading = document.createElement('div');
    loading.className = 'loading-overlay';
    loading.innerHTML = '<div class="loading-spinner"></div>';
    
    document.body.appendChild(loading);
}

function hideLoading() {
    if (loading) {
        document.body.removeChild(loading);
        loading = null;
    }
}

// 切换标签页
function initTabs(container) {
    const tabContainer = document.getElementById(container);
    if (!tabContainer) return;
    
    const tabs = tabContainer.querySelectorAll('.tab-item');
    const contents = tabContainer.querySelectorAll('.tab-content');
    
    for (let i = 0; i < tabs.length; i++) {
        tabs[i].addEventListener('click', function() {
            // 移除所有激活状态
            for (let j = 0; j < tabs.length; j++) {
                tabs[j].classList.remove('active');
                contents[j].classList.remove('active');
            }
            
            // 设置当前激活
            this.classList.add('active');
            contents[i].classList.add('active');
        });
    }
}

// DOM加载完成
document.addEventListener('DOMContentLoaded', function() {
    // 自动初始化所有标签页
    initTabs('tabs');
});
