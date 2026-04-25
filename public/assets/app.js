const appBasePath = window.location.pathname.replace(/\/[^/]*$/, '');
const menuToggleButton = document.querySelector('[data-menu-toggle]');
const menuCloseTarget = document.querySelector('[data-menu-close]');
const sidebar = document.getElementById('sidebar-nav');
const scannerButton = document.querySelector('[data-start-scanner]');
const imageInput = document.querySelector('[data-qr-image]');

const setMenuState = (isOpen) => {
    document.body.classList.toggle('menu-open', isOpen);

    if (menuToggleButton) {
        menuToggleButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        menuToggleButton.textContent = isOpen ? 'Fechar' : 'Menu';
    }

    if (menuCloseTarget) {
        menuCloseTarget.hidden = !isOpen;
    }
};

if (menuToggleButton && sidebar) {
    menuToggleButton.addEventListener('click', () => {
        const isOpen = !document.body.classList.contains('menu-open');
        setMenuState(isOpen);
    });

    if (menuCloseTarget) {
        menuCloseTarget.addEventListener('click', () => setMenuState(false));
    }

    sidebar.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 980) {
                setMenuState(false);
            }
        });
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 980) {
            setMenuState(false);
        }
    });
}

const scannerStatus = document.getElementById('scanner-status');
const scannerVideo = document.getElementById('qr-video');
let scannerStream = null;
let scannerDetector = null;
let scanFrameHandle = null;

const updateScannerStatus = (message) => {
    if (scannerStatus) {
        scannerStatus.textContent = message;
    }
};

const openDetectedQr = (rawValue) => {
    if (!rawValue) {
        return;
    }

    updateScannerStatus('QR detectado. Abrindo equipamento...');

    if (rawValue.startsWith('http')) {
        window.location.href = rawValue;
        return;
    }

    window.location.href = `${appBasePath}/qr.php?code=${encodeURIComponent(rawValue)}`;
};

const detectFromSource = async (source) => {
    if (!scannerDetector) {
        throw new Error('detector-unavailable');
    }

    const codes = await scannerDetector.detect(source);
    if (codes.length > 0) {
        openDetectedQr(codes[0].rawValue || '');
        return true;
    }

    return false;
};

const stopScanner = () => {
    if (scanFrameHandle) {
        cancelAnimationFrame(scanFrameHandle);
        scanFrameHandle = null;
    }

    if (scannerStream) {
        scannerStream.getTracks().forEach((track) => track.stop());
        scannerStream = null;
    }

    if (scannerVideo) {
        scannerVideo.pause();
        scannerVideo.srcObject = null;
    }
};

const startFrameScan = async () => {
    if (!scannerVideo) {
        return;
    }

    try {
        const found = await detectFromSource(scannerVideo);
        if (found) {
            stopScanner();
            return;
        }
    } catch (error) {
        updateScannerStatus('Nao foi possivel ler o QR continuamente. Tente usar a foto como alternativa.');
        stopScanner();
        return;
    }

    scanFrameHandle = requestAnimationFrame(startFrameScan);
};

if (scannerButton) {
    scannerButton.addEventListener('click', async () => {
        if (!scannerVideo) {
            return;
        }

        if (!('BarcodeDetector' in window)) {
            updateScannerStatus('Este navegador nao suporta leitura nativa. Use a opcao de foto ou digite o codigo manualmente.');
            return;
        }

        if (!window.isSecureContext) {
            updateScannerStatus('A camera do navegador exige HTTPS ou localhost. No celular, use a opcao de foto se estiver acessando por IP local.');
            return;
        }

        scannerDetector = new BarcodeDetector({ formats: ['qr_code'] });
        updateScannerStatus('Solicitando acesso a camera...');

        try {
            stopScanner();

            scannerStream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: { ideal: 'environment' },
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                },
                audio: false,
            });

            scannerVideo.srcObject = scannerStream;
            await scannerVideo.play();
            updateScannerStatus('Camera iniciada. Aponte para o QR Code.');
            startFrameScan();
        } catch (error) {
            updateScannerStatus('Nao foi possivel acessar a camera. Verifique permissao, HTTPS e se outra pagina ja esta usando a camera.');
            stopScanner();
        }
    });
}

if (imageInput) {
    imageInput.addEventListener('change', async (event) => {
        const [file] = event.target.files || [];
        if (!file) {
            return;
        }

        if (!('BarcodeDetector' in window)) {
            updateScannerStatus('Seu navegador nao conseguiu ler QR por foto automaticamente. Use o codigo manual.');
            return;
        }

        scannerDetector = new BarcodeDetector({ formats: ['qr_code'] });
        updateScannerStatus('Analisando foto...');

        try {
            const imageBitmap = await createImageBitmap(file);
            const found = await detectFromSource(imageBitmap);

            if (!found) {
                updateScannerStatus('Nenhum QR foi encontrado na foto. Tente outra imagem mais nitida.');
            }
        } catch (error) {
            updateScannerStatus('Nao foi possivel analisar a foto selecionada.');
        } finally {
            event.target.value = '';
        }
    });
}
