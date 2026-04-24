<?php

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

$status = trim((string) ($_GET['status'] ?? ''));
$search = trim((string) ($_GET['search'] ?? ''));
$equipments = allEquipments($status ?: null, $search ?: null);
$stats = dashboardStats();
$insightItems = insights();

render('Dashboard', function () use ($equipments, $stats, $insightItems, $status, $search): void {
    ?>
    <section class="hero">
        <div>
            <p class="eyebrow">Inventario fisico</p>
            <h2>Visao geral dos ativos da empresa</h2>
            <p class="muted">Cadastre equipamentos, acompanhe posse e atualize rapidamente via QR Code.</p>
        </div>
        <a class="button primary" href="<?= h(appPath('equipamento.php')) ?>">Cadastrar equipamento</a>
    </section>

    <section class="stats-grid">
        <article class="card stat-card">
            <span>Total</span>
            <strong><?= h((string) $stats['total']) ?></strong>
        </article>
        <article class="card stat-card">
            <span>Ativos</span>
            <strong><?= h((string) $stats['ativos']) ?></strong>
        </article>
        <article class="card stat-card">
            <span>Em manutencao</span>
            <strong><?= h((string) $stats['manutencao']) ?></strong>
        </article>
        <article class="card stat-card">
            <span>Sem responsavel</span>
            <strong><?= h((string) $stats['sem_responsavel']) ?></strong>
        </article>
    </section>

    <section class="layout-grid">
        <article class="card">
            <div class="section-title">
                <h3>Filtros</h3>
            </div>
            <form class="filters" method="get">
                <label>
                    Busca
                    <input type="text" name="search" value="<?= h($search) ?>" placeholder="Codigo, nome, responsavel ou local">
                </label>
                <label>
                    Status
                    <select name="status">
                        <option value="">Todos</option>
                        <?php foreach (statuses() as $item): ?>
                            <option value="<?= h($item) ?>" <?= $status === $item ? 'selected' : '' ?>><?= h(ucfirst($item)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="button primary" type="submit">Aplicar</button>
            </form>
        </article>

        <article class="card">
            <div class="section-title">
                <h3>Sugestoes operacionais</h3>
                <span class="badge">Assistencia inteligente</span>
            </div>
            <?php if (!$insightItems): ?>
                <p class="muted">Nenhum alerta no momento.</p>
            <?php else: ?>
                <div class="stack">
                    <?php foreach ($insightItems as $item): ?>
                        <div class="insight insight-<?= h($item['level']) ?>">
                            <strong><?= h($item['title']) ?></strong>
                            <p><?= h($item['message']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>
    </section>

    <section class="card">
        <div class="section-title">
            <h3>Equipamentos</h3>
            <span class="badge"><?= h((string) count($equipments)) ?> itens</span>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Codigo</th>
                    <th>Tipo</th>
                    <th>Equipamento</th>
                    <th>Responsavel</th>
                    <th>Local</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($equipments as $equipment): ?>
                    <tr>
                        <td><?= h($equipment['code']) ?></td>
                        <td><?= h(ucfirst($equipment['type'])) ?></td>
                        <td>
                            <strong><?= h($equipment['name']) ?></strong>
                            <small><?= h($equipment['serial_number'] ?: 'Sem numero de serie') ?></small>
                        </td>
                        <td><?= h($equipment['holder_name'] ?: 'Nao definido') ?></td>
                        <td><?= h($equipment['location_name'] ?: 'Nao informado') ?></td>
                        <td><span class="status status-<?= h($equipment['status']) ?>"><?= h($equipment['status']) ?></span></td>
                        <td><a class="button ghost" href="<?= h(appPath('ativo.php')) ?>?id=<?= h((string) $equipment['id']) ?>">Abrir</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$equipments): ?>
                    <tr>
                        <td colspan="7" class="empty">Nenhum equipamento encontrado para os filtros atuais.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php
});
