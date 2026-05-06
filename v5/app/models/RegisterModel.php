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
        public array $idsClientes,
        public array $idsProveedores,
        public string $fecha = '',
        public string $fecha_actualizacion = ''
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
            self::normalizeIds(
                $data['ids_clientes']
                    ?? $data['idsClientes']
                    ?? ($data['id_cliente'] ?? $data['idCliente'] ?? [])
            ),
            self::normalizeIds(
                $data['ids_proveedores']
                    ?? $data['idsProveedores']
                    ?? []
            ),
            trim((string) ($data['fecha'] ?? $data['fecha_inicio'] ?? '')),
            trim((string) ($data['fecha_actualizacion'] ?? ''))
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
            'ids_clientes' => $this->idsClientes,
            'ids_proveedores' => $this->idsProveedores,
            'fecha_inicio' => $this->fecha,
            'fecha_actualizacion' => $this->fecha_actualizacion,
        ];
    }

    private static function normalizeIds(mixed $rawIds): array
    {
        if (is_string($rawIds)) {
            $rawIds = explode(',', $rawIds);
        }

        if (is_int($rawIds)) {
            $rawIds = [$rawIds];
        }

        if (!is_array($rawIds)) {
            return [];
        }

        $uniqueIds = [];
        foreach ($rawIds as $id) {
            $intId = (int) $id;
            if ($intId > 0) {
                $uniqueIds[$intId] = true;
            }
        }

        return array_map('intval', array_keys($uniqueIds));
    }
}
