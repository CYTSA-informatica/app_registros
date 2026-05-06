<?php

declare(strict_types=1);

final class UserDAO
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, username, email, dpto, contra_hash, isAdmin FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findByEmail(string $email): array
    {
        $stmt = $this->pdo->prepare('SELECT id, username, email, dpto, contra_hash, isAdmin FROM users WHERE email = :email ORDER BY username ASC');
        $stmt->execute(['email' => $email]);

        return $stmt->fetchAll();
    }

    public function findById(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, username, email, dpto, contra_hash, isAdmin FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findAllBasic(): array
    {
        return $this->pdo->query('SELECT id, username, email, dpto, isAdmin FROM users ORDER BY username ASC')->fetchAll();
    }

    public function findBasicById(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, username, email, dpto, isAdmin FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(UserModel $user): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (username, email, dpto, contra_hash, isAdmin) VALUES (:username, :email, :dpto, :contra_hash, :isAdmin)'
        );

        $stmt->execute([
            'username' => $user->username,
            'email' => $user->email,
            'dpto' => $user->dpto,
            'contra_hash' => $user->contraHash,
            'isAdmin' => $user->isAdmin ? 1 : 0,
        ]);
    }

    public function update(UserModel $user, bool $updatePassword): void
    {
        if ($updatePassword) {
            $stmt = $this->pdo->prepare(
                'UPDATE users SET username = :username, email = :email, dpto = :dpto, isAdmin = :isAdmin, contra_hash = :contra_hash WHERE id = :id'
            );

            $stmt->execute([
                'id' => (int) $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'dpto' => $user->dpto,
                'isAdmin' => $user->isAdmin ? 1 : 0,
                'contra_hash' => $user->contraHash,
            ]);

            return;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE users SET username = :username, email = :email, dpto = :dpto, isAdmin = :isAdmin WHERE id = :id'
        );

        $stmt->execute([
            'id' => (int) $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'dpto' => $user->dpto,
            'isAdmin' => $user->isAdmin ? 1 : 0,
        ]);
    }

    public function delete(int $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
    }
}
