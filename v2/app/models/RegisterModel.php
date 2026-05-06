<?php

declare(strict_types=1);

final class RegisterModel
{
    public function __construct(
        public ?int $id,
        public int $duracion,
        public string $descripcion,
        public string $estado,
        public string $notas,
        public int $idEmpleado,
        public int $idCliente
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            isset($data['id']) ? (int) $data['id'] : null,
            (int) ($data['duracion'] ?? 0),
            trim((string) ($data['descripcion'] ?? '')),
            trim((string) ($data['estado'] ?? 'pendiente')),
            trim((string) ($data['notas'] ?? '')),
            (int) ($data['id_empleado'] ?? $data['idEmpleado'] ?? 0),
            (int) ($data['id_cliente'] ?? $data['idCliente'] ?? 0)
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'duracion' => $this->duracion,
            'descripcion' => $this->descripcion,
            'estado' => $this->estado,
            'notas' => $this->notas,
            'id_empleado' => $this->idEmpleado,
            'id_cliente' => $this->idCliente,
        ];
    }
}
