<?php

namespace Templates;

use PiedWeb\Splates\Template\Attribute\Inject;
use PiedWeb\Splates\Template\Attribute\TemplateData;
use PiedWeb\Splates\Template\TemplateAbstract;

/**
 * Table row component for user list.
 * Demonstrates small, focused component pattern.
 */
class UserRow extends TemplateAbstract
{
    #[Inject]
    public AppService $app;

    public function __construct(
        #[TemplateData]
        public User $user,
    ) {
    }

    public function __invoke(): void
    { ?>
<tr>
    <td>
        <a href="<?= $this->e($this->app->url('/users/' . $this->user->id)) ?>">
            <span class="avatar-small"><?= $this->e($this->user->getInitials()) ?></span>
            <?= $this->e($this->user->name) ?>
        </a>
    </td>
    <td><?= $this->e($this->user->email) ?></td>
    <td>
        <span class="badge badge-<?= $this->e($this->user->role) ?>">
            <?= $this->e(ucfirst($this->user->role)) ?>
        </span>
    </td>
    <td>
        <a href="<?= $this->e($this->app->url('/users/' . $this->user->id . '/edit')) ?>" class="btn btn-sm">
            Edit
        </a>
    </td>
</tr>
<?php }
    }
