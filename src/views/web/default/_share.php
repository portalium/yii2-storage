<?php

use portalium\storage\Module;
use portalium\storage\models\StorageShare;
use portalium\theme\widgets\Button;
use portalium\theme\widgets\Html;
use portalium\theme\widgets\Modal;
use portalium\user\models\User;
use portalium\workspace\models\Workspace;

/**
 * Share Modal View
 * @var $model \portalium\storage\models\Storage|null - for file share
 * @var $directory \portalium\storage\models\Storage|null - for directory share (type=directory)
 * @var $shareType string - 'file', 'directory', or 'storage'
 * @var $userId int|null - for full storage share
 */

$shareType = $shareType ?? 'file';
$isFile = $shareType === 'file' && isset($model);
$isDirectory = $shareType === 'directory' && isset($directory);
$isFullStorage = $shareType === 'storage' && isset($userId);

// Get current item info
if ($isFile) {
    $itemId = $model->id_storage;
    $itemName = $model->title;
    $itemIcon = 'fa-file';
    $access = ($model->access == 1) ? 'public' : 'private';
    $shares = StorageShare::getShares($model)->all();
} elseif ($isDirectory) {
    $itemId = $directory->id_storage;
    $itemName = $directory->name;
    $itemIcon = 'fa-folder';
    $access = 'private';
    $shares = StorageShare::getShares(null, $directory)->all();
} elseif ($isFullStorage) {
    $itemId = $userId;
    $itemName = Module::t('Full Storage');
    $itemIcon = 'fa-database';
    $access = 'private';
    $shares = StorageShare::getShares(null, null, $userId)->all();
} else {
    Yii::$app->session->setFlash('error', Module::t('File not found!'));
    return;
}

// Get available users and workspaces for sharing
$currentUserId = Yii::$app->user->id;
$users = User::find()
    ->where(['!=', 'id_user', $currentUserId])
    ->andWhere(['status' => 10])
    ->orderBy(['username' => SORT_ASC])
    ->all();

$workspaces = Workspace::find()
    ->orderBy(['name' => SORT_ASC])
    ->all();

// Permission levels for dropdown
$permissionLevels = [
    StorageShare::PERMISSION_VIEW => Module::t('View'),
    StorageShare::PERMISSION_EDIT => Module::t('Edit'),
    StorageShare::PERMISSION_MANAGE => Module::t('Manage'),
];

$accessText = $access === 'public' ? Module::t('Public') : Module::t('Restricted');
$accessDesc = $access === 'public'
    ? Module::t('Anyone with the link can view the content')
    : Module::t('Only people with access can open it using this link');
$accessIcon = $access === 'public' ? 'fa-globe' : 'fa-lock';

