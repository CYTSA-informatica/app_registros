<?php

declare(strict_types=1);

final class RegisterDAO
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(RegisterModel $register): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO registers (duracion, descripcion, estado, notas, id_empleado, id_cliente) VALUES (:duracion, :descripcion, :estado, :notas, :id_empleado, :id_cliente)'
        );

        $stmt->execute([
            'duracion' => $register->duracion,
            'descripcion' => $register->descripcion,
            'estado' => $register->estado,
            'notas' => $register->notas,
            'id_empleado' => $register->idEmpleado,
            'id_cliente' => $register->idCliente,
        ]);
    }

    public function update(RegisterModel $register): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE registers SET duracion = :duracion, descripcion = :descripcion, estado = :estado, notas = :notas, id_cliente = :id_cliente WHERE id = :id'
        );

        $stmt->execute([
            'id' => (int) $register->id,
            'duracion' => $register->duracion,
            'descripcion' => $register->descripcion,
            'estado' => $register->estado,
            'notas' => $register->notas,
            'id_cliente' => $register->idCliente,
        ]);
    }

    public function delete(int $registerId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM registers WHERE id = :id');
        $stmt->execute(['id' => $registerId]);
    }

    public function findById(int $registerId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM registers WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $registerId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findAllForUser(int $userId, bool $isAdmin): array
    {
        if ($isAdmin) {
            $stmt = $this->pdo->query('SELECT r.*, c.nombre AS cliente_nombre, u.username AS empleado_nombre FROM registers r LEFT JOIN clients c ON c.id = r.id_cliente LEFT JOIN users u ON u.id = r.id_empleado ORDER BY r.id DESC');
            return $stmt->fetchAll();
        }

        $stmt = $this->pdo->prepare('SELECT r.*, c.nombre AS cliente_nombre, u.username AS empleado_nombre FROM registers r LEFT JOIN clients c ON c.id = r.id_cliente LEFT JOIN users u ON u.id = r.id_empleado WHERE r.id_empleado = :id_empleado ORDER BY r.id DESC');
        $stmt->execute(['id_empleado' => $userId]);

        return $stmt->fetchAll();
    }
}
