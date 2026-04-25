<?php

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

$equipmentId = isset($_POST['equipment_id']) ? (int) $_POST['equipment_id'] : 0;

try {
    $changed = quickMoveEquipment($equipmentId, $_POST);
    flash($changed ? 'Movimentacao registrada com sucesso.' : 'Nenhuma alteracao foi detectada.');
} catch (Throwable $exception) {
    flash('Nao foi possivel registrar a movimentacao: ' . $exception->getMessage(), 'error');
}

$fromQr = isset($_POST['from_qr']) && $_POST['from_qr'] === '1';
if ($fromQr) {
    redirect(appPath('qr.php') . '?code=' . urlencode((string) ($_POST['equipment_code'] ?? '')));
}

redirect(appPath('ativo.php') . '?id=' . $equipmentId);
