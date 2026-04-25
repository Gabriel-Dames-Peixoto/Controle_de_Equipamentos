<?php

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.ph';

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$equipment = $id ? equipmentById($id) : null;

if ($id && !$equipment) {
    flash('Equipamento nao encontrado.', 'error');
    redirect(appPath('index.php'));
}

$equipment ??= [
    'id' => null,
    'code' => '',
    'type' => 'computador',
    'name' => '',
    'serial_number' => '',
    'status' => 'ativo',
    'holder_name' => '',
    'location_name' => '',
    'notes' => '',
    'ocs_reference' => '',
];

render($id ? 'Editar equipamento' : 'Novo equipamento', function () use ($equipment, $id): void {
?>
    <section class="hero compact">
        <div>
            <p class="eyebrow">Cadastro</p>
            <h2><?= $id ? 'Atualizar equipamento' : 'Cadastrar novo equipamento' ?></h2>
            <p class="muted">Preencha os dados principais do ativo e deixe posse/localizacao prontas para consulta rapida.</p>
        </div>
    </section>

    <form class="card form-grid" method="post" action="<?= h(appPath('salvar_equipamento.php')) ?>">
        <?php if ($id): ?>
            <input type="hidden" name="id" value="<?= h((string) $id) ?>">
        <?php endif; ?>

        <label>
            Codigo ou identificacao
            <input type="text" name="code" required value="<?= h($equipment['code']) ?>" placeholder="Ex: EQP-004">
        </label>

        <label>
            Tipo
            <select name="type" required>
                <?php foreach (equipmentTypes() as $type): ?>
                    <option value="<?= h($type) ?>" <?= $equipment['type'] === $type ? 'selected' : '' ?>><?= h(equipmentTypeLabel($type)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="full">
            Nome do equipamento
            <input type="text" name="name" required value="<?= h($equipment['name']) ?>" placeholder="Ex: Notebook Lenovo ThinkPad">
        </label>

        <label>
            Numero de serie
            <input type="text" name="serial_number" value="<?= h($equipment['serial_number']) ?>" placeholder="Opcional">
        </label>

        <label>
            Status
            <select name="status" required>
                <?php foreach (statuses() as $status): ?>
                    <option value="<?= h($status) ?>" <?= $equipment['status'] === $status ? 'selected' : '' ?>><?= h(statusLabel($status)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Responsavel atual
            <input type="text" name="holder_name" value="<?= h($equipment['holder_name']) ?>" placeholder="Quem esta com o equipamento">
        </label>

        <label>
            Localizacao atual
            <input type="text" name="location_name" value="<?= h($equipment['location_name']) ?>" placeholder="Setor, sala ou unidade">
        </label>

        <label>
            Referencia OCS
            <input type="text" name="ocs_reference" value="<?= h($equipment['ocs_reference']) ?>" placeholder="Ex: OCS-9004">
        </label>

        <label class="full">
            Observacoes
            <textarea name="notes" rows="4" placeholder="Informacoes complementares"><?= h($equipment['notes']) ?></textarea>
        </label>

        <div class="actions full">
            <a class="button ghost" href="<?= h(appPath('index.php')) ?>">Cancelar</a>
            <button class="button primary" type="submit"><?= $id ? 'Salvar alteracoes' : 'Cadastrar equipamento' ?></button>
        </div>
    </form>
<?php
});
