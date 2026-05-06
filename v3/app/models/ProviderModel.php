<?php

declare(strict_types=1);

final class ProviderModel
{
    public function __construct(
        public ?int $id,
        public string $proveedor,
        public string $telefono,
        public string $contacto,
        public string $movil,
        public string $correo,
        public string $categoria
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            isset($data['id']) ? (int) $data['id'] : null,
            trim((string) ($data['proveedor'] ?? '')),
            trim((string) ($data['telefono'] ?? '')),
            trim((string) ($data['contacto'] ?? '')),
            trim((string) ($data['movil'] ?? '')),
            trim((string) ($data['correo'] ?? '')),
            trim((string) ($data['categoria'] ?? ''))
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'proveedor' => $this->proveedor,
            'telefono' => $this->telefono,
            'contacto' => $this->contacto,
            'movil' => $this->movil,
            'correo' => $this->correo,
            'categoria' => $this->categoria,
        ];
    }
}
