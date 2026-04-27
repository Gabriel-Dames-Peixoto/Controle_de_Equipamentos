<?php

declare(strict_types=1);

const APP_NAME = 'Controle de Inventario de Equipamentos';
const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'controle_equipamentos';
const DB_USER = 'root';
const DB_PASS = '';
const OCS_MOCK_PATH = __DIR__ . '/../storage/data/ocs_mock.json';
const TABLE_EQUIPAMENTOS = 'equipamentos';
const TABLE_MOVIMENTACOES = 'movimentacoes';
const LEGACY_TABLE_EQUIPMENTS = 'equipments';
const LEGACY_TABLE_MOVEMENTS = 'movements';
const FK_MOVIMENTACOES_EQUIPAMENTO = 'fk_movimentacoes_equipamento';
const LEGACY_FK_MOVEMENTS_EQUIPMENT = 'fk_movements_equipment';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dataDir = dirname(OCS_MOCK_PATH);
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0777, true);
    }

    initializeDatabaseServer();

    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME),
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    initializeDatabase($pdo);

    return $pdo;
}

function initializeDatabaseServer(): void
{
    static $initialized = false;

    if ($initialized) {
        return;
    }

    $serverPdo = new PDO(
        sprintf('mysql:host=%s;port=%d;charset=utf8mb4', DB_HOST, DB_PORT),
        DB_USER,
        DB_PASS
    );
    $serverPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $serverPdo->exec(
        'CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
    );

    $initialized = true;
}

function initializeDatabase(PDO $pdo): void
{
    migrateLegacyTableNames($pdo);

    $pdo->exec(
        <<<SQL
        CREATE TABLE IF NOT EXISTS %s (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(60) NOT NULL UNIQUE,
            type VARCHAR(40) NOT NULL,
            name VARCHAR(255) NOT NULL,
            serial_number VARCHAR(120) DEFAULT NULL,
            status VARCHAR(40) NOT NULL,
            holder_name VARCHAR(150) DEFAULT '',
            location_name VARCHAR(180) DEFAULT '',
            notes TEXT DEFAULT NULL,
            ocs_reference VARCHAR(80) DEFAULT '',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS %s (
            id INT AUTO_INCREMENT PRIMARY KEY,
            equipment_id INT NOT NULL,
            previous_holder VARCHAR(150) DEFAULT '',
            new_holder VARCHAR(150) DEFAULT '',
            previous_location VARCHAR(180) DEFAULT '',
            new_location VARCHAR(180) DEFAULT '',
            previous_status VARCHAR(40) DEFAULT '',
            new_status VARCHAR(40) DEFAULT '',
            change_reason TEXT DEFAULT NULL,
            changed_at DATETIME NOT NULL,
            CONSTRAINT %s
                FOREIGN KEY (equipment_id) REFERENCES %s(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL
        ,
        TABLE_EQUIPAMENTOS,
        TABLE_MOVIMENTACOES,
        FK_MOVIMENTACOES_EQUIPAMENTO,
        TABLE_EQUIPAMENTOS
    );

    seedDatabase($pdo);
    seedOcsMock();
}

function migrateLegacyTableNames(PDO $pdo): void
{
    $hasEquipamentos = tableExists($pdo, TABLE_EQUIPAMENTOS);
    $hasEquipments = tableExists($pdo, LEGACY_TABLE_EQUIPMENTS);

    if (!$hasEquipamentos && $hasEquipments) {
        $pdo->exec(sprintf('RENAME TABLE %s TO %s', LEGACY_TABLE_EQUIPMENTS, TABLE_EQUIPAMENTOS));
        $hasEquipamentos = true;
    }

    $hasMovimentacoes = tableExists($pdo, TABLE_MOVIMENTACOES);
    $hasMovements = tableExists($pdo, LEGACY_TABLE_MOVEMENTS);

    if (!$hasMovimentacoes && $hasMovements) {
        if (foreignKeyExists($pdo, LEGACY_TABLE_MOVEMENTS, LEGACY_FK_MOVEMENTS_EQUIPMENT)) {
            $pdo->exec(sprintf(
                'ALTER TABLE %s DROP FOREIGN KEY %s',
                LEGACY_TABLE_MOVEMENTS,
                LEGACY_FK_MOVEMENTS_EQUIPMENT
            ));
        }

        $pdo->exec(sprintf('RENAME TABLE %s TO %s', LEGACY_TABLE_MOVEMENTS, TABLE_MOVIMENTACOES));
        $hasMovimentacoes = true;
    }

    if ($hasMovimentacoes && !foreignKeyExists($pdo, TABLE_MOVIMENTACOES, FK_MOVIMENTACOES_EQUIPAMENTO)) {
        if (foreignKeyExists($pdo, TABLE_MOVIMENTACOES, LEGACY_FK_MOVEMENTS_EQUIPMENT)) {
            $pdo->exec(sprintf(
                'ALTER TABLE %s DROP FOREIGN KEY %s',
                TABLE_MOVIMENTACOES,
                LEGACY_FK_MOVEMENTS_EQUIPMENT
            ));
        }

        $pdo->exec(sprintf(
            'ALTER TABLE %s
             ADD CONSTRAINT %s
             FOREIGN KEY (equipment_id) REFERENCES %s(id) ON DELETE CASCADE',
            TABLE_MOVIMENTACOES,
            FK_MOVIMENTACOES_EQUIPAMENTO,
            TABLE_EQUIPAMENTOS
        ));
    }
}

function tableExists(PDO $pdo, string $tableName): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.tables
         WHERE table_schema = :schema AND table_name = :table'
    );
    $stmt->execute([
        'schema' => DB_NAME,
        'table' => $tableName,
    ]);

    return (int) $stmt->fetchColumn() > 0;
}

function foreignKeyExists(PDO $pdo, string $tableName, string $constraintName): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.table_constraints
         WHERE constraint_schema = :schema
           AND table_name = :table
           AND constraint_name = :constraint
           AND constraint_type = \'FOREIGN KEY\''
    );
    $stmt->execute([
        'schema' => DB_NAME,
        'table' => $tableName,
        'constraint' => $constraintName,
    ]);

    return (int) $stmt->fetchColumn() > 0;
}

