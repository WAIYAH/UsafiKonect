/**
 * UsafiKonect - Main JavaScript
 * Utility functions, AJAX helpers, toast notifications
 */

const UsafiKonect = {
    baseUrl: document.querySelector('meta[name="base-url"]')?.content || '',

    /** Show toast notification */
    toast(message, type = 'info', duration = 4000) {
        const container = document.getElementById('toast-container');
        if (!container) return;
        
        const icons = { success: 'fa-check-circle', error: 'fa-exclamation-circle', warning: 'fa-exclamation-triangle', info: 'fa-info-circle' };
        const colors = { success: 'bg-green-500', error: 'bg-red-500', warning: 'bg-yellow-500', info: 'bg-blue-500' };
        
        const toast = document.createElement('div');
        toast.className = `toast-enter flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg text-white ${colors[type] || colors.info} min-w-[280px] max-w-sm`;
        toast.innerHTML = `
            <i class="fas ${icons[type] || icons.info}"></i>
            <span class="flex-1 text-sm">${this.escapeHtml(message)}</span>
            <button onclick="this.parentElement.remove()" class="text-white/70 hover:text-white"><i class="fas fa-times"></i></button>
        `;
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.replace('toast-enter', 'toast-exit');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    },

    /** Escape HTML to prevent XSS */
    escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },

    /** AJAX helper with CSRF token */
    async fetch(url, options = {}) {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        const defaults = {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': csrf || '' }
        };
        
        if (options.body instanceof FormData) {
            // Don't set Content-Type for FormData
        } else if (options.body && typeof options.body === 'object') {
            defaults.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(options.body);
        }
        
        const config = { ...defaults, ...options, headers: { ...defaults.headers, ...options.headers } };
        
        try {
            const response = await fetch(this.baseUrl + url, config);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const contentType = response.headers.get('content-type');
            return contentType?.includes('application/json') ? response.json() : response.text();
        } catch (err) {
            this.toast('Network error. Please try again.', 'error');
            throw err;
        }
    },

    /** Format currency - KES */
    formatCurrency(amount) {
        return 'KES ' + Number(amount).toLocaleString('en-KE');
    },

    /** Time ago helper */
    timeAgo(dateStr) {
        const seconds = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
        const intervals = [
            [31536000, 'year'], [2592000, 'month'], [86400, 'day'],
            [3600, 'hour'], [60, 'minute'], [1, 'second']
        ];
        for (const [secs, label] of intervals) {
            const count = Math.floor(seconds / secs);
            if (count >= 1) return `${count} ${label}${count > 1 ? 's' : ''} ago`;
        }
        return 'just now';
    },

    /** Confirm dialog wrapper */
    async confirm(message, title = 'Confirm Action') {
        return new Promise(resolve => {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 z-[9999] flex items-center justify-center p-4';
            modal.innerHTML = `
                <div class="fixed inset-0 bg-black/50" onclick="this.parentElement.remove()"></div>
                <div class="relative bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full z-10">
                    <h3 class="text-lg font-bold text-gray-800 mb-2">${this.escapeHtml(title)}</h3>
                    <p class="text-gray-600 text-sm mb-6">${this.escapeHtml(message)}</p>
                    <div class="flex gap-3 justify-end">
                        <button class="cancel-btn px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-sm">Cancel</button>
                        <button class="confirm-btn px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 text-sm">Confirm</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            modal.querySelector('.cancel-btn').onclick = () => { modal.remove(); resolve(false); };
            modal.querySelector('.confirm-btn').onclick = () => { modal.remove(); resolve(true); };
        });
    },

    /** Counter animation for stats */
    animateCounter(element, target, duration = 2000) {
        const start = 0;
        const startTime = performance.now();
        const update = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3); // easeOutCubic
            element.textContent = Math.floor(start + (target - start) * eased).toLocaleString();
            if (progress < 1) requestAnimationFrame(update);
        };
        requestAnimationFrame(update);
    },

    /** Debounce function */
    debounce(func, wait = 300) {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    },

    /** Initialize common behaviors */
    init() {
        // Auto-dismiss flash messages
        document.querySelectorAll('[data-auto-dismiss]').forEach(el => {
            const delay = parseInt(el.dataset.autoDismiss) || 5000;
            setTimeout(() => {
                el.style.transition = 'opacity 0.3s ease';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 300);
            }, delay);
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(link => {
            link.addEventListener('click', e => {
                const target = document.querySelector(link.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });

        // File upload preview
        document.querySelectorAll('input[type="file"][data-preview]').forEach(input => {
            input.addEventListener('change', e => {
                const preview = document.getElementById(input.dataset.preview);
                if (preview && e.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = ev => { preview.src = ev.target.result; preview.classList.remove('hidden'); };
                    reader.readAsDataURL(e.target.files[0]);
                }
            });
        });
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => UsafiKonect.init());
