<?php

declare(strict_types=1);

final class ClientDAO
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findAll(): array
    {
        return $this->pdo->query('SELECT * FROM clients ORDER BY nombre ASC')->fetchAll();
    }

    public function findById(int $clientId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM clients WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $clientId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(ClientModel $client): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO clients (nombre, email, phone, address, dni, pais, postal, poblacion, provincia) VALUES (:nombre, :email, :phone, :address, :dni, :pais, :postal, :poblacion, :provincia)'
        );

        $stmt->execute([
            'nombre' => $client->nombre,
            'email' => $client->email,
            'phone' => $client->phone,
            'address' => $client->address,
            'dni' => $client->dni,
            'pais' => $client->pais,
            'postal' => $client->postal,
            'poblacion' => $client->poblacion,
            'provincia' => $client->provincia,
        ]);
    }

    public function update(ClientModel $client): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE clients SET nombre = :nombre, email = :email, phone = :phone, address = :address, dni = :dni, pais = :pais, postal = :postal, poblacion = :poblacion, provincia = :provincia WHERE id = :id'
        );

        $stmt->execute([
            'id' => (int) $client->id,
            'nombre' => $client->nombre,
            'email' => $client->email,
            'phone' => $client->phone,
            'address' => $client->address,
            'dni' => $client->dni,
            'pais' => $client->pais,
            'postal' => $client->postal,
            'poblacion' => $client->poblacion,
            'provincia' => $client->provincia,
        ]);
    }

    public function delete(int $clientId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM clients WHERE id = :id');
        $stmt->execute(['id' => $clientId]);
    }
}
