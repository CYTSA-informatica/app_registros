<?php

declare(strict_types=1);

final class UserModel
{
    public function __construct(
        public ?int $id,
        public string $username,
        public string $email,
        public string $dpto,
        public bool $isAdmin,
        public string $contraHash = ''
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            isset($data['id']) ? (int) $data['id'] : null,
            trim((string) ($data['username'] ?? '')),
            trim((string) ($data['email'] ?? '')),
            trim((string) ($data['dpto'] ?? '')),
            (bool) ($data['isAdmin'] ?? false),
            (string) ($data['contra_hash'] ?? $data['contraHash'] ?? '')
        );
    }

    public function toArray(bool $includePasswordHash = false): array
    {
        $data = [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'dpto' => $this->dpto,
            'isAdmin' => $this->isAdmin,
        ];

        if ($includePasswordHash) {
            $data['contra_hash'] = $this->contraHash;
        }

        return $data;
    }
}
