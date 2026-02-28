<?php

namespace Templates;

/**
 * Example service that gets injected globally to all templates.
 * In a real app, this could be your TemplateExtension with auth, i18n, etc.
 */
class AppService
{
    public function __construct(
        private string $appName = 'My App',
        private string $appVersion = '1.0.0',
    ) {
    }

    public function getAppName(): string
    {
        return $this->appName;
    }

    public function getVersion(): string
    {
        return $this->appVersion;
    }

    public function url(string $path): string
    {
        return '/' . ltrim($path, '/');
    }

    public function asset(string $path): string
    {
        return '/assets/' . ltrim($path, '/') . '?v=' . $this->appVersion;
    }

    public function formatDate(\DateTimeInterface $date): string
    {
        return $date->format('F j, Y');
    }

    public function isAuthenticated(): bool
    {
        return true; // Simulated
    }

    public function getCurrentUser(): ?User
    {
        // Simulated authenticated user
        return new User(
            id: 1,
            name: 'Current User',
            email: 'current@example.com',
            role: 'admin',
            createdAt: new \DateTimeImmutable('2024-01-15'),
        );
    }
}