function seedDatabase(PDO $pdo): void
{
    $count = (int) $pdo->query(sprintf('SELECT COUNT(*) FROM %s', TABLE_EQUIPAMENTOS))->fetchColumn();
    if ($count > 0) {
        return;
    }

    $now = date('Y-m-d H:i:s');
    $seedEquipments = [
        [
            'code' => 'EQP-001',
            'type' => 'computador',
            'name' => 'Notebook Dell Latitude 5420',
            'serial_number' => 'DL5420-ABC-001',
            'status' => 'ativo',
            'holder_name' => 'Ana Souza',
            'location_name' => 'Financeiro - Sala 2',
            'notes' => 'Equipamento principal da analista financeira.',
            'ocs_reference' => 'OCS-9001',
        ],
        [
            'code' => 'EQP-002',
            'type' => 'celular',
            'name' => 'Samsung Galaxy A54',
            'serial_number' => 'SAM-A54-7788',
            'status' => 'ativo',
            'holder_name' => 'Carlos Lima',
            'location_name' => 'Operacoes - Unidade Centro',
            'notes' => 'Aparelho corporativo com chip de plantao.',
            'ocs_reference' => 'OCS-9002',
        ],
        [
            'code' => 'EQP-003',
            'type' => 'tablet',
            'name' => 'iPad 9a geracao',
            'serial_number' => 'IPAD-0900-7781',
            'status' => 'manutencao',
            'holder_name' => '',
            'location_name' => 'TI - Bancada',
            'notes' => 'Tela com trinca. Aguardando orcamento.',
            'ocs_reference' => '',
        ],
    ];

    $insert = $pdo->prepare(
        sprintf(
            'INSERT INTO %s (code, type, name, serial_number, status, holder_name, location_name, notes, ocs_reference, created_at, updated_at)
             VALUES (:code, :type, :name, :serial_number, :status, :holder_name, :location_name, :notes, :ocs_reference, :created_at, :updated_at)',
            TABLE_EQUIPAMENTOS
        )
    );

    $movementInsert = $pdo->prepare(
        sprintf(
            'INSERT INTO %s (equipment_id, previous_holder, new_holder, previous_location, new_location, previous_status, new_status, change_reason, changed_at)
             VALUES (:equipment_id, :previous_holder, :new_holder, :previous_location, :new_location, :previous_status, :new_status, :change_reason, :changed_at)',
            TABLE_MOVIMENTACOES
        )
    );

    foreach ($seedEquipments as $equipment) {
        $insert->execute($equipment + ['created_at' => $now, 'updated_at' => $now]);
        $equipmentId = (int) $pdo->lastInsertId();
        $movementInsert->execute([
            'equipment_id' => $equipmentId,
            'previous_holder' => '',
            'new_holder' => $equipment['holder_name'],
            'previous_location' => '',
            'new_location' => $equipment['location_name'],
            'previous_status' => '',
            'new_status' => $equipment['status'],
            'change_reason' => 'Cadastro inicial do ativo.',
            'changed_at' => $now,
        ]);
    }
}

