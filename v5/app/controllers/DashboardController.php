<?php

declare(strict_types=1);

final class DashboardController
{
    public function __construct(
        private RegisterDAO $registerDAO,
        private ClientDAO $clientDAO,
        private ProviderDAO $providerDAO,
        private UserDAO $userDAO
    ) {
    }

    public function handleAction(string $action, array $post, array $currentUser): ?string
    {
        $userId = (int) $currentUser['id'];
        $isAdmin = (bool) $currentUser['isAdmin'];

        if ($action === 'create_register') {
            $fechaFromPost = trim((string) ($post['fecha'] ?? ''));
            $fechaValidada = '';
            
            if ($fechaFromPost !== '') {
                if (!$this->isValidDate($fechaFromPost)) {
                    throw new RuntimeException('La fecha proporcionada no es válida.');
                }
                if ($this->isFutureDate($fechaFromPost)) {
                    throw new RuntimeException('No se puede usar una fecha futura.');
                }
                $fechaValidada = $fechaFromPost;
            }
            
            $register = RegisterModel::fromArray([
                'duracion' => $this->parseDuration($post),
                'descripcion' => $post['descripcion'] ?? '',
                'estado' => $post['estado'] ?? 'pendiente',
                'notas' => $post['notas'] ?? '',
                'id_empleado' => $userId,
                'ids_clientes' => $post['ids_clientes'] ?? [],
                'ids_proveedores' => $post['ids_proveedores'] ?? [],
                'fecha' => $fechaValidada,
            ]);

            $this->registerDAO->create($register);
            return 'Registro creado correctamente.';
        }

        if ($action === 'update_register') {
            $registerId = (int) ($post['id'] ?? 0);
            $ownerId = (int) ($post['id_empleado'] ?? 0);
            if (!$isAdmin && $ownerId !== $userId) {
                throw new RuntimeException('No puedes editar registros de otro usuario.');
            }

            $register = RegisterModel::fromArray([
                'id' => $registerId,
                'duracion' => $this->parseDuration($post),
                'descripcion' => $post['descripcion'] ?? '',
                'estado' => $post['estado'] ?? 'pendiente',
                'notas' => $post['notas'] ?? '',
                'ids_clientes' => $post['ids_clientes'] ?? [],
                'ids_proveedores' => $post['ids_proveedores'] ?? [],
                'fecha' => $post['fecha'] ?? '',
            ]);

            $this->registerDAO->update($register);
            return 'Registro actualizado.';
        }

        if ($action === 'delete_register') {
            $registerId = (int) ($post['id'] ?? 0);
            $ownerId = (int) ($post['id_empleado'] ?? 0);
            if (!$isAdmin && $ownerId !== $userId) {
                throw new RuntimeException('No puedes borrar registros de otro usuario.');
            }

            $this->registerDAO->delete($registerId);
            return 'Registro eliminado.';
        }

        if ($action === 'create_client') {
            require_admin();
            $client = ClientModel::fromArray($post);
            $this->clientDAO->create($client);
            return 'Cliente creado.';
        }

        if ($action === 'update_client') {
            require_admin();
            $client = ClientModel::fromArray($post);
            $this->clientDAO->update($client);
            return 'Cliente actualizado.';
        }

        if ($action === 'delete_client') {
            require_admin();
            $this->clientDAO->delete((int) ($post['id'] ?? 0));
            return 'Cliente eliminado.';
        }

        if ($action === 'create_provider') {
            require_admin();
            $provider = ProviderModel::fromArray($post);
            $this->providerDAO->create($provider);
            return 'Proveedor creado.';
        }

        if ($action === 'update_provider') {
            require_admin();
            $provider = ProviderModel::fromArray($post);
            $this->providerDAO->update($provider);
            return 'Proveedor actualizado.';
        }

        if ($action === 'delete_provider') {
            require_admin();
            $this->providerDAO->delete((int) ($post['id'] ?? 0));
            return 'Proveedor eliminado.';
        }

        if ($action === 'create_user') {
            require_admin();
            $password = (string) ($post['contra_hash'] ?? '');
            $username = trim((string) ($post['username'] ?? ''));
            if ($password === '') {
                throw new RuntimeException('La contrasena es obligatoria.');
            }
            if ($username === '') {
                throw new RuntimeException('El nombre de usuario es obligatorio.');
            }

            $user = UserModel::fromArray([
                'username' => $username,
                'email' => $post['email'] ?? '',
                'dpto' => $post['dpto'] ?? '',
                'isAdmin' => isset($post['isAdmin']),
                'contra_hash' => password_hash($password, PASSWORD_BCRYPT),
            ]);

            $this->userDAO->create($user);
            return 'Empleado creado.';
        }

        if ($action === 'update_user') {
            require_admin();
            $password = trim((string) ($post['contra_hash'] ?? ''));
            $username = trim((string) ($post['username'] ?? ''));

            if ($username === '') {
                throw new RuntimeException('El nombre de usuario es obligatorio.');
            }

            $user = UserModel::fromArray([
                'id' => $post['id'] ?? 0,
                'username' => $username,
                'email' => $post['email'] ?? '',
                'dpto' => $post['dpto'] ?? '',
                'isAdmin' => isset($post['isAdmin']),
                'contra_hash' => $password !== '' ? password_hash($password, PASSWORD_BCRYPT) : '',
            ]);

            $this->userDAO->update($user, $password !== '');
            return 'Empleado actualizado.';
        }

        if ($action === 'delete_user') {
            require_admin();
            $target = (int) ($post['id'] ?? 0);
            if ($target === $userId) {
                throw new RuntimeException('No puedes borrarte a ti mismo.');
            }

            $this->userDAO->delete($target);
            return 'Empleado eliminado.';
        }

        return null;
    }

