<?php

require_once __DIR__ . '/../core/Config.php';
require_once __DIR__ . '/../core/DomainError.php';
require_once __DIR__ . '/../core/Password.php';
require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../repositories/RoleRepository.php';


final class AuthService
{
    private UserRepository $userRepository;
    private RoleRepository $roleRepository;

    public function __construct(UserRepository $userRepository, RoleRepository $roleRepository)
    {
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
    }

    public function register(string $email, string $password, string $confirmedPassword): int
    {
        $email = trim(mb_strtolower($email));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Nieprawidłowy adres email.', DomainError::BAD_EMAIL);
        }

        if ($password !== $confirmedPassword) {
            throw new RuntimeException('Hasła nie są takie same.', DomainError::PASSWORD_MISMATCH);
        }

        if (trim($password) === '' || mb_strlen($password) < Config::MIN_PASSWORD_LENGTH) {
            throw new RuntimeException(
                'Hasło musi mieć co najmniej ' . Config::MIN_PASSWORD_LENGTH . ' znaków.',
                DomainError::BAD_PASSWORD
            );
        }

        if ($this->userRepository->existsByEmail($email)) {
            throw new RuntimeException('Ten adres email jest już zajęty.', DomainError::EMAIL_TAKEN);
        }

        $readerRoleId = $this->roleRepository->findIdByCode(Config::ROLE_CODE_READER);
        if ($readerRoleId === null) {
            throw new RuntimeException('Błąd konfiguracji systemu.', DomainError::ROLE_NOT_FOUND);
        }

        $hash = Password::hash($password);
        return $this->userRepository->create($email, $hash, $readerRoleId);
    }

    public function login(string $email, string $password): array
    {
        $email = trim(mb_strtolower($email));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Nieprawidłowy adres email.', DomainError::BAD_EMAIL);
        }

        if (trim($password) === '') {
            throw new RuntimeException('Nieprawidłowe dane logowania.', DomainError::INVALID_LOGIN);
        }

        $user = $this->userRepository->findByEmail($email);
        if (!$user) {
            throw new RuntimeException('Nieprawidłowe dane logowania.', DomainError::INVALID_LOGIN);
        }

        if (!Password::verify($password, $user['password_hash'])) {
            throw new RuntimeException('Nieprawidłowe dane logowania.', DomainError::INVALID_LOGIN);
        }

        return [
            'id' => (int)$user['id'],
            'email' => (string)$user['email'],
            'role_id' => (int)$user['role_id'],
        ];
    }
}