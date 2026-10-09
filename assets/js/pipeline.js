const Pipeline = {
    MAX_DIMENSION: 2048,
    JPEG_QUALITY: 0.82,

    async process(file) {
        const ext = file.name.split('.').pop().toLowerCase();
        let isHeic = file.type === 'image/heic' || file.type === 'image/heif' || ext === 'heic' || ext === 'heif';
        let imageBlob = file;
        
        if (isHeic) {
            imageBlob = await this.convertHeic(file);
        }

        return await this.resizeAndEncode(imageBlob, file.name);
    },

    async convertHeic(file) {
        if (typeof heic2any === 'undefined') {
            await this.loadScript('/assets/vendor/heic2any.min.js');
        }
        if (typeof heic2any !== 'undefined') {
            const blob = await heic2any({ blob: file, toType: 'image/jpeg', quality: this.JPEG_QUALITY });
            return Array.isArray(blob) ? blob[0] : blob;
        }
        throw new Error('Konversi HEIC tidak tersedia');
    },

    loadScript(src) {
        return new Promise((resolve, reject) => {
            const s = document.createElement('script');
            s.src = src;
            s.onload = resolve;
            s.onerror = reject;
            document.head.appendChild(s);
        });
    },

    resizeAndEncode(blob, originalName) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            const url = URL.createObjectURL(blob);
            img.onload = async () => {
                URL.revokeObjectURL(url);
                let w = img.width;
                let h = img.height;

                if (w > this.MAX_DIMENSION || h > this.MAX_DIMENSION) {
                    if (w > h) {
                        h = Math.round((h * this.MAX_DIMENSION) / w);
                        w = this.MAX_DIMENSION;
                    } else {
                        w = Math.round((w * this.MAX_DIMENSION) / h);
                        h = this.MAX_DIMENSION;
                    }
                }

                const canvas = document.createElement('canvas');
                canvas.width = w;
                canvas.height = h;
                const ctx = canvas.getContext('2d');
                
                ctx.fillStyle = '#FFFFFF';
                ctx.fillRect(0, 0, w, h);
                ctx.drawImage(img, 0, 0, w, h);
                
                canvas.toBlob(async (newBlob) => {
                    const sha256 = await this.calculateHash(newBlob);
                    resolve({
                        blob: newBlob,
                        width: w,
                        height: h,
                        sha256: sha256,
                        originalName: originalName
                    });
                }, 'image/jpeg', this.JPEG_QUALITY);
            };
            img.onerror = () => reject(new Error('Gagal membaca gambar'));
            img.src = url;
        });
    },

    async calculateHash(blob) {
        const arrayBuffer = await blob.arrayBuffer();
        const hashBuffer = await crypto.subtle.digest('SHA-256', arrayBuffer);
        const hashArray = Array.from(new Uint8Array(hashBuffer));
        return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    },

    generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }
};
