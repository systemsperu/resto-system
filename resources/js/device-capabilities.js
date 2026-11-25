export const DeviceCapabilities = {
    async scanBarcode(video, onResult) {
        if (!navigator.mediaDevices?.getUserMedia || !('BarcodeDetector' in window)) throw new Error('El escaneo por cámara no está disponible. Usa el ingreso manual.');
        const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
        video.srcObject = stream; await video.play();
        const detector = new BarcodeDetector({ formats: ['qr_code', 'ean_13', 'ean_8', 'code_128'] });
        let active = true;
        const frame = async () => { if (!active) return; const codes = await detector.detect(video); if (codes[0]) { active = false; onResult(codes[0].rawValue); stream.getTracks().forEach(track => track.stop()); return; } requestAnimationFrame(frame); };
        requestAnimationFrame(frame);
        return () => { active = false; stream.getTracks().forEach(track => track.stop()); };
    },
    async prepareImage(file, { maxSize = 1600, quality = 0.82 } = {}) {
        if (!(file instanceof Blob) || !file.type.startsWith('image/')) {
            throw new Error('Selecciona una imagen válida.');
        }

        const bitmap = await createImageBitmap(file);
        const scale = Math.min(1, maxSize / Math.max(bitmap.width, bitmap.height));
        const canvas = document.createElement('canvas');
        canvas.width = Math.max(1, Math.round(bitmap.width * scale));
        canvas.height = Math.max(1, Math.round(bitmap.height * scale));
        canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
        bitmap.close();

        const blob = await new Promise((resolve, reject) => {
            canvas.toBlob(result => result ? resolve(result) : reject(new Error('No se pudo procesar la imagen.')), 'image/jpeg', quality);
        });

        return new File([blob], `${file.name?.replace(/\.[^.]+$/, '') || 'imagen'}.jpg`, { type: 'image/jpeg' });
    },
    print() { window.print(); },
    async requestNotifications() {
        if (!('Notification' in window)) return 'unsupported';
        return Notification.requestPermission();
    },
};

window.RecreoDevice = DeviceCapabilities;
