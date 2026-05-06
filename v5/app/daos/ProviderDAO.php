<?php

declare(strict_types=1);

final class ProviderDAO
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findAll(): array
    {
        return $this->pdo->query('SELECT * FROM providers ORDER BY proveedor ASC')->fetchAll();
    }

    public function findById(int $providerId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM providers WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $providerId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(ProviderModel $provider): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO providers (proveedor, telefono, contacto, movil, correo, categoria) VALUES (:proveedor, :telefono, :contacto, :movil, :correo, :categoria)'
        );

        $stmt->execute([
            'proveedor' => $provider->proveedor,
            'telefono' => $provider->telefono,
            'contacto' => $provider->contacto,
            'movil' => $provider->movil,
            'correo' => $provider->correo,
            'categoria' => $provider->categoria,
        ]);
    }

    public function update(ProviderModel $provider): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE providers SET proveedor = :proveedor, telefono = :telefono, contacto = :contacto, movil = :movil, correo = :correo, categoria = :categoria WHERE id = :id'
        );

        $stmt->execute([
            'id' => (int) $provider->id,
            'proveedor' => $provider->proveedor,
            'telefono' => $provider->telefono,
            'contacto' => $provider->contacto,
            'movil' => $provider->movil,
            'correo' => $provider->correo,
            'categoria' => $provider->categoria,
        ]);
    }

    public function delete(int $providerId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM providers WHERE id = :id');
        $stmt->execute(['id' => $providerId]);
    }
}
