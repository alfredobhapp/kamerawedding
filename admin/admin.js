const Admin = {
    token: localStorage.getItem('wpr_admin_token') || '',
    photos: [],
    selectedIds: new Set(),

    init() {
        this.loginView = document.getElementById('login-view');
        this.dashboardView = document.getElementById('dashboard-view');
        this.errorMsg = document.getElementById('login-error');
        this.galleryEl = document.getElementById('admin-gallery');
        
        document.getElementById('login-form').addEventListener('submit', this.login.bind(this));
        document.getElementById('btn-logout').addEventListener('click', this.logout.bind(this));
        document.getElementById('btn-select-all').addEventListener('click', this.toggleSelectAll.bind(this));
        document.getElementById('btn-delete-selected').addEventListener('click', this.deleteSelected.bind(this));
        
        document.querySelectorAll('.admin-nav a').forEach(a => {
            a.addEventListener('click', (e) => {
                e.preventDefault();
                document.querySelectorAll('.admin-nav a').forEach(nav => nav.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(tab => tab.classList.add('hidden'));
                
                e.target.classList.add('active');
                document.getElementById(e.target.dataset.target).classList.remove('hidden');
            });
        });

        if (this.token) {
            this.showDashboard();
        } else {
            this.showLogin();
        }
    },

    showLogin() {
        this.loginView.classList.remove('hidden');
        this.dashboardView.classList.add('hidden');
    },

    showDashboard() {
        this.loginView.classList.add('hidden');
        this.dashboardView.classList.remove('hidden');
        this.loadData();
    },

    async request(url, options = {}) {
        options.headers = options.headers || {};
        options.headers['X-Csrf-Token'] = this.token;
        
        const res = await fetch(url, options);
        const data = await res.json();
        
        if (res.status === 401 || res.status === 403) {
            this.logout();
            throw new Error(data.error?.message || 'Unauthorized');
        }
        if (res.status >= 400) {
            throw new Error(data.error?.message || 'Error');
        }
        return data;
    },

    async login(e) {
        e.preventDefault();
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        const btn = document.getElementById('btn-login');
        
        btn.disabled = true;
        this.errorMsg.textContent = '';
        
        try {
            const res = await fetch('../api/admin/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, password })
            });
            const data = await res.json();
            
            if (res.ok) {
                this.token = data.token;
                localStorage.setItem('wpr_admin_token', this.token);
                this.showDashboard();
            } else {
                this.errorMsg.textContent = data.error?.message || 'Login gagal';
            }
        } catch (err) {
            this.errorMsg.textContent = 'Terjadi kesalahan jaringan';
        }
        btn.disabled = false;
    },

    logout() {
        this.token = '';
        localStorage.removeItem('wpr_admin_token');
        this.showLogin();
    },

    async loadData() {
        try {
            const stats = await this.request('../api/admin/stats');
            document.getElementById('stat-total-photos').textContent = stats.total_photos;
            document.getElementById('stat-total-size').textContent = stats.total_size_mb;

            const photos = await this.request('../api/admin/photos');
            this.photos = photos.items || [];
            this.selectedIds.clear();
            this.renderGallery();

            await this.loadSettings();
        } catch (e) {
            console.error(e);
        }
    },

    async loadSettings() {
        try {
            const res = await this.request('../api/admin/settings');
            const event = res.event || {};
            
            if (document.getElementById('set-event-title')) {
                document.getElementById('set-event-title').textContent = event.title || '-';
                document.getElementById('set-event-date').textContent = event.event_date || '-';
                document.getElementById('set-event-slug').textContent = event.slug || '-';
                document.getElementById('set-event-token').textContent = event.access_token || '-';
            }

            // QR Code setup
            const origin = window.location.origin;
            const pathParts = window.location.pathname.split('/');
            pathParts.pop(); // remove admin or file
            if (pathParts[pathParts.length - 1] === 'admin') pathParts.pop();
            const basePath = pathParts.join('/');
            const guestUrl = `${origin}${basePath}/?k=${event.access_token || ''}`;

            const qrUrlEl = document.getElementById('qr-target-url');
            if (qrUrlEl) qrUrlEl.textContent = guestUrl;

            const qrContainer = document.getElementById('qr-container');
            if (qrContainer) {
                // Generate QR Code image using pure SVG or Google Chart / quickchart fallback without heavy lib
                const encoded = encodeURIComponent(guestUrl);
                qrContainer.innerHTML = `<img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encoded}" alt="QR Code Tamu" width="200" height="200" style="display:block; border-radius: 4px;">`;
            }
        } catch (e) {
            console.error('Failed to load settings/QR', e);
        }
    },

    renderGallery() {
        this.galleryEl.innerHTML = '';
        this.photos.forEach(p => {
            const el = document.createElement('div');
            el.className = `admin-item ${this.selectedIds.has(p.id) ? 'selected' : ''}`;
            el.onclick = () => this.toggleSelect(p.id, el);
            
            el.innerHTML = `
                <div class="checkbox-overlay"></div>
                <img src="../${p.thumb}" loading="lazy">
                ${p.note ? `<div class="note-overlay">${p.note}</div>` : ''}
            `;
            this.galleryEl.appendChild(el);
        });
        this.updateToolbar();
    },

    toggleSelect(id, el) {
        if (this.selectedIds.has(id)) {
            this.selectedIds.delete(id);
            el.classList.remove('selected');
        } else {
            this.selectedIds.add(id);
            el.classList.add('selected');
        }
        this.updateToolbar();
    },

    toggleSelectAll() {
        if (this.selectedIds.size === this.photos.length && this.photos.length > 0) {
            this.selectedIds.clear();
        } else {
            this.photos.forEach(p => this.selectedIds.add(p.id));
        }
        this.renderGallery();
    },

    updateToolbar() {
        const count = this.selectedIds.size;
        document.getElementById('selected-count').textContent = `Dipilih: ${count}`;
        document.getElementById('btn-delete-selected').disabled = count === 0;
    },

    async deleteSelected() {
        if (!confirm(`Hapus ${this.selectedIds.size} foto secara permanen?`)) return;
        
        const btn = document.getElementById('btn-delete-selected');
        btn.disabled = true;
        btn.textContent = 'Menghapus...';
        
        try {
            await this.request('../api/admin/photos/bulk', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', ids: Array.from(this.selectedIds) })
            });
            await this.loadData();
        } catch (e) {
            alert('Gagal menghapus: ' + e.message);
        }
        
        btn.textContent = 'Hapus Terpilih';
        this.updateToolbar();
    }
};

document.addEventListener('DOMContentLoaded', () => {
    Admin.init();
});
