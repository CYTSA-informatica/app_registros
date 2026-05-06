<?php

declare(strict_types=1);

final class ClientModel
{
    public function __construct(
        public ?int $id,
        public string $nombre,
        public string $email,
        public string $phone,
        public string $address,
        public string $dni,
        public string $pais,
        public string $postal,
        public string $poblacion,
        public string $provincia
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            isset($data['id']) ? (int) $data['id'] : null,
            trim((string) ($data['nombre'] ?? '')),
            trim((string) ($data['email'] ?? '')),
            trim((string) ($data['phone'] ?? '')),
            trim((string) ($data['address'] ?? '')),
            trim((string) ($data['dni'] ?? '')),
            trim((string) ($data['pais'] ?? '')),
            trim((string) ($data['postal'] ?? '')),
            trim((string) ($data['poblacion'] ?? '')),
            trim((string) ($data['provincia'] ?? ''))
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'dni' => $this->dni,
            'pais' => $this->pais,
            'postal' => $this->postal,
            'poblacion' => $this->poblacion,
            'provincia' => $this->provincia,
        ];
    }
}
