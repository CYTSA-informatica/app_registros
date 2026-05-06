<?php

declare(strict_types=1);

final class AuthController
{
    public function __construct(private UserDAO $userDAO)
    {
    }

    public function login(string $identifier, string $password): array
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return ['status' => 'invalid'];
        }

        $user = $this->userDAO->findByUsername($identifier);
        if ($user) {
            $storedPassword = (string) ($user['contra_hash'] ?? '');
            $passwordMatches = $storedPassword !== '' && (
                password_verify($password, $storedPassword)
                || hash_equals($storedPassword, $password)
                || hash_equals($storedPassword, hash('sha256', $password))
            );

            if (!$passwordMatches) {
                return ['status' => 'invalid'];
            }

            establish_user_session($user, true);
            return ['status' => 'success'];
        }

        $candidates = $this->userDAO->findByEmail($identifier);

        if (count($candidates) === 1) {
            $storedPassword = (string) ($candidates[0]['contra_hash'] ?? '');
            $passwordMatches = $storedPassword !== '' && (
                password_verify($password, $storedPassword)
                || hash_equals($storedPassword, $password)
                || hash_equals($storedPassword, hash('sha256', $password))
            );

            if (!$passwordMatches) {
                return ['status' => 'invalid'];
            }

            establish_user_session($candidates[0], true);
            unset($_SESSION['pending_login_candidates']);
            return ['status' => 'success'];
        }

        if (count($candidates) > 1) {
            $_SESSION['pending_login_candidates'] = array_map(static fn (array $candidate): array => [
                'id' => (int) $candidate['id'],
                'username' => (string) ($candidate['username'] ?? ''),
                'email' => (string) ($candidate['email'] ?? ''),
                'dpto' => (string) ($candidate['dpto'] ?? ''),
            ], $candidates);

            return [
                'status' => 'select',
                'candidates' => $_SESSION['pending_login_candidates'],
            ];
        }

        unset($_SESSION['pending_login_candidates']);
        return ['status' => 'invalid'];
    }

    public function completeSelectedLogin(int $userId, string $password): bool
    {
        $pendingCandidates = $_SESSION['pending_login_candidates'] ?? [];
        $password = (string) $password;

        foreach ($pendingCandidates as $candidate) {
            if ((int) ($candidate['id'] ?? 0) !== $userId) {
                continue;
            }

            $user = $this->userDAO->findById($userId);
            if (!$user) {
                break;
            }

            $storedPassword = (string) ($user['contra_hash'] ?? '');
            $passwordMatches = $storedPassword !== '' && (
                password_verify($password, $storedPassword)
                || hash_equals($storedPassword, $password)
                || hash_equals($storedPassword, hash('sha256', $password))
            );

            if (!$passwordMatches) {
                return false;
            }

            establish_user_session($user, true);
            unset($_SESSION['pending_login_candidates']);
            return true;
        }

        return false;
    }
}