function seedOcsMock(): void
{
    if (is_file(OCS_MOCK_PATH)) {
        return;
    }

    $data = [
        [
            'ocs_id' => 'OCS-9001',
            'asset_tag' => 'EQP-001',
            'device_name' => 'Notebook Dell Latitude 5420',
            'serial_number' => 'DL5420-ABC-001',
            'user_name' => 'Ana Souza',
            'location' => 'Financeiro - Sala 2',
            'last_sync' => '2026-04-20 09:10:00',
        ],
        [
            'ocs_id' => 'OCS-9002',
            'asset_tag' => 'EQP-002',
            'device_name' => 'Samsung Galaxy A54',
            'serial_number' => 'SAM-A54-7788',
            'user_name' => 'Carlos Lima',
            'location' => 'Operacoes - Unidade Centro',
            'last_sync' => '2026-04-22 16:45:00',
        ],
        [
            'ocs_id' => 'OCS-9003',
            'asset_tag' => 'EQP-099',
            'device_name' => 'Notebook Lenovo V14',
            'serial_number' => 'LEN-V14-3344',
            'user_name' => 'Nao mapeado',
            'location' => 'Comercial - Sala 1',
            'last_sync' => '2026-04-18 11:25:00',
        ],
    ];

    file_put_contents(
        OCS_MOCK_PATH,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

function allEquipments(?string $statusFilter = null, ?string $search = null): array
{
    $sql = sprintf('SELECT * FROM %s WHERE 1 = 1', TABLE_EQUIPAMENTOS);
    $params = [];

    if ($statusFilter) {
        $sql .= ' AND status = :status';
        $params['status'] = $statusFilter;
    }

    if ($search) {
        $sql .= ' AND (code LIKE :search OR name LIKE :search OR holder_name LIKE :search OR location_name LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }

    $sql .= ' ORDER BY updated_at DESC, id DESC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function equipmentById(int $id): ?array
{
    $stmt = db()->prepare(sprintf('SELECT * FROM %s WHERE id = :id', TABLE_EQUIPAMENTOS));
    $stmt->execute(['id' => $id]);
    $equipment = $stmt->fetch();

    return $equipment ?: null;
}

function equipmentByCode(string $code): ?array
{
    $stmt = db()->prepare(sprintf('SELECT * FROM %s WHERE code = :code', TABLE_EQUIPAMENTOS));
    $stmt->execute(['code' => trim($code)]);
    $equipment = $stmt->fetch();

    return $equipment ?: null;
}

function movementsForEquipment(int $equipmentId): array
{
    $stmt = db()->prepare(
        sprintf('SELECT * FROM %s WHERE equipment_id = :equipment_id ORDER BY changed_at DESC, id DESC', TABLE_MOVIMENTACOES)
    );
    $stmt->execute(['equipment_id' => $equipmentId]);

    return $stmt->fetchAll();
}

function dashboardStats(): array
{
    $stats = [
        'total' => (int) db()->query(sprintf('SELECT COUNT(*) FROM %s', TABLE_EQUIPAMENTOS))->fetchColumn(),
        'ativos' => (int) db()->query(sprintf("SELECT COUNT(*) FROM %s WHERE status = 'ativo'", TABLE_EQUIPAMENTOS))->fetchColumn(),
        'manutencao' => (int) db()->query(sprintf("SELECT COUNT(*) FROM %s WHERE status = 'manutencao'", TABLE_EQUIPAMENTOS))->fetchColumn(),
        'sem_responsavel' => (int) db()->query(sprintf("SELECT COUNT(*) FROM %s WHERE TRIM(holder_name) = ''", TABLE_EQUIPAMENTOS))->fetchColumn(),
    ];

    return $stats;
}

function insights(): array
{
    $items = [];

    $withoutHolder = db()->query(
        sprintf("SELECT code, name FROM %s WHERE TRIM(holder_name) = '' ORDER BY updated_at DESC", TABLE_EQUIPAMENTOS)
    )->fetchAll();
    foreach ($withoutHolder as $equipment) {
        $items[] = [
            'level' => 'warning',
            'title' => 'Equipamento sem responsavel definido',
            'message' => sprintf('%s (%s) esta sem posse registrada.', $equipment['name'], $equipment['code']),
        ];
    }

    $maintenance = db()->query(
        sprintf("SELECT code, name, location_name FROM %s WHERE status = 'manutencao' ORDER BY updated_at ASC", TABLE_EQUIPAMENTOS)
    )->fetchAll();
    foreach ($maintenance as $equipment) {
        $items[] = [
            'level' => 'info',
            'title' => 'Acompanhar manutencao',
            'message' => sprintf('%s (%s) segue em manutencao em %s.', $equipment['name'], $equipment['code'], $equipment['location_name'] ?: 'local nao informado'),
        ];
    }

    return $items;
}

function saveEquipment(array $payload, ?int $id = null): int
{
    $now = date('Y-m-d H:i:s');
    $current = $id ? equipmentById($id) : null;

    $data = [
        'code' => strtoupper(trim((string) ($payload['code'] ?? ''))),
        'type' => normalizeEquipmentType((string) ($payload['type'] ?? 'computador')),
        'name' => trim((string) ($payload['name'] ?? '')),
        'serial_number' => trim((string) ($payload['serial_number'] ?? '')),
        'status' => normalizeStatus((string) ($payload['status'] ?? 'ativo')),
        'holder_name' => trim((string) ($payload['holder_name'] ?? '')),
        'location_name' => trim((string) ($payload['location_name'] ?? '')),
        'notes' => trim((string) ($payload['notes'] ?? '')),
        'ocs_reference' => trim((string) ($payload['ocs_reference'] ?? '')),
    ];

    if ($data['code'] === '' || $data['name'] === '') {
        throw new InvalidArgumentException('Codigo e nome sao obrigatorios.');
    }

    validateEquipmentData($data);

    $pdo = db();

    if ($current) {
        $stmt = $pdo->prepare(
            sprintf(
                'UPDATE %s
             SET code = :code, type = :type, name = :name, serial_number = :serial_number, status = :status,
                 holder_name = :holder_name, location_name = :location_name, notes = :notes, ocs_reference = :ocs_reference, updated_at = :updated_at
             WHERE id = :id',
                TABLE_EQUIPAMENTOS
            )
        );
        $stmt->execute($data + ['updated_at' => $now, 'id' => $id]);
        $equipmentId = $id;
    } else {
        $stmt = $pdo->prepare(
            sprintf(
                'INSERT INTO %s (code, type, name, serial_number, status, holder_name, location_name, notes, ocs_reference, created_at, updated_at)
                 VALUES (:code, :type, :name, :serial_number, :status, :holder_name, :location_name, :notes, :ocs_reference, :created_at, :updated_at)',
                TABLE_EQUIPAMENTOS
            )
        );
        $stmt->execute($data + ['created_at' => $now, 'updated_at' => $now]);
        $equipmentId = (int) $pdo->lastInsertId();
    }

    $shouldCreateMovement = !$current
        || $current['holder_name'] !== $data['holder_name']
        || $current['location_name'] !== $data['location_name']
        || $current['status'] !== $data['status'];

    if ($shouldCreateMovement) {
        recordMovement(
            $equipmentId,
            [
                'previous_holder' => $current['holder_name'] ?? '',
                'new_holder' => $data['holder_name'],
                'previous_location' => $current['location_name'] ?? '',
                'new_location' => $data['location_name'],
                'previous_status' => $current['status'] ?? '',
                'new_status' => $data['status'],
                'change_reason' => $current ? 'Atualizacao pelo cadastro do equipamento.' : 'Cadastro inicial do ativo.',
            ]
        );
    }

    return $equipmentId;
}

function recordMovement(int $equipmentId, array $payload): void
{
    $stmt = db()->prepare(
        sprintf(
            'INSERT INTO %s (equipment_id, previous_holder, new_holder, previous_location, new_location, previous_status, new_status, change_reason, changed_at)
             VALUES (:equipment_id, :previous_holder, :new_holder, :previous_location, :new_location, :previous_status, :new_status, :change_reason, :changed_at)',
            TABLE_MOVIMENTACOES
        )
    );

    $stmt->execute([
        'equipment_id' => $equipmentId,
        'previous_holder' => trim((string) ($payload['previous_holder'] ?? '')),
        'new_holder' => trim((string) ($payload['new_holder'] ?? '')),
        'previous_location' => trim((string) ($payload['previous_location'] ?? '')),
        'new_location' => trim((string) ($payload['new_location'] ?? '')),
        'previous_status' => trim((string) ($payload['previous_status'] ?? '')),
        'new_status' => trim((string) ($payload['new_status'] ?? '')),
        'change_reason' => trim((string) ($payload['change_reason'] ?? '')),
        'changed_at' => date('Y-m-d H:i:s'),
    ]);
}

function quickMoveEquipment(int $equipmentId, array $payload): bool
{
    $equipment = equipmentById($equipmentId);
    if (!$equipment) {
        throw new RuntimeException('Equipamento nao encontrado.');
    }

    $newHolder = trim((string) ($payload['holder_name'] ?? ''));
    $newLocation = trim((string) ($payload['location_name'] ?? ''));
    $newStatus = normalizeStatus((string) ($payload['status'] ?? $equipment['status']));
    $reason = trim((string) ($payload['change_reason'] ?? 'Atualizacao rapida via QR Code.'));

    validateEquipmentData([
        'code' => $equipment['code'],
        'type' => $equipment['type'],
        'name' => $equipment['name'],
        'serial_number' => $equipment['serial_number'],
        'status' => $newStatus,
        'holder_name' => $newHolder,
        'location_name' => $newLocation,
        'notes' => $equipment['notes'],
        'ocs_reference' => $equipment['ocs_reference'],
    ]);

    $hasChanges = $equipment['holder_name'] !== $newHolder
        || $equipment['location_name'] !== $newLocation
        || $equipment['status'] !== $newStatus;

    if (!$hasChanges) {
        return false;
    }

    $stmt = db()->prepare(
        sprintf(
            'UPDATE %s SET holder_name = :holder_name, location_name = :location_name, status = :status, updated_at = :updated_at WHERE id = :id',
            TABLE_EQUIPAMENTOS
        )
    );

    $stmt->execute([
        'holder_name' => $newHolder,
        'location_name' => $newLocation,
        'status' => $newStatus,
        'updated_at' => date('Y-m-d H:i:s'),
        'id' => $equipmentId,
    ]);

    recordMovement($equipmentId, [
        'previous_holder' => $equipment['holder_name'],
        'new_holder' => $newHolder,
        'previous_location' => $equipment['location_name'],
        'new_location' => $newLocation,
        'previous_status' => $equipment['status'],
        'new_status' => $newStatus,
        'change_reason' => $reason,
    ]);

    return true;
}

function statuses(): array
{
    return ['ativo', 'manutencao', 'reserva', 'baixado'];
}

function equipmentTypes(): array
{
    return ['computador', 'celular', 'tablet'];
}

function statusLabel(string $status): string
{
    $labels = [
        'ativo' => 'Ativo',
        'manutencao' => 'Em manutencao',
        'reserva' => 'Reserva',
        'baixado' => 'Baixado',
        'ok' => 'OK',
        'divergente' => 'Divergente',
        'nao_encontrado' => 'Nao encontrado',
    ];

    return $labels[$status] ?? ucfirst($status);
}

function equipmentTypeLabel(string $type): string
{
    $labels = [
        'computador' => 'Computador',
        'celular' => 'Celular',
        'tablet' => 'Tablet',
    ];

    return $labels[$type] ?? ucfirst($type);
}

function normalizeStatus(string $status): string
{
    $normalized = strtolower(trim($status));
    $aliases = [
        'em manutencao' => 'manutencao',
    ];

    return $aliases[$normalized] ?? $normalized;
}

function normalizeEquipmentType(string $type): string
{
    return strtolower(trim($type));
}

function validateEquipmentData(array $data): void
{
    if (!in_array($data['type'], equipmentTypes(), true)) {
        throw new InvalidArgumentException('Tipo de equipamento invalido.');
    }

    if (!in_array($data['status'], statuses(), true)) {
        throw new InvalidArgumentException('Status do equipamento invalido.');
    }
}

function ocsEntries(): array
{
    $content = file_get_contents(OCS_MOCK_PATH);
    $data = json_decode($content ?: '[]', true);

    return is_array($data) ? $data : [];
}

function compareWithOcs(): array
{
    $equipments = allEquipments();
    $byCode = [];
    $bySerial = [];

    foreach ($equipments as $equipment) {
        $byCode[$equipment['code']] = $equipment;
        if ($equipment['serial_number']) {
            $bySerial[$equipment['serial_number']] = $equipment;
        }
    }

    $comparisons = [];

    foreach (ocsEntries() as $entry) {
        $local = $byCode[$entry['asset_tag']] ?? $bySerial[$entry['serial_number']] ?? null;
        $status = 'nao_encontrado';

        if ($local) {
            $holderMatches = trim((string) $local['holder_name']) === trim((string) $entry['user_name']);
            $locationMatches = trim((string) $local['location_name']) === trim((string) $entry['location']);
            $status = ($holderMatches && $locationMatches) ? 'ok' : 'divergente';
        }

        $comparisons[] = [
            'ocs' => $entry,
            'local' => $local,
            'comparison_status' => $status,
        ];
    }

    return $comparisons;
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function baseUrl(): string
{
    $https = ($_SERVER['HTTPS'] ?? '') === 'on';
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = basePath();

    return $scheme . '://' . $host . ($path === '/' ? '' : $path);
}

function basePath(): string
{
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $path = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

    return $path === '' ? '/' : $path;
}

function appPath(string $path = ''): string
{
    $base = basePath();
    $suffix = ltrim($path, '/');

    if ($suffix === '') {
        return $base;
    }

    return ($base === '/' ? '' : $base) . '/' . $suffix;
}

function equipmentQrUrl(array $equipment): string
{
    return baseUrl() . '/qr.php?code=' . urlencode($equipment['code']);
}

function qrImageUrl(array $equipment): string
{
    return 'https://quickchart.io/qr?size=220&text=' . rawurlencode(equipmentQrUrl($equipment));
}

function flash(string $message, string $type = 'success'): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function getFlash(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function render(string $title, callable $content): void
{
    $flash = getFlash();
?>
    <!DOCTYPE html>
    <html lang="pt-BR">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= h($title) ?> | <?= APP_NAME ?></title>
        <link rel="stylesheet" href="<?= h(appPath('public/assets/app.css')) ?>">
    </head>

    <body>
        <button
            class="menu-toggle"
            type="button"
            data-menu-toggle
            aria-expanded="false"
            aria-controls="sidebar-nav"
            aria-label="Abrir menu"
        >
            Menu
        </button>
        <div class="sidebar-backdrop" data-menu-close hidden></div>
        <div class="shell">
            <aside class="sidebar" id="sidebar-nav">
                <div>
                    <p class="eyebrow">MVP</p>
                    <h1><?= APP_NAME ?></h1>
                    <p class="muted"></p>
                </div>
                <nav class="nav">
                    <a href="<?= h(appPath('index.php')) ?>">Dashboard</a>
                    <a href="<?= h(appPath('equipamento.php')) ?>">Novo equipamento</a>
                    <a href="<?= h(appPath('scanner.php')) ?>">Leitor QR</a>
                    <a href="<?= h(appPath('ocs.php')) ?>">Integracao OCS</a>
                </nav>
            </aside>
            <main class="content">
                <?php if ($flash): ?>
                    <div class="flash flash-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
                <?php endif; ?>
                <?php $content(); ?>
            </main>
        </div>
        <script src="<?= h(appPath('public/assets/app.js')) ?>"></script>
    </body>

    </html>
<?php
}
