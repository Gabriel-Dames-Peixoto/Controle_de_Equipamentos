<?php

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

render('Leitor QR', function (): void {
    ?>
    <section class="hero compact mobile-hero">
        <div>
            <p class="eyebrow">Leitor QR</p>
            <h2>Captura por camera do celular</h2>
            <p class="muted">Use o navegador do smartphone para apontar para o QR e abrir o equipamento automaticamente.</p>
        </div>
    </section>

    <section class="layout-grid qr-layout">
        <article class="card">
            <div class="section-title">
                <h3>Camera</h3>
            </div>
            <video id="qr-video" playsinline muted></video>
            <p class="muted" id="scanner-status">Permita o acesso a camera para iniciar a leitura.</p>
            <button class="button primary" type="button" data-start-scanner>Iniciar leitor</button>
        </article>

        <article class="card">
            <div class="section-title">
                <h3>Fallback manual</h3>
            </div>
            <form class="form-grid" method="get" action="<?= h(appPath('qr.php')) ?>">
                <label class="full">
                    Codigo do equipamento
                    <input type="text" name="code" placeholder="Ex: EQP-001">
                </label>
                <div class="actions full">
                    <button class="button ghost" type="submit">Abrir equipamento</button>
                </div>
            </form>
            <p class="muted">Se o navegador nao suportar leitura nativa de QR, digite o codigo impresso abaixo do QR.</p>
        </article>
    </section>
    <?php
});
