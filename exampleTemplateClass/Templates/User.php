<?php

namespace Templates;

/**
 * Simple User entity for template examples.
 */
readonly class User
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $role = 'user',
        public ?\DateTimeImmutable $createdAt = null,
    ) {
    }

    public function getInitials(): string
    {
        $parts = explode(' ', $this->name);
        $initials = '';
        foreach ($parts as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $initials;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
