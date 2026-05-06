<?php

declare(strict_types=1);

final class RegisterDAO
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(RegisterModel $register): void
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO registers (duracion, descripcion, estado, notas, id_empleado) VALUES (:duracion, :descripcion, :estado, :notas, :id_empleado)'
            );

            $stmt->execute([
                'duracion' => $register->duracion,
                'descripcion' => $register->descripcion,
                'estado' => $register->estado,
                'notas' => $register->notas,
                'id_empleado' => $register->idEmpleado,
            ]);

            $registerId = (int) $this->pdo->lastInsertId();
            $this->syncRegisterClients($registerId, $register->idsClientes);
            $this->syncRegisterProviders($registerId, $register->idsProveedores);
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function update(RegisterModel $register): void
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE registers SET duracion = :duracion, descripcion = :descripcion, estado = :estado, notas = :notas WHERE id = :id'
            );

            $stmt->execute([
                'id' => (int) $register->id,
                'duracion' => $register->duracion,
                'descripcion' => $register->descripcion,
                'estado' => $register->estado,
                'notas' => $register->notas,
            ]);

            $this->syncRegisterClients((int) $register->id, $register->idsClientes);
            $this->syncRegisterProviders((int) $register->id, $register->idsProveedores);
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function delete(int $registerId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM registers WHERE id = :id');
        $stmt->execute(['id' => $registerId]);
    }

    public function findById(int $registerId): ?array
    {
        $stmt = $this->pdo->prepare(
                'SELECT r.*,
                     COALESCE(client_ids.ids_clientes_csv, "") AS ids_clientes_csv,
                     COALESCE(provider_ids.ids_proveedores_csv, "") AS ids_proveedores_csv
             FROM registers r
                 LEFT JOIN (
                     SELECT register_id, GROUP_CONCAT(DISTINCT client_id ORDER BY client_id SEPARATOR ",") AS ids_clientes_csv
                     FROM register_clients
                     GROUP BY register_id
                 ) client_ids ON client_ids.register_id = r.id
                 LEFT JOIN (
                     SELECT register_id, GROUP_CONCAT(DISTINCT provider_id ORDER BY provider_id SEPARATOR ",") AS ids_proveedores_csv
                     FROM register_providers
                     GROUP BY register_id
                 ) provider_ids ON provider_ids.register_id = r.id
             WHERE r.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $registerId]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $idsCsv = (string) ($row['ids_clientes_csv'] ?? '');
        $ids = array_values(array_filter(array_map('intval', $idsCsv !== '' ? explode(',', $idsCsv) : []), static fn(int $id): bool => $id > 0));

        if ($ids === [] && (int) ($row['id_cliente'] ?? 0) > 0) {
            $ids = [(int) $row['id_cliente']];
        }

        $providerIdsCsv = (string) ($row['ids_proveedores_csv'] ?? '');
        $providerIds = array_values(array_filter(array_map('intval', $providerIdsCsv !== '' ? explode(',', $providerIdsCsv) : []), static fn(int $id): bool => $id > 0));

        $row['ids_clientes'] = $ids;
        $row['ids_proveedores'] = $providerIds;

        return $row;
    }

    public function findAllForUser(int $userId, bool $isAdmin): array
    {
        if ($isAdmin) {
            $stmt = $this->pdo->query(
                     'SELECT r.*, COALESCE(rca.clientes_nombres, c_legacy.nombre, "") AS clientes_nombres, COALESCE(rpa.proveedores_nombres, "") AS proveedores_nombres, u.username AS empleado_nombre
                 FROM registers r
                 LEFT JOIN (
                    SELECT rc.register_id, GROUP_CONCAT(DISTINCT c.nombre ORDER BY c.nombre SEPARATOR ", ") AS clientes_nombres
                    FROM register_clients rc
                    INNER JOIN clients c ON c.id = rc.client_id
                    GROUP BY rc.register_id
                 ) rca ON rca.register_id = r.id
                      LEFT JOIN (
                          SELECT rp.register_id, GROUP_CONCAT(DISTINCT p.proveedor ORDER BY p.proveedor SEPARATOR ", ") AS proveedores_nombres
                          FROM register_providers rp
                          INNER JOIN providers p ON p.id = rp.provider_id
                          GROUP BY rp.register_id
                      ) rpa ON rpa.register_id = r.id
                 LEFT JOIN clients c_legacy ON c_legacy.id = r.id_cliente
                 LEFT JOIN users u ON u.id = r.id_empleado
                 ORDER BY r.id DESC'
            );
            return $stmt->fetchAll();
        }

        $stmt = $this->pdo->prepare(
                'SELECT r.*, COALESCE(rca.clientes_nombres, c_legacy.nombre, "") AS clientes_nombres, COALESCE(rpa.proveedores_nombres, "") AS proveedores_nombres, u.username AS empleado_nombre
             FROM registers r
             LEFT JOIN (
                SELECT rc.register_id, GROUP_CONCAT(DISTINCT c.nombre ORDER BY c.nombre SEPARATOR ", ") AS clientes_nombres
                FROM register_clients rc
                INNER JOIN clients c ON c.id = rc.client_id
                GROUP BY rc.register_id
             ) rca ON rca.register_id = r.id
                 LEFT JOIN (
                     SELECT rp.register_id, GROUP_CONCAT(DISTINCT p.proveedor ORDER BY p.proveedor SEPARATOR ", ") AS proveedores_nombres
                     FROM register_providers rp
                     INNER JOIN providers p ON p.id = rp.provider_id
                     GROUP BY rp.register_id
                 ) rpa ON rpa.register_id = r.id
             LEFT JOIN clients c_legacy ON c_legacy.id = r.id_cliente
             LEFT JOIN users u ON u.id = r.id_empleado
             WHERE r.id_empleado = :id_empleado
             ORDER BY r.id DESC'
        );
        $stmt->execute(['id_empleado' => $userId]);

        return $stmt->fetchAll();
    }

    private function syncRegisterClients(int $registerId, array $clientIds): void
    {
        $normalizedIds = [];
        foreach ($clientIds as $clientId) {
            $intId = (int) $clientId;
            if ($intId > 0) {
                $normalizedIds[$intId] = true;
            }
        }

        $ids = array_map('intval', array_keys($normalizedIds));

        $deleteStmt = $this->pdo->prepare('DELETE FROM register_clients WHERE register_id = :register_id');
        $deleteStmt->execute(['register_id' => $registerId]);

        if ($ids !== []) {
            $insertStmt = $this->pdo->prepare('INSERT INTO register_clients (register_id, client_id) VALUES (:register_id, :client_id)');
            foreach ($ids as $clientId) {
                $insertStmt->execute([
                    'register_id' => $registerId,
                    'client_id' => $clientId,
                ]);
            }
        }

        $legacyClientId = $ids[0] ?? null;
        $legacyStmt = $this->pdo->prepare('UPDATE registers SET id_cliente = :id_cliente WHERE id = :id');
        $legacyStmt->bindValue(':id', $registerId, PDO::PARAM_INT);
        if ($legacyClientId === null) {
            $legacyStmt->bindValue(':id_cliente', null, PDO::PARAM_NULL);
        } else {
            $legacyStmt->bindValue(':id_cliente', $legacyClientId, PDO::PARAM_INT);
        }
        $legacyStmt->execute();
    }

    private function syncRegisterProviders(int $registerId, array $providerIds): void
    {
        $normalizedIds = [];
        foreach ($providerIds as $providerId) {
            $intId = (int) $providerId;
            if ($intId > 0) {
                $normalizedIds[$intId] = true;
            }
        }

        $ids = array_map('intval', array_keys($normalizedIds));

        $deleteStmt = $this->pdo->prepare('DELETE FROM register_providers WHERE register_id = :register_id');
        $deleteStmt->execute(['register_id' => $registerId]);

        if ($ids !== []) {
            $insertStmt = $this->pdo->prepare('INSERT INTO register_providers (register_id, provider_id) VALUES (:register_id, :provider_id)');
            foreach ($ids as $providerId) {
                $insertStmt->execute([
                    'register_id' => $registerId,
                    'provider_id' => $providerId,
                ]);
            }
        }
    }
}
