<?php

namespace Templates;

use PiedWeb\Splates\Template\Attribute\TemplateData;
use PiedWeb\Splates\Template\TemplateAbstract;

/**
 * User profile page demonstrating:
 * - Object data binding with #[TemplateData]
 * - Global service injection
 * - Heredoc syntax for better IDE highlighting
 * - Component composition with Card
 */
class Profile extends TemplateAbstract
{
    #[TemplateData(global: true)]
    public AppService $app;

    public function __construct(
        #[TemplateData]
        public User $user,
    ) {
    }

    public function __invoke(): void
    {
        echo $this->render(new Layout(
            title: 'Profile: ' . $this->user->name,
            header: fn () => $this->renderHeader(),
            content: fn () => $this->renderContent(),
            sidebar: fn () => $this->renderSidebar(),
        ));
    }

    private function renderHeader(): string
    {
        $initials = $this->e($this->user->getInitials());
        $name = $this->e($this->user->name);
        $adminBadge = $this->user->isAdmin()
            ? '<span class="badge badge-admin">Admin</span>'
            : '';

        return <<<HTML
            <h1>
                <span class="avatar">{$initials}</span>
                {$name}
                {$adminBadge}
            </h1>
            HTML;
    }

    private function renderContent(): string
    {
        $email = $this->e($this->user->email);
        $role = $this->e(ucfirst($this->user->role));
        $memberSince = $this->user->createdAt
            ? '<dt>Member since</dt><dd>' . $this->e($this->app->formatDate($this->user->createdAt)) . '</dd>'
            : '';

        $userInfoCard = $this->render(new Card(
            title: 'User Information',
            content: fn () => <<<HTML
                <dl class="info-list">
                    <dt>Email</dt>
                    <dd>{$email}</dd>
                    <dt>Role</dt>
                    <dd>{$role}</dd>
                    {$memberSince}
                </dl>
                HTML,
        ));

        $editUrl = $this->e($this->app->url('/users/' . $this->user->id . '/edit'));
        $backUrl = $this->e($this->app->url('/users'));

        $actionsCard = $this->render(new Card(
            title: 'Actions',
            content: fn () => <<<HTML
                <a href="{$editUrl}" class="btn">Edit Profile</a>
                <a href="{$backUrl}" class="btn btn-secondary">Back to Users</a>
                HTML,
        ));

        return $userInfoCard . $actionsCard;
    }

    private function renderSidebar(): string
    {
        $dashboardUrl = $this->e($this->app->url('/dashboard'));
        $usersUrl = $this->e($this->app->url('/users'));
        $settingsUrl = $this->e($this->app->url('/settings'));

        return
<<<HTML
    <nav class="sidebar-nav">
        <h3>Quick Links</h3>
        <ul>
            <li><a href="{$dashboardUrl}">Dashboard</a></li>
            <li><a href="{$usersUrl}">All Users</a></li>
            <li><a href="{$settingsUrl}">Settings</a></li>
        </ul>
    </nav>
    HTML;
    }

    private function renderSidebarViaCapture(): string
    {
        return $this->capture(function () { ?>
<nav class="sidebar-nav">
    <h3>Quick Links</h3>
    <ul>
        <li><a href="<?= $this->e($this->app->url('/dashboard')) ?>">Dashboard</a></li>
        <li><a href="<?= $this->e($this->app->url('/users')) ?>">All Users</a></li>
        <li><a href="<?= $this->e($this->app->url('/settings')) ?>">Settings</a></li>
    </ul>
</nav>
        <?php });
    }
}
