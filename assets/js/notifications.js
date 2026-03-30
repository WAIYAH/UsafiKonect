/**
 * UsafiKonect - Notification handlers
 * Real-time notification polling and display
 */

const NotificationManager = {
    pollInterval: 30000,
    timer: null,

    init() {
        this.updateBadge();
        this.timer = setInterval(() => this.poll(), this.pollInterval);
    },

    async poll() {
        try {
            const data = await UsafiKonect.fetch('/api/notifications.php?action=count', { method: 'GET' });
            if (data && typeof data.count !== 'undefined') {
                this.setBadgeCount(data.count);
            }
        } catch (e) {
            // Silently fail - not critical
        }
    },

    setBadgeCount(count) {
        document.querySelectorAll('.notification-badge').forEach(badge => {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        });
    },

    updateBadge() {
        const badges = document.querySelectorAll('.notification-badge');
        badges.forEach(badge => {
            const count = parseInt(badge.textContent) || 0;
            badge.style.display = count > 0 ? 'flex' : 'none';
        });
    },

    async markAsRead(id) {
        const form = new FormData();
        form.append('action', 'mark_read');
        form.append('id', id);
        await UsafiKonect.fetch('/api/notifications.php', { method: 'POST', body: form });
        this.poll();
    },

    async markAllRead() {
        const form = new FormData();
        form.append('action', 'mark_all_read');
        await UsafiKonect.fetch('/api/notifications.php', { method: 'POST', body: form });
        this.poll();
        UsafiKonect.toast('All notifications marked as read', 'success');
    },

    destroy() {
        if (this.timer) clearInterval(this.timer);
    }
};

document.addEventListener('DOMContentLoaded', () => NotificationManager.init());
