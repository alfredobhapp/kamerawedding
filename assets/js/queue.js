const UploadQueue = {
    queue: [],
    activeCount: 0,
    maxConcurrent: 2,

    init() {
        this.listEl = document.getElementById('upload-list');
        this.sheetEl = document.getElementById('upload-sheet');
        this.countEl = document.getElementById('upload-count');
        
        document.getElementById('btn-close-sheet').addEventListener('click', () => {
            this.sheetEl.classList.add('hidden');
        });
    },

    addFiles(files) {
        if (files.length === 0) return;
        this.sheetEl.classList.remove('hidden');
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const item = {
                id: Pipeline.generateUUID(),
                file: file,
                previewUrl: URL.createObjectURL(file),
                note: '',
                status: 'pending',
                progress: 0,
                retries: 0
            };
            this.queue.push(item);
            this.renderItem(item);
        }
        
        this.updateTotal();
        this.processQueue();
    },

    updateTotal() {
        this.countEl.textContent = this.queue.length;
    },

    renderItem(item) {
        let el = document.getElementById(`upload-${item.id}`);
        if (!el) {
            el = document.createElement('div');
            el.id = `upload-${item.id}`;
            el.className = 'upload-item';
            this.listEl.appendChild(el);
        }
        
        let statusText = '';
        if (item.status === 'pending') statusText = 'Menunggu...';
        else if (item.status === 'processing') statusText = 'Memproses...';
        else if (item.status === 'uploading') statusText = `${item.progress}%`;
        else if (item.status === 'success') statusText = 'Terunggah ✓';
        else if (item.status === 'error') statusText = 'Gagal';

        const index = this.queue.indexOf(item) + 1;
        el.innerHTML = `
            <div class="upload-item-header">
                <img src="${item.previewUrl}" class="upload-item-thumb" alt="Preview foto">
                <div class="upload-item-details">
                    <div class="upload-item-info">
                        <span class="upload-item-name">Foto ${index}</span>
                        <span class="upload-item-status ${item.status}">${statusText}</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill ${item.status}" style="width: ${item.progress}%"></div>
                    </div>
                </div>
            </div>
            <textarea class="upload-item-note" placeholder="Tulis ucapan/catatan untuk foto ini (opsional)..." rows="1">${item.note || ''}</textarea>
            ${item.status === 'error' ? `<button onclick="UploadQueue.retry('${item.id}')" class="btn-retry">Coba Lagi</button>` : ''}
        `;

        const textarea = el.querySelector('.upload-item-note');
        textarea.oninput = (e) => {
            item.note = e.target.value;
        };
        // Disable note editing once successfully uploaded
        if (item.status === 'success') {
            textarea.disabled = true;
        }
    },

    retry(id) {
        const item = this.queue.find(q => q.id === id);
        if (item) {
            item.status = 'pending';
            item.retries = 0;
            item.progress = 0;
            this.renderItem(item);
            this.processQueue();
        }
    },

    async processQueue() {
        if (this.activeCount >= this.maxConcurrent) return;

        const next = this.queue.find(q => q.status === 'pending');
        if (!next) {
            const pending = this.queue.filter(q => q.status !== 'success' && q.status !== 'error').length;
            if (pending === 0) window.onbeforeunload = null;
            return;
        }

        window.onbeforeunload = () => "Sedang mengunggah. Biarkan halaman terbuka.";
        this.activeCount++;
        next.status = 'processing';
        this.renderItem(next);

        try {
            const processed = await Pipeline.process(next.file);
            next.status = 'uploading';
            this.renderItem(next);
            await this.uploadFile(next, processed);
            next.status = 'success';
            next.progress = 100;
        } catch (err) {
            console.error(err);
            next.status = 'error';
        }
        
        this.renderItem(next);
        this.activeCount--;
        this.processQueue();
    },

    uploadFile(item, processed) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'api/photos');
            xhr.setRequestHeader('X-Requested-With', 'wpr');
            
            xhr.upload.onprogress = (e) => {
                if (e.lengthComputable) {
                    item.progress = Math.round((e.loaded / e.total) * 100);
                    this.renderItem(item);
                }
            };

            xhr.onload = () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    resolve(JSON.parse(xhr.responseText));
                } else if (xhr.status === 429 || xhr.status >= 500) {
                    if (item.retries < 3) {
                        item.retries++;
                        const delay = Math.pow(3, item.retries) * 1000 + Math.random() * 1000;
                        setTimeout(() => this.retryUpload(item, processed, resolve, reject), delay);
                    } else reject(new Error(`Server error: ${xhr.status}`));
                } else reject(new Error(`Error ${xhr.status}: ${xhr.responseText}`));
            };

            xhr.onerror = () => {
                if (item.retries < 3) {
                    item.retries++;
                    const delay = Math.pow(3, item.retries) * 1000 + Math.random() * 1000;
                    setTimeout(() => this.retryUpload(item, processed, resolve, reject), delay);
                } else reject(new Error('Network Error'));
            };

            const fd = new FormData();
            fd.append('file', processed.blob, processed.originalName.replace(/\.[^/.]+$/, "") + ".jpg");
            fd.append('client_uuid', item.id);
            fd.append('sha256', processed.sha256);
            fd.append('width', processed.width);
            fd.append('height', processed.height);
            if (item.note) fd.append('guest_note', item.note);

            xhr.send(fd);
        });
    },

    retryUpload(item, processed, resolve, reject) {
        item.status = 'uploading';
        item.progress = 0;
        this.renderItem(item);
        this.uploadFile(item, processed).then(resolve).catch(reject);
    }
};

document.addEventListener('DOMContentLoaded', () => {
    UploadQueue.init();

    const cameraInput = document.getElementById('camera-input');
    const galleryInput = document.getElementById('upload-gallery');

    if (cameraInput) {
        cameraInput.addEventListener('change', (e) => {
            UploadQueue.addFiles(e.target.files);
            e.target.value = '';
        });
    }

    if (galleryInput) {
        galleryInput.addEventListener('change', (e) => {
            UploadQueue.addFiles(e.target.files);
            e.target.value = '';
        });
    }
});
