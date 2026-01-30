<?php

use portalium\storage\Module;
use portalium\storage\models\StorageShare;
use portalium\theme\widgets\Html;

/**
 * Shares List Partial View
 * @var $shares array - array of StorageShare models
 */

// Permission levels for dropdown
$permissionLevels = [
    StorageShare::PERMISSION_VIEW => Module::t('View'),
    StorageShare::PERMISSION_EDIT => Module::t('Edit'),
    StorageShare::PERMISSION_MANAGE => Module::t('Manage'),
];

?>

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
