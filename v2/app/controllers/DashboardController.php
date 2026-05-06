<?php

declare(strict_types=1);

final class DashboardController
{
    public function __construct(
        private RegisterDAO $registerDAO,
        private ClientDAO $clientDAO,
        private UserDAO $userDAO
    ) {
    }

    public function handleAction(string $action, array $post, array $currentUser): ?string
    {
        $userId = (int) $currentUser['id'];
        $isAdmin = (bool) $currentUser['isAdmin'];

        if ($action === 'create_register') {
            $register = RegisterModel::fromArray([
                'duracion' => $post['duracion'] ?? 0,
                'descripcion' => $post['descripcion'] ?? '',
                'estado' => $post['estado'] ?? 'pendiente',
                'notas' => $post['notas'] ?? '',
                'id_empleado' => $userId,
                'id_cliente' => $post['id_cliente'] ?? 0,
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
                'duracion' => $post['duracion'] ?? 0,
                'descripcion' => $post['descripcion'] ?? '',
                'estado' => $post['estado'] ?? 'pendiente',
                'notas' => $post['notas'] ?? '',
                'id_cliente' => $post['id_cliente'] ?? 0,
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

    public function getDashboardData(array $currentUser, int $editRegisterId, int $editClientId, int $editUserId): array
    {
        $userId = (int) $currentUser['id'];
        $isAdmin = (bool) $currentUser['isAdmin'];

        $clients = $this->clientDAO->findAll();
        $users = $isAdmin ? $this->userDAO->findAllBasic() : [];
        $registers = $this->registerDAO->findAllForUser($userId, $isAdmin);

        $registerForm = [
            'id' => null,
            'duracion' => '',
            'descripcion' => '',
            'estado' => 'pendiente',
            'notas' => '',
            'id_cliente' => '',
            'id_empleado' => $userId,
        ];

        if ($editRegisterId > 0) {
            $row = $this->registerDAO->findById($editRegisterId);
            if ($row && ($isAdmin || (int) $row['id_empleado'] === $userId)) {
                $registerForm = [
                    'id' => (int) $row['id'],
                    'duracion' => (string) ($row['duracion'] ?? ''),
                    'descripcion' => (string) ($row['descripcion'] ?? ''),
                    'estado' => (string) ($row['estado'] ?? 'pendiente'),
                    'notas' => (string) ($row['notas'] ?? ''),
                    'id_cliente' => (string) ($row['id_cliente'] ?? ''),
                    'id_empleado' => (int) ($row['id_empleado'] ?? $userId),
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

        $userForm = [
            'id' => null,
            'username' => '',
            'email' => '',
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
                    'contra_hash' => '',
                    'isAdmin' => (bool) ($row['isAdmin'] ?? false),
                ];
            }
        }

        return [
            'clients' => $clients,
            'users' => $users,
            'registers' => $registers,
            'registerForm' => $registerForm,
            'clientForm' => $clientForm,
            'userForm' => $userForm,
        ];
    }
}
