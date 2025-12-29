<?php

require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../repositories/RoleRepository.php';
require_once __DIR__ . '/../core/Password.php';
require_once __DIR__ . '/../core/DomainError.php';


final class AuthService {
    private UserRepository $userRepository;
    private RoleRepository $roleRepository;

    public function __construct(UserRepository $userRepository, RoleRepository $roleRepository) 
    {
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
    }

    public function register(string $email, string $password)
    {
        $email = trim(mb_strtolower($email));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Invalid email.', DomainError::BAD_EMAIL);
        }

        if (trim($password) === '' || mb_strlen($password) < 8) {
            throw new RuntimeException('Password needs to have at least 8 characters.', DomainError::BAD_PASSWORD);
        }

        if ($this->userRepository->existsByEmail($email)) {
            throw new RuntimeException('Email already taken.', DomainError::EMAIL_TAKEN);
        }

        $readerRoleId = $this->roleRepository->findIdByName('READER');
        if ($readerRoleId === null) {
            throw new RuntimeException('Reader role not found.', DomainError::ROLE_NOT_FOUND);
        }

        $hash = Password::hash($password);
        return $this->userRepository->create($email, $hash, $readerRoleId);
    }

    public function login(string $email, string $password)
    {
        $email = trim(mb_strtolower($email));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Invalid email.', DomainError::BAD_EMAIL);
        }

        if (trim($password) === '') {
            throw new RuntimeException('Invalid credentials.', DomainError::INVALID_LOGIN);
        }

        $user = $this->userRepository->findByEmail($email);
        if (!$user) {
            throw new RuntimeException('Invalid credentials.', DomainError::INVALID_LOGIN);
        }

        if (!Password::verify($password, $user['password_hash'])) {
            throw new RuntimeException('Invalid credentials.', DomainError::INVALID_LOGIN);
        }

        return [
            'id' => (int)$user['id'],
            'email' => (string)$user['email'],
            'role_id' => (int)$user['role_id'],
        ];
    }
}