const Gallery = {
    cursor: null,
    loading: false,
    hasMore: true,
    items: [],

    init() {
        this.galleryView = document.getElementById('gallery-view');
        this.homeView = document.getElementById('home-view');
        this.gridEl = document.getElementById('gallery-grid');
        this.lightbox = document.getElementById('lightbox');
        
        window.addEventListener('hashchange', this.onHashChange.bind(this));
        this.onHashChange();
        
        this.setupIntersectionObserver();
        this.setupLightbox();
        
        setInterval(() => this.pollNewPhotos(), 20000);
    },

    onHashChange() {
        if (location.hash === '#/galeri') {
            this.homeView.style.display = 'none';
            this.galleryView.style.display = 'block';
            if (this.items.length === 0) this.loadPhotos();
        } else {
            this.homeView.style.display = 'flex';
            this.galleryView.style.display = 'none';
        }
    },

    async loadPhotos() {
        if (this.loading || !this.hasMore) return;
        this.loading = true;
        
        try {
            const url = this.cursor ? `api/photos?cursor=${this.cursor}` : 'api/photos';
            const res = await fetch(url);
            const data = await res.json();
            
            if (data.items) {
                this.renderItems(data.items);
                this.items = this.items.concat(data.items);
            }
            
            this.cursor = data.next_cursor;
            this.hasMore = !!this.cursor;
        } catch (e) {
            console.error(e);
        }
        this.loading = false;
    },

    renderItems(items) {
        items.forEach(item => {
            const aspect = (item.w / item.h) || 1;
            const el = document.createElement('div');
            el.className = 'grid-item';
            el.style.aspectRatio = aspect;
            el.innerHTML = `<img src="${item.thumb}" loading="lazy" decoding="async" alt="Foto ${item.id}">`;
            el.onclick = () => this.openLightbox(item);
            this.gridEl.appendChild(el);
        });
    },

    setupIntersectionObserver() {
        const sentinel = document.getElementById('gallery-sentinel');
        if (!window.IntersectionObserver) return;
        
        const io = new IntersectionObserver(entries => {
            if (entries[0].isIntersecting) {
                this.loadPhotos();
            }
        });
        io.observe(sentinel);
    },

    async pollNewPhotos() {
        if (location.hash !== '#/galeri' || document.hidden) return;
        // TBD: Gunakan endpoint polling di V1
    },

    setupLightbox() {
        this.lbImg = document.getElementById('lb-img');
        this.lbNote = document.getElementById('lb-note');
        this.lbDownload = document.getElementById('lb-download');
        
        document.getElementById('lb-close').onclick = () => this.closeLightbox();
    },

    openLightbox(item) {
        this.lightbox.classList.remove('hidden');
        this.lbImg.src = item.full;
        this.lbNote.textContent = item.note || '';
        
        this.lbDownload.onclick = async () => {
            const url = `api/photos/${item.id}/download`;
            if (navigator.share) {
                try {
                    const res = await fetch(url);
                    const blob = await res.blob();
                    const file = new File([blob], `foto-${item.id}.jpg`, { type: blob.type });
                    await navigator.share({ files: [file] });
                    return;
                } catch (e) {
                    console.log('Share API dibatalkan/gagal, fallback ke donwload biasa', e);
                }
            }
            
            const a = document.createElement('a');
            a.href = url;
            a.download = '';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        };
    },

    closeLightbox() {
        this.lightbox.classList.add('hidden');
        this.lbImg.src = '';
    }
};

document.addEventListener('DOMContentLoaded', () => {
    Gallery.init();
});
