<?php

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$equipment = equipmentById($id);

if (!$equipment) {
    flash('Equipamento nao encontrado.', 'error');
    redirect(appPath('index.php'));
}

$movements = movementsForEquipment($id);
$qrUrl = equipmentQrUrl($equipment);

render('Detalhe do equipamento', function () use ($equipment, $movements, $qrUrl): void {
?>
    <section class="hero compact">
        <div>
            <p class="eyebrow"><?= h(strtoupper($equipment['code'])) ?></p>
            <h2><?= h($equipment['name']) ?></h2>
            <p class="muted">Consulta rapida, QR Code e atualizacao de posse/local em um unico lugar.</p>
        </div>
        <div class="actions">
            <a class="button ghost" href="<?= h(appPath('equipamento.php')) ?>?id=<?= h((string) $equipment['id']) ?>">Editar cadastro</a>
            <a class="button primary" href="<?= h(appPath('qr.php')) ?>?code=<?= h(urlencode($equipment['code'])) ?>">Abrir modo QR</a>
        </div>
    </section>

    <section class="layout-grid details">
        <article class="card">
            <div class="section-title">
                <h3>Dados atuais</h3>
                <span class="status status-<?= h($equipment['status']) ?>"><?= h(statusLabel($equipment['status'])) ?></span>
            </div>
            <dl class="details-list">
                <div>
                    <dt>Tipo</dt>
                    <dd><?= h(equipmentTypeLabel($equipment['type'])) ?></dd>
                </div>
                <div>
                    <dt>Numero de serie</dt>
                    <dd><?= h($equipment['serial_number'] ?: 'Nao informado') ?></dd>
                </div>
                <div>
                    <dt>Responsavel</dt>
                    <dd><?= h($equipment['holder_name'] ?: 'Nao definido') ?></dd>
                </div>
                <div>
                    <dt>Localizacao</dt>
                    <dd><?= h($equipment['location_name'] ?: 'Nao informada') ?></dd>
                </div>
                <div>
                    <dt>Referencia OCS</dt>
                    <dd><?= h($equipment['ocs_reference'] ?: 'Nao vinculada') ?></dd>
                </div>
                <div>
                    <dt>Atualizado em</dt>
                    <dd><?= h(date('d/m/Y H:i', strtotime($equipment['updated_at']))) ?></dd>
                </div>
            </dl>
            <?php if ($equipment['notes']): ?>
                <div class="note-box"><?= nl2br(h($equipment['notes'])) ?></div>
            <?php endif; ?>
        </article>

        <article class="card qr-card">
            <div class="section-title">
                <h3>QR Code do equipamento</h3>
                <span class="badge">Obrigatorio</span>
            </div>
            <img src="<?= h(qrImageUrl($equipment)) ?>" alt="QR Code do equipamento <?= h($equipment['code']) ?>">
            <p class="muted">Ao ler o QR, o colaborador abre a pagina de consulta e atualizacao rapida no celular.</p>
            <input type="text" readonly value="<?= h($qrUrl) ?>">
        </article>
    </section>

    <section class="layout-grid">
        <article class="card">
            <div class="section-title">
                <h3>Atualizacao rapida</h3>
            </div>
            <form class="form-grid" method="post" action="<?= h(appPath('movimentar.php')) ?>">
                <input type="hidden" name="equipment_id" value="<?= h((string) $equipment['id']) ?>">
                <label>
                    Novo responsavel
                    <input type="text" name="holder_name" value="<?= h($equipment['holder_name']) ?>" placeholder="Quem esta com o equipamento">
                </label>
                <label>
                    Nova localizacao
                    <input type="text" name="location_name" value="<?= h($equipment['location_name']) ?>" placeholder="Setor, sala ou unidade">
                </label>
                <label>
                    Status atual
                    <select name="status">
                        <?php foreach (statuses() as $status): ?>
                            <option value="<?= h($status) ?>" <?= $equipment['status'] === $status ? 'selected' : '' ?>><?= h(statusLabel($status)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="full">
                    Motivo da movimentacao
                    <input type="text" name="change_reason" value="" placeholder="Ex: troca de usuario, devolucao, envio para manutencao">
                </label>
                <div class="actions full">
                    <button class="button primary" type="submit">Registrar movimentacao</button>
                </div>
            </form>
        </article>

        <article class="card">
            <div class="section-title">
                <h3>Historico de movimentacao</h3>
            </div>
            <div class="timeline">
                <?php foreach ($movements as $movement): ?>
                    <div class="timeline-item">
                        <strong><?= h(date('d/m/Y H:i', strtotime($movement['changed_at']))) ?></strong>
                        <p>
                            <span>Responsavel:</span>
                            <?= h($movement['previous_holder'] ?: 'vazio') ?> ->
                            <?= h($movement['new_holder'] ?: 'vazio') ?>
                        </p>
                        <p>
                            <span>Local:</span>
                            <?= h($movement['previous_location'] ?: 'vazio') ?> ->
                            <?= h($movement['new_location'] ?: 'vazio') ?>
                        </p>
                        <p>
                            <span>Status:</span>
                            <?= h($movement['previous_status'] ?: 'vazio') ?> ->
                            <?= h($movement['new_status'] ?: 'vazio') ?>
                        </p>
                        <p class="muted"><?= h($movement['change_reason'] ?: 'Sem observacoes') ?></p>
                    </div>
                <?php endforeach; ?>
                <?php if (!$movements): ?>
                    <div class="timeline-item">
                        <p class="muted">Nenhuma movimentacao registrada para este equipamento ate o momento.</p>
                    </div>
                <?php endif; ?>
            </div>
        </article>
    </section>
<?php
});
