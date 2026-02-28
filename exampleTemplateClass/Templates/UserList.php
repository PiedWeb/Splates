<?php

namespace Templates;

use PiedWeb\Splates\Template\Attribute\Inject;
use PiedWeb\Splates\Template\Attribute\TemplateData;
use PiedWeb\Splates\Template\TemplateAbstract;

/**
 * User list page demonstrating:
 * - Array iteration in templates
 * - Component composition (UserRow)
 * - Conditional empty state
 * - Global service injection via #[Inject]
 */
class UserList extends TemplateAbstract
{
    #[Inject]
    public AppService $app;

    /**
     * @param User[] $users
     */
    public function __construct(
        #[TemplateData]
        public array $users,
        #[TemplateData]
        public string $title = 'Users',
    ) {
    }

    public function __invoke(): void
    {
        echo $this->render(new Layout(
            title: $this->title,
            header: $this->slot(function () { ?>
                <h1><?= $this->e($this->title) ?></h1>
                <p><?= count($this->users) ?> user(s) found</p>
            <?php }),
            content: fn () => $this->renderContent(),
        ));
    }

    private function renderContent(): string
    {
        return $this->capture(function () {
            if (empty($this->users)): ?>
                <div class="empty-state">
                    <p>No users found.</p>
                    <a href="<?= $this->e($this->app->url('/users/new')) ?>" class="btn">
                        Create First User
                    </a>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->users as $user): ?>
                            <?= $this->render(new UserRow(user: $user)) ?>
                        <?php endforeach ?>
                    </tbody>
                </table>
            <?php endif;
        });
    }
}