    public function getDashboardData(array $currentUser, int $editRegisterId, int $editClientId, int $editProviderId, int $editUserId): array
    {
        $userId = (int) $currentUser['id'];
        $isAdmin = (bool) $currentUser['isAdmin'];

        $clients = $this->clientDAO->findAll();
        $providers = $this->providerDAO->findAll();
        $users = $isAdmin ? $this->userDAO->findAllBasic() : [];
        $registers = $this->registerDAO->findAllForUser($userId, $isAdmin);

        $registerForm = [
            'id' => null,
            'duracion' => '',
            'duracion_horas' => '',
            'duracion_minutos' => '',
            'descripcion' => '',
            'estado' => 'pendiente',
            'notas' => '',
            'ids_clientes' => [],
            'ids_proveedores' => [],
            'id_empleado' => $userId,
        ];

        if ($editRegisterId > 0) {
            $row = $this->registerDAO->findById($editRegisterId);
            if ($row && ($isAdmin || (int) $row['id_empleado'] === $userId)) {
                $totalMinutes = (int) ($row['duracion'] ?? 0);
                $registerForm = [
                    'id' => (int) $row['id'],
                    'duracion' => (string) $totalMinutes,
                    'duracion_horas' => (string) intdiv($totalMinutes, 60),
                    'duracion_minutos' => (string) ($totalMinutes % 60),
                    'descripcion' => (string) ($row['descripcion'] ?? ''),
                    'estado' => (string) ($row['estado'] ?? 'pendiente'),
                    'notas' => (string) ($row['notas'] ?? ''),
                    'ids_clientes' => is_array($row['ids_clientes'] ?? null) ? $row['ids_clientes'] : [],
                    'ids_proveedores' => is_array($row['ids_proveedores'] ?? null) ? $row['ids_proveedores'] : [],
                    'id_empleado' => (int) ($row['id_empleado'] ?? $userId),
                    'fecha' => $row['fecha_inicio'] ? date('Y-m-d\TH:i', strtotime($row['fecha_inicio'])) : '',
                    'fecha_actualizacion' => (string) ($row['fecha_actualizacion'] ?? ''),
                ];
            }
        }

        $clientForm = [
            'id' => null,
            'nombre' => '',
            'email' => '',
            'phone' => '',
            'address' => '',
            'dni' => '',
            'pais' => '',
            'postal' => '',
            'poblacion' => '',
            'provincia' => '',
        ];

        if ($editClientId > 0) {
            $row = $this->clientDAO->findById($editClientId);
            if ($row) {
                $clientForm = [
                    'id' => (int) $row['id'],
                    'nombre' => (string) ($row['nombre'] ?? ''),
                    'email' => (string) ($row['email'] ?? ''),
                    'phone' => (string) ($row['phone'] ?? ''),
                    'address' => (string) ($row['address'] ?? ''),
                    'dni' => (string) ($row['dni'] ?? ''),
                    'pais' => (string) ($row['pais'] ?? ''),
                    'postal' => (string) ($row['postal'] ?? ''),
                    'poblacion' => (string) ($row['poblacion'] ?? ''),
                    'provincia' => (string) ($row['provincia'] ?? ''),
                ];
            }
        }

        $providerForm = [
            'id' => null,
            'proveedor' => '',
            'telefono' => '',
            'contacto' => '',
            'movil' => '',
            'correo' => '',
            'categoria' => '',
        ];

        if ($editProviderId > 0 && $isAdmin) {
            $row = $this->providerDAO->findById($editProviderId);
            if ($row) {
                $providerForm = [
                    'id' => (int) $row['id'],
                    'proveedor' => (string) ($row['proveedor'] ?? ''),
                    'telefono' => (string) ($row['telefono'] ?? ''),
                    'contacto' => (string) ($row['contacto'] ?? ''),
                    'movil' => (string) ($row['movil'] ?? ''),
                    'correo' => (string) ($row['correo'] ?? ''),
                    'categoria' => (string) ($row['categoria'] ?? ''),
                ];
            }
        }

        $userForm = [
            'id' => null,
            'username' => '',
            'email' => '',
            'dpto' => '',
            'contra_hash' => '',
            'isAdmin' => false,
        ];

        if ($editUserId > 0) {
            $row = $this->userDAO->findBasicById($editUserId);
            if ($row) {
                $userForm = [
                    'id' => (int) $row['id'],
                    'username' => (string) ($row['username'] ?? ''),
                    'email' => (string) ($row['email'] ?? ''),
                    'dpto' => (string) ($row['dpto'] ?? ''),
                    'contra_hash' => '',
                    'isAdmin' => (bool) ($row['isAdmin'] ?? false),
                ];
            }
        }

        return [
            'clients' => $clients,
            'providers' => $providers,
            'users' => $users,
            'registers' => $registers,
            'registerForm' => $registerForm,
            'clientForm' => $clientForm,
            'providerForm' => $providerForm,
            'userForm' => $userForm,
        ];
    }

    private function parseDuration(array $post): int
    {
        $hours = trim((string) ($post['duracion_horas'] ?? ''));
        $minutes = trim((string) ($post['duracion_minutos'] ?? ''));

        if ($hours === '' && $minutes === '') {
            return max(0, (int) ($post['duracion'] ?? 0));
        }

        $hoursValue = $hours === '' ? 0 : (int) $hours;
        $minutesValue = $minutes === '' ? 0 : (int) $minutes;

        if ($hoursValue < 0) {
            throw new RuntimeException('Las horas no pueden ser negativas.');
        }

        if ($minutesValue < 0 || $minutesValue > 59) {
            throw new RuntimeException('Los minutos deben estar entre 0 y 59.');
        }

        return ($hoursValue * 60) + $minutesValue;
    }

    private function isValidDate(string $date): bool
    {
        return strtotime($date) !== false;
    }

    private function isFutureDate(string $date): bool
    {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return false;
        }
        
        return $timestamp > time();
    }
}
