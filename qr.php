<?php

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

$code = trim((string) ($_GET['code'] ?? ''));
$equipment = $code !== '' ? equipmentByCode($code) : null;

render('Modo QR', function () use ($equipment, $code): void {
?>
    <section class="hero compact mobile-hero">
        <div>
            <p class="eyebrow">QR Mode</p>
            <h2><?= $equipment ? h($equipment['name']) : 'Consulta rapida por QR Code' ?></h2>
            <p class="muted">Tela pensada para leitura no celular e atualizacao imediata do inventario.</p>
        </div>
        <a class="button ghost" href="<?= h(appPath('scanner.php')) ?>">Abrir leitor via camera</a>
    </section>

    <?php if (!$equipment): ?>
        <section class="card">
            <h3>Equipamento nao encontrado</h3>
            <p class="muted">O codigo informado foi <strong><?= h($code ?: 'vazio') ?></strong>. Confira o QR ou use o leitor integrado.</p>
        </section>
    <?php else: ?>
        <section class="layout-grid qr-layout">
            <article class="card">
                <div class="section-title">
                    <h3>Resumo do ativo</h3>
<<<<<<< HEAD
                    <span class="status status-<?= h($equipment['status']) ?>"><?= h(statusLabel($equipment['status'])) ?></span>
=======
                    <span class="status status-<?= h($equipment['status']) ?>"><?= h($equipment['status']) ?></span>
>>>>>>> 64008ce534cb8635856c9f616a1c1fa1d0899102
                </div>
                <dl class="details-list">
                    <div>
                        <dt>Codigo</dt>
                        <dd><?= h($equipment['code']) ?></dd>
                    </div>
                    <div>
                        <dt>Tipo</dt>
<<<<<<< HEAD
                        <dd><?= h(equipmentTypeLabel($equipment['type'])) ?></dd>
=======
                        <dd><?= h(ucfirst($equipment['type'])) ?></dd>
>>>>>>> 64008ce534cb8635856c9f616a1c1fa1d0899102
                    </div>
                    <div>
                        <dt>Serie</dt>
                        <dd><?= h($equipment['serial_number'] ?: 'Nao informada') ?></dd>
                    </div>
                    <div>
                        <dt>Responsavel</dt>
                        <dd><?= h($equipment['holder_name'] ?: 'Nao definido') ?></dd>
                    </div>
                    <div>
                        <dt>Local</dt>
                        <dd><?= h($equipment['location_name'] ?: 'Nao informado') ?></dd>
                    </div>
                </dl>
            </article>

            <article class="card">
                <div class="section-title">
                    <h3>Atualizacao rapida</h3>
                </div>
                <form class="form-grid" method="post" action="<?= h(appPath('movimentar.php')) ?>">
                    <input type="hidden" name="equipment_id" value="<?= h((string) $equipment['id']) ?>">
                    <input type="hidden" name="equipment_code" value="<?= h($equipment['code']) ?>">
                    <input type="hidden" name="from_qr" value="1">
                    <label>
                        Quem esta com o equipamento
                        <input type="text" name="holder_name" value="<?= h($equipment['holder_name']) ?>">
                    </label>
                    <label>
                        Onde ele esta agora
                        <input type="text" name="location_name" value="<?= h($equipment['location_name']) ?>">
                    </label>
                    <label>
                        Status
                        <select name="status">
                            <?php foreach (statuses() as $status): ?>
<<<<<<< HEAD
                                <option value="<?= h($status) ?>" <?= $equipment['status'] === $status ? 'selected' : '' ?>><?= h(statusLabel($status)) ?></option>
=======
                                <option value="<?= h($status) ?>" <?= $equipment['status'] === $status ? 'selected' : '' ?>><?= h(ucfirst($status)) ?></option>
>>>>>>> 64008ce534cb8635856c9f616a1c1fa1d0899102
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="full">
                        Observacao da mudanca
                        <input type="text" name="change_reason" value="" placeholder="Ex: entregue para novo colaborador">
                    </label>
                    <div class="actions full">
                        <button class="button primary" type="submit">Salvar atualizacao</button>
                    </div>
                </form>
            </article>
        </section>
    <?php endif; ?>
<?php
});
