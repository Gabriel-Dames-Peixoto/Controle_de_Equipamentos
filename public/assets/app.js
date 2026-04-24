const appBasePath = window.location.pathname.replace(/\/[^/]*$/, '');
const scannerButton = document.querySelector('[data-start-scanner]');

if (scannerButton) {
    scannerButton.addEventListener('click', async () => {
        const status = document.getElementById('scanner-status');
        const video = document.getElementById('qr-video');

        if (!('BarcodeDetector' in window)) {
            status.textContent = 'Leitura nativa de QR nao suportada neste navegador. Use o codigo manual.';
            return;
        }

        const detector = new BarcodeDetector({ formats: ['qr_code'] });
        status.textContent = 'Solicitando acesso a camera...';

        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: 'environment' } },
                audio: false,
            });

            video.srcObject = stream;
            await video.play();
            status.textContent = 'Camera iniciada. Aponte para o QR Code.';

            const scan = async () => {
                const codes = await detector.detect(video);
                if (codes.length > 0) {
                    const rawValue = codes[0].rawValue || '';
                    status.textContent = 'QR detectado. Abrindo equipamento...';

                    if (rawValue.startsWith('http')) {
                        window.location.href = rawValue;
                        return;
                    }

                    window.location.href = `${appBasePath}/qr.php?code=${encodeURIComponent(rawValue)}`;
                    return;
                }

                requestAnimationFrame(scan);
            };

            requestAnimationFrame(scan);
        } catch (error) {
            status.textContent = 'Nao foi possivel acessar a camera. Verifique a permissao do navegador.';
        }
    });
}
