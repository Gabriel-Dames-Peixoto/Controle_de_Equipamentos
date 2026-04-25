<?php

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

$comparisons = compareWithOcs();

render('Integracao OCS', function () use ($comparisons): void {
?>
    <section class="hero compact">
        <div>
            <p class="eyebrow">Integracao inicial</p>
            <h2>Cruzamento com OCS Inventory</h2>
            <p class="muted">MVP usando export/mock local para validar a ideia de sincronizacao sem depender de integracao completa neste primeiro momento.</p>
        </div>
    </section>

    <section class="card">
        <div class="section-title">
            <h3>Comparativo local x OCS</h3>
            <span class="badge">Mock / export JSON</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>OCS ID</th>
                        <th>Asset tag</th>
                        <th>Equipamento OCS</th>
                        <th>Inventario local</th>
                        <th>Responsavel / local</th>
                        <th>Resultado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comparisons as $row): ?>
                        <tr>
                            <td><?= h($row['ocs']['ocs_id']) ?></td>
                            <td><?= h($row['ocs']['asset_tag']) ?></td>
                            <td>
                                <strong><?= h($row['ocs']['device_name']) ?></strong>
                                <small><?= h($row['ocs']['serial_number']) ?></small>
                            </td>
                            <td>
                                <?php if ($row['local']): ?>
                                    <strong><?= h($row['local']['name']) ?></strong>
                                    <small><?= h($row['local']['code']) ?></small>
                                <?php else: ?>
                                    <span class="muted">Nao encontrado localmente</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                OCS: <?= h($row['ocs']['user_name']) ?> / <?= h($row['ocs']['location']) ?><br>
                                Local: <?= h($row['local']['holder_name'] ?? '---') ?> / <?= h($row['local']['location_name'] ?? '---') ?>
                            </td>
                            <td><span class="status status-<?= h($row['comparison_status']) ?>"><?= h(statusLabel($row['comparison_status'])) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="muted">Arquivo usado no MVP: <code>storage/data/ocs_mock.json</code>. Em uma proxima etapa, este ponto pode receber importacao real por API ou export do OCS.</p>
    </section>
<?php
});