// Prepare JavaScript configuration as JSON
$shareConfigJson = json_encode([
    'shareType' => $shareType,
    'itemId' => $itemId,
    'idStorage' => $isFile ? $model->id_storage : null,
    'idDirectory' => $isDirectory ? $directory->id_directory : null,
    'idUserOwner' => $isFullStorage ? $userId : null,
    'urls' => [
        'createShare' => Yii::$app->urlManager->createUrl(['/storage/default/create-share']),
        'getShares' => Yii::$app->urlManager->createUrl(['/storage/default/get-shares']),
        'revokeShare' => Yii::$app->urlManager->createUrl(['/storage/default/revoke-share']),
        'updatePermission' => Yii::$app->urlManager->createUrl(['/storage/default/update-share-permission']),
        'updateAccess' => Yii::$app->urlManager->createUrl(['/storage/default/update-access']),
        'getFileUrl' => Yii::$app->urlManager->createUrl(['/storage/default/get-file']),
    ],
    'messages' => [
        'shareCreated' => Module::t('Share created successfully!'),
        'shareRevoked' => Module::t('Share revoked successfully!'),
        'permissionUpdated' => Module::t('Share permission updated successfully!'),
        'accessChanged' => Module::t('Access type changed: {status}'),
        'error' => Module::t('An error occurred'),
        'selectUser' => Module::t('Select user'),
        'selectWorkspace' => Module::t('Select workspace'),
        'public' => Module::t('Public'),
        'restricted' => Module::t('Restricted'),
        'noSharesYet' => Module::t('No shares yet'),
    ],
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

// Find existing valid link share to pre-populate the copy link button
$existingLinkUrl = '';
foreach ($shares as $share) {
    if ($share->shared_with_type === StorageShare::TYPE_LINK && $share->is_active && !$share->isExpired()) {
        $existingLinkUrl = Yii::$app->urlManager->createAbsoluteUrl(['/storage/default/view-share', 'id' => $share->id_share]);
        break;
    }
}
?>
<script>
window.shareConfig = <?= $shareConfigJson ?>;
window.generatedShareLink = <?= json_encode($existingLinkUrl) ?>;
</script>

<?php

Modal::begin([
    'id' => 'shareModal',
    'title' => Html::tag('i', '', ['class' => 'fa ' . $itemIcon . ' me-2']) . Module::t('Share') . ': ' . Html::encode($itemName),
    'dialogOptions' => ['class' => 'modal-dialog-centered modal-lg'],
    'footer' => Button::widget([
        'label' => Html::tag('i', '', ['class' => 'fa fa-link me-2']) . Module::t('Copy Link'),
        'encodeLabel' => false,
        'options' => [
            'class' => 'btn btn-outline-secondary',
            'id' => 'copyLink',
            'data-copied' => htmlspecialchars(Module::t('Copied!'), ENT_QUOTES, 'UTF-8'),
            'onclick' => 'handleCopyLink(this)'
        ],
    ]) . ' ' . Button::widget([
        'label' => Module::t('Done'),
        'options' => [
            'class' => 'btn btn-success',
            'data-bs-dismiss' => 'modal',
        ],
    ]),
]);
?>

<?php // Share Type Tabs ?>
<?= Html::beginTag('ul', [
    'class' => 'nav nav-tabs mb-4',
    'id' => 'shareTypeTabs',
    'role' => 'tablist'
]) ?>
    <?= Html::tag('li', 
        Html::button(
            Html::tag('i', '', ['class' => 'fa fa-user me-1']) . ' ' . Module::t('User'),
            [
                'class' => 'nav-link active',
                'id' => 'users-tab',
                'data-bs-toggle' => 'tab',
                'data-bs-target' => '#users-panel',
                'type' => 'button',
                'role' => 'tab'
            ]
        ),
        ['class' => 'nav-item', 'role' => 'presentation']
    ) ?>
    <?= Html::tag('li', 
        Html::button(
            Html::tag('i', '', ['class' => 'fa fa-users me-1']) . ' ' . Module::t('Workspace'),
            [
                'class' => 'nav-link',
                'id' => 'workspace-tab',
                'data-bs-toggle' => 'tab',
                'data-bs-target' => '#workspace-panel',
                'type' => 'button',
                'role' => 'tab'
            ]
        ),
        ['class' => 'nav-item', 'role' => 'presentation']
    ) ?>
    <?= Html::tag('li', 
        Html::button(
            Html::tag('i', '', ['class' => 'fa fa-link me-1']) . ' ' . Module::t('Link'),
            [
                'class' => 'nav-link',
                'id' => 'link-tab',
                'data-bs-toggle' => 'tab',
                'data-bs-target' => '#link-panel',
                'type' => 'button',
                'role' => 'tab'
            ]
        ),
        ['class' => 'nav-item', 'role' => 'presentation']
    ) ?>
<?= Html::endTag('ul') ?>

<?= Html::beginTag('div', [
    'class' => 'tab-content',
    'id' => 'shareTypeTabsContent'
]) ?>
    <?php // User Share Tab ?>
    <?= Html::beginTag('div', [
        'class' => 'tab-pane fade show active',
        'id' => 'users-panel',
        'role' => 'tabpanel'
    ]) ?>
        <?= Html::beginTag('div', ['class' => 'row g-3 align-items-end mb-3']) ?>
            <?= Html::beginTag('div', ['class' => 'col-md-5']) ?>
                <?= Html::tag('label', Module::t('Select user'), ['class' => 'form-label fw-semibold']) ?>
                <?= Html::beginTag('select', ['class' => 'form-select', 'id' => 'shareUserSelect']) ?>
                    <?= Html::tag('option', Module::t('Select user') . '...', ['value' => '']) ?>
                    <?php foreach ($users as $user): ?>
                        <?= Html::tag('option', Html::encode($user->username), ['value' => $user->id_user]) ?>
                    <?php endforeach; ?>
                <?= Html::endTag('select') ?>
            <?= Html::endTag('div') ?>
            <?= Html::beginTag('div', ['class' => 'col-md-4']) ?>
                <?= Html::tag('label', Module::t('Permission Level'), ['class' => 'form-label fw-semibold']) ?>
                <?= Html::beginTag('select', ['class' => 'form-select', 'id' => 'shareUserPermission']) ?>
                    <?php foreach ($permissionLevels as $value => $label): ?>
                        <?= Html::tag('option', $label, ['value' => $value]) ?>
                    <?php endforeach; ?>
                <?= Html::endTag('select') ?>
            <?= Html::endTag('div') ?>
            <?= Html::beginTag('div', ['class' => 'col-md-3']) ?>
                <?= Button::widget([
                    'label' => Html::tag('i', '', ['class' => 'fa fa-plus me-1']) . Module::t('Share'),
                    'encodeLabel' => false,
                    'options' => [
                        'class' => 'btn btn-success w-100',
                        'onclick' => 'createShare("user")',
                    ],
                ]) ?>
            <?= Html::endTag('div') ?>
        <?= Html::endTag('div') ?>
    <?= Html::endTag('div') ?>

    <?php // Workspace Share Tab ?>
    <?= Html::beginTag('div', [
        'class' => 'tab-pane fade',
        'id' => 'workspace-panel',
        'role' => 'tabpanel'
    ]) ?>
        <?= Html::beginTag('div', ['class' => 'row g-3 align-items-end mb-3']) ?>
            <?= Html::beginTag('div', ['class' => 'col-md-5']) ?>
                <?= Html::tag('label', Module::t('Select workspace'), ['class' => 'form-label fw-semibold']) ?>
                <?= Html::beginTag('select', ['class' => 'form-select', 'id' => 'shareWorkspaceSelect']) ?>
                    <?= Html::tag('option', Module::t('Select workspace') . '...', ['value' => '']) ?>
                    <?php foreach ($workspaces as $workspace): ?>
                        <?= Html::tag('option', Html::encode($workspace->name), ['value' => $workspace->id_workspace]) ?>
                    <?php endforeach; ?>
                <?= Html::endTag('select') ?>
            <?= Html::endTag('div') ?>
            <?= Html::beginTag('div', ['class' => 'col-md-4']) ?>
                <?= Html::tag('label', Module::t('Permission Level'), ['class' => 'form-label fw-semibold']) ?>
                <?= Html::beginTag('select', ['class' => 'form-select', 'id' => 'shareWorkspacePermission']) ?>
                    <?php foreach ($permissionLevels as $value => $label): ?>
                        <?= Html::tag('option', $label, ['value' => $value]) ?>
                    <?php endforeach; ?>
                <?= Html::endTag('select') ?>
            <?= Html::endTag('div') ?>
            <?= Html::beginTag('div', ['class' => 'col-md-3']) ?>
                <?= Button::widget([
                    'label' => Html::tag('i', '', ['class' => 'fa fa-plus me-1']) . Module::t('Share'),
                    'encodeLabel' => false,
                    'options' => [
                        'class' => 'btn btn-success w-100',
                        'onclick' => 'createShare("workspace")',
                    ],
                ]) ?>
            <?= Html::endTag('div') ?>
        <?= Html::endTag('div') ?>
    <?= Html::endTag('div') ?>

    <?php // Link Share Tab ?>
    <?= Html::beginTag('div', [
        'class' => 'tab-pane fade',
        'id' => 'link-panel',
        'role' => 'tabpanel'
    ]) ?>
        <?= Html::beginTag('div', ['class' => 'row g-3 align-items-end mb-3']) ?>
            <?= Html::beginTag('div', ['class' => 'col-md-5']) ?>
                <?= Html::tag('label', Module::t('Permission Level'), ['class' => 'form-label fw-semibold']) ?>
                <?= Html::beginTag('select', ['class' => 'form-select', 'id' => 'shareLinkPermission']) ?>
                    <?php foreach ($permissionLevels as $value => $label): ?>
                        <?= Html::tag('option', $label, ['value' => $value]) ?>
                    <?php endforeach; ?>
                <?= Html::endTag('select') ?>
            <?= Html::endTag('div') ?>
            <?= Html::beginTag('div', ['class' => 'col-md-4']) ?>
                <?= Html::tag('label', Module::t('Expiration date (optional)'), ['class' => 'form-label fw-semibold']) ?>
                <?= Html::input('datetime-local', '', '', ['class' => 'form-control', 'id' => 'shareLinkExpiry']) ?>
            <?= Html::endTag('div') ?>
            <?= Html::beginTag('div', ['class' => 'col-md-3']) ?>
                <?= Button::widget([
                    'label' => Html::tag('i', '', ['class' => 'fa fa-link me-1']) . Module::t('Generate link'),
                    'encodeLabel' => false,
                    'options' => [
                        'class' => 'btn btn-success w-100',
                        'onclick' => 'createShare("link")',
                    ],
                ]) ?>
            <?= Html::endTag('div') ?>
        <?= Html::endTag('div') ?>
    <?= Html::endTag('div') ?>
<?= Html::endTag('div') ?>

<?= Html::tag('hr', '', ['class' => 'my-4']) ?>

<?php // Current Shares List ?>
<?= Html::tag('h6', Html::tag('i', '', ['class' => 'fa fa-users me-1']) . ' ' . Module::t('People with access'), [
    'class' => 'fw-bold text-secondary mb-3'
]) ?>

<?= Html::beginTag('div', [
    'id' => 'sharesList',
    'class' => 'list-group list-group-flush rounded-3',
    'style' => 'max-height: 250px; overflow-y: auto;'
]) ?>
    <?php if (empty($shares)): ?>
        <?= Html::beginTag('div', [
            'class' => 'text-muted text-center py-3',
            'id' => 'noSharesMessage'
        ]) ?>
            <?= Html::tag('i', '', ['class' => 'fa fa-info-circle me-1']) ?>
            <?= Module::t('No shares yet') ?>
        <?= Html::endTag('div') ?>
    <?php else: ?>
        <?php foreach ($shares as $share): ?>
            <?= Html::beginTag('div', [
                'class' => 'list-group-item d-flex justify-content-between align-items-center py-3',
                'data-share-id' => $share->id_share
            ]) ?>
                <?= Html::beginTag('div', ['class' => 'd-flex align-items-center']) ?>
                    <?php
                    $iconClass = 'fa-user';
                    if ($share->shared_with_type === StorageShare::TYPE_WORKSPACE) {
                        $iconClass = 'fa-users';
                    } elseif ($share->shared_with_type === StorageShare::TYPE_LINK) {
                        $iconClass = 'fa-link';
                    }
                    ?>
                    <?= Html::beginTag('div', [
                        'class' => 'bg-light rounded-circle p-2 me-3',
                        'style' => 'width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;'
                    ]) ?>
                        <?= Html::tag('i', '', ['class' => 'fa ' . $iconClass]) ?>
                    <?= Html::endTag('div') ?>
                    <?= Html::beginTag('div') ?>
                        <?= Html::tag('div', Html::encode($share->getSharedWithName()), ['class' => 'fw-semibold']) ?>
                        <?= Html::tag('small', 
                            $share->getShareTypeLabel() . ' · ' .
                            Html::tag('span', $share->getPermissionLabel(), ['class' => 'permission-badge badge bg-secondary']) .
                            ($share->expires_at ? ' · ' . Html::tag('i', '', ['class' => 'fa fa-clock-o']) . ' ' . Yii::$app->formatter->asDatetime($share->expires_at, 'short') : ''),
                            ['class' => 'text-muted']
                        ) ?>
                    <?= Html::endTag('div') ?>
                <?= Html::endTag('div') ?>
                <?= Html::beginTag('div', ['class' => 'd-flex align-items-center gap-2']) ?>
                    <?php // Permission dropdown ?>
                    <?= Html::beginTag('select', [
                        'class' => 'form-select form-select-sm',
                        'style' => 'width: 100px;',
                        'onchange' => 'updateSharePermission(' . $share->id_share . ', this.value)'
                    ]) ?>
                        <?php foreach ($permissionLevels as $value => $label): ?>
                            <?= Html::tag('option', $label, [
                                'value' => $value,
                                'selected' => $share->permission_level === $value
                            ]) ?>
                        <?php endforeach; ?>
                    <?= Html::endTag('select') ?>
                    <?php // Revoke button ?>
                    <?= Html::button(Html::tag('i', '', ['class' => 'fa fa-times']), [
                        'type' => 'button',
                        'class' => 'btn btn-sm btn-outline-danger',
                        'onclick' => 'revokeShare(' . $share->id_share . ')',
                        'title' => Module::t('Revoke Share')
                    ]) ?>
                <?= Html::endTag('div') ?>
            <?= Html::endTag('div') ?>
        <?php endforeach; ?>
    <?php endif; ?>
<?= Html::endTag('div') ?>

<?php if ($isFile): ?>
    <?= Html::tag('hr', '', ['class' => 'my-4']) ?>

    <?php // General Access (Public/Private) - Only for files ?>
    <?= Html::tag('h6', 
        Html::tag('i', '', ['class' => 'fa fa-globe me-1']) . ' ' . Module::t('General Access'), 
        ['class' => 'fw-bold text-secondary mb-3']
    ) ?>

    <?= Html::beginTag('div', ['class' => 'd-flex align-items-center']) ?>
        <?= Html::beginTag('div', [
            'class' => 'bg-light rounded-circle p-2 me-3',
            'style' => 'width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;'
        ]) ?>
            <?= Html::tag('i', '', [
                'id' => 'access-icon',
                'class' => 'fa ' . $accessIcon
            ]) ?>
        <?= Html::endTag('div') ?>
        <?= Html::beginTag('div', ['class' => 'flex-grow-1']) ?>
            <?= Html::beginTag('div', ['class' => 'dropdown d-inline']) ?>
                <?= Html::button(
                    Html::tag('span', $accessText, [
                        'id' => 'access-text',
                        'data-public' => Module::t('Public'),
                        'data-private' => Module::t('Restricted')
                    ]) . ' ' . Html::tag('i', '', ['class' => 'fa fa-caret-down ms-1']),
                    [
                        'class' => 'btn btn-light border-0 p-0 text-start fw-semibold',
                        'type' => 'button',
                        'id' => 'accessDropdownBtn',
                        'data-bs-toggle' => 'dropdown',
                        'aria-expanded' => 'false',
                        'style' => 'box-shadow: none;'
                    ]
                ) ?>
                <?= Html::beginTag('ul', [
                    'class' => 'dropdown-menu',
                    'aria-labelledby' => 'accessDropdownBtn'
                ]) ?>
                    <?= Html::tag('li', 
                        Html::a(
                            Html::tag('i', '', ['class' => 'fa fa-lock me-2']) . ' ' . Module::t('Restricted'),
                            '#',
                            ['class' => 'dropdown-item', 'onclick' => "setAccessLevel('private'); return false;"]
                        )
                    ) ?>
                    <?= Html::tag('li', 
                        Html::a(
                            Html::tag('i', '', ['class' => 'fa fa-globe me-2']) . ' ' . Module::t('Public'),
                            '#',
                            ['class' => 'dropdown-item', 'onclick' => "setAccessLevel('public'); return false;"]
                        )
                    ) ?>
                <?= Html::endTag('ul') ?>
            <?= Html::endTag('div') ?>
            <?= Html::tag('small', $accessDesc, [
                'id' => 'access-desc',
                'class' => 'text-muted d-block',
                'data-public' => Module::t('Anyone with the link can view the content'),
                'data-private' => Module::t('Only people with access can open it using this link')
            ]) ?>
        <?= Html::endTag('div') ?>
    <?= Html::endTag('div') ?>
<?php endif; ?>

<?php // Toast notification ?>
<?= Html::beginTag('div', [
    'id' => 'shareToast',
    'class' => 'toast align-items-center text-bg-success border-0 shadow-lg position-fixed',
    'role' => 'alert',
    'aria-live' => 'assertive',
    'aria-atomic' => 'true',
    'data-bs-delay' => '2500',
    'style' => 'bottom: 20px; right: 20px; z-index: 9999;',
]) ?>
    <?= Html::beginTag('div', ['class' => 'd-flex']) ?>
        <?= Html::tag('div', '', ['class' => 'toast-body', 'id' => 'shareToastBody']) ?>
        <?= Html::button('', [
            'type' => 'button',
            'class' => 'btn-close btn-close-white me-2 m-auto',
            'data-bs-dismiss' => 'toast',
            'aria-label' => Module::t('Close'),
        ]) ?>
    <?= Html::endTag('div') ?>
<?= Html::endTag('div') ?>

<?php Modal::end(); ?>