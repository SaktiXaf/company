/**
 * Admin Content Editor JavaScript
 * Real-time preview and content management
 */

class ContentEditor {
    constructor() {
        this.apiUrl = 'api/content.php';
        this.previewFrame = null;
        this.autoSave = true;
        this.saveTimeout = null;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.initPreview();
        this.startAutoRefresh();
    }
    
    bindEvents() {
        // Auto-save on form changes
        document.querySelectorAll('input, textarea, select').forEach(element => {
            element.addEventListener('input', () => {
                if (this.autoSave) {
                    this.scheduleAutoSave();
                }
                this.updatePreview();
            });
        });
        
        // Form submissions
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', (e) => {
                this.handleFormSubmit(e);
            });
        });
        
        // Preview refresh button
        const refreshBtn = document.getElementById('refresh-preview');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.refreshPreview();
            });
        }
        
        // Auto-save toggle
        const autoSaveToggle = document.getElementById('auto-save-toggle');
        if (autoSaveToggle) {
            autoSaveToggle.addEventListener('change', (e) => {
                this.autoSave = e.target.checked;
            });
        }
    }
    
    initPreview() {
        this.previewFrame = document.querySelector('iframe');
        if (this.previewFrame) {
            this.previewFrame.addEventListener('load', () => {
                this.injectPreviewStyles();
            });
        }
    }
    
    injectPreviewStyles() {
        if (!this.previewFrame) return;
        
        try {
            const previewDoc = this.previewFrame.contentDocument;
            if (previewDoc) {
                // Add preview indicator
                const indicator = previewDoc.createElement('div');
                indicator.innerHTML = '📝 Live Preview Mode';
                indicator.style.cssText = `
                    position: fixed;
                    top: 0;
                    right: 0;
                    background: #007AFF;
                    color: white;
                    padding: 8px 16px;
                    font-size: 12px;
                    z-index: 9999;
                    border-radius: 0 0 0 8px;
                `;
                previewDoc.body.appendChild(indicator);
            }
        } catch (e) {
            console.log('Cannot inject styles into preview (cross-origin)');
        }
    }
    
    scheduleAutoSave() {
        if (this.saveTimeout) {
            clearTimeout(this.saveTimeout);
        }
        
        this.saveTimeout = setTimeout(() => {
            this.autoSaveContent();
        }, 2000); // Auto-save after 2 seconds of inactivity
    }
    
    async autoSaveContent() {
        const forms = document.querySelectorAll('form');
        
        for (const form of forms) {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            
            // Determine content type from form
            let contentType = 'hero';
            if (form.querySelector('[name="about_title"]')) {
                contentType = 'about';
            } else if (form.querySelector('[name="contact_title"]')) {
                contentType = 'contact';
            }
            
            try {
                await this.saveContent(contentType, data);
                this.showNotification('Auto-saved successfully', 'success');
            } catch (error) {
                this.showNotification('Auto-save failed: ' + error.message, 'error');
            }
        }
    }
    
    async saveContent(type, data) {
        const response = await fetch(this.apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'update',
                type: type,
                data: data
            })
        });
        
        const result = await response.json();
        if (!result.success) {
            throw new Error(result.error);
        }
        
        return result;
    }
    
    updatePreview() {
        if (this.previewFrame) {
            // Debounce preview updates
            clearTimeout(this.previewTimeout);
            this.previewTimeout = setTimeout(() => {
                this.refreshPreview();
            }, 1000);
        }
    }
    
    refreshPreview() {
        if (this.previewFrame) {
            this.previewFrame.src = this.previewFrame.src;
        }
    }
    
    startAutoRefresh() {
        // Refresh preview every 30 seconds
        setInterval(() => {
            if (this.previewFrame && document.visibilityState === 'visible') {
                this.refreshPreview();
            }
        }, 30000);
    }
    
    handleFormSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            submitBtn.disabled = true;
            
            // Restore button after 2 seconds
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 2000);
        }
        
        // Submit form normally
        form.submit();
    }
    
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `admin-notification admin-notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i>
            ${message}
            <button class="admin-notification-close">&times;</button>
        `;
        
        // Add styles
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#d4edda' : '#f8d7da'};
            color: ${type === 'success' ? '#155724' : '#721c24'};
            padding: 12px 16px;
            border-radius: 8px;
            border-left: 4px solid ${type === 'success' ? '#28a745' : '#dc3545'};
            z-index: 10000;
            min-width: 300px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 8px;
            animation: slideIn 0.3s ease;
        `;
        
        document.body.appendChild(notification);
        
        // Close button
        const closeBtn = notification.querySelector('.admin-notification-close');
        closeBtn.addEventListener('click', () => {
            notification.remove();
        });
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('.admin-body')) {
        new ContentEditor();
    }
});

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .admin-notification-close {
        background: none;
        border: none;
        color: inherit;
        cursor: pointer;
        font-size: 18px;
        padding: 0;
        margin-left: auto;
    }
    
    .admin-notification-close:hover {
        opacity: 0.7;
    }
`;
document.head.appendChild(style);