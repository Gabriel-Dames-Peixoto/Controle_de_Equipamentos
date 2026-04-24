<?php

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

try {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : null;
    $equipmentId = saveEquipment($_POST, $id);
    flash($id ? 'Equipamento atualizado com sucesso.' : 'Equipamento cadastrado com sucesso.');
    redirect(appPath('ativo.php') . '?id=' . $equipmentId);
} catch (Throwable $exception) {
    flash('Nao foi possivel salvar o equipamento: ' . $exception->getMessage(), 'error');
    redirect(appPath('index.php'));
}
