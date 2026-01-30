<?php

use portalium\storage\Module;
use portalium\storage\models\StorageShare;
use portalium\theme\widgets\Panel;
use portalium\theme\widgets\Html;
use portalium\theme\widgets\GridView;
use yii\helpers\Url;

/**
 * Shared With Me View
 * @var $this yii\web\View
 * @var $shares array - Array of StorageShare models shared with current user
 */

$this->title = Module::t('Shared With Me');
$this->params['breadcrumbs'][] = ['label' => Module::t('Storage'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>

<?php Panel::begin([
    'title' => Html::tag('i', '', ['class' => 'fa fa-share-alt me-2']) . $this->title,
    'icon' => 'share-alt',
]); ?>

<?php if (empty($shares)): ?>
    <?= Html::beginTag('div', ['class' => 'alert alert-info text-center']) ?>
        <?= Html::tag('i', '', ['class' => 'fa fa-info-circle fa-3x mb-3']) ?>
        <?= Html::tag('h5', Module::t('No items shared with you yet'), ['class' => 'mb-2']) ?>
        <?= Html::tag('p', Module::t('When someone shares files or folders with you, they will appear here.'), ['class' => 'text-muted mb-0']) ?>
    <?= Html::endTag('div') ?>
<?php else: ?>
    <?= Html::beginTag('div', ['class' => 'table-responsive']) ?>
        <?= Html::beginTag('table', ['class' => 'table table-hover align-middle']) ?>
            <?= Html::beginTag('thead', ['class' => 'table-light']) ?>
                <?= Html::beginTag('tr') ?>
                    <?= Html::tag('th', Html::tag('i', '', ['class' => 'fa fa-cube me-1']) . Module::t('Type')) ?>
                    <?= Html::tag('th', Html::tag('i', '', ['class' => 'fa fa-file me-1']) . Module::t('Name')) ?>
                    <?= Html::tag('th', Html::tag('i', '', ['class' => 'fa fa-user me-1']) . Module::t('Shared By')) ?>
                    <?= Html::tag('th', Html::tag('i', '', ['class' => 'fa fa-users me-1']) . Module::t('Shared With')) ?>
                    <?= Html::tag('th', Html::tag('i', '', ['class' => 'fa fa-key me-1']) . Module::t('Permission')) ?>
                    <?= Html::tag('th', Html::tag('i', '', ['class' => 'fa fa-calendar me-1']) . Module::t('Shared Date')) ?>
                    <?= Html::tag('th', Html::tag('i', '', ['class' => 'fa fa-clock-o me-1']) . Module::t('Expires')) ?>
                    <?= Html::tag('th', Module::t('Actions'), ['class' => 'text-end']) ?>
                <?= Html::endTag('tr') ?>
            <?= Html::endTag('thead') ?>
            <?= Html::beginTag('tbody') ?>
                <?php foreach ($shares as $share): ?>
                    <?php
                    // Determine share type and details
                    if ($share->id_storage) {
                        $itemType = Module::t('File');
                        $itemIcon = 'fa-file';
                        $itemName = $share->storage ? $share->storage->title : Module::t('Deleted File');
                        $itemUrl = $share->storage ? Url::to(['/storage/default/get-file', 'id' => $share->storage->id_storage]) : '#';
                        $itemExists = $share->storage !== null;
                    } elseif ($share->id_directory) {
                        $itemType = Module::t('Folder');
                        $itemIcon = 'fa-folder';
                        $itemName = $share->directory ? $share->directory->name : Module::t('Deleted Folder');
                        $itemUrl = $share->directory ? Url::to(['/storage/default/index', 'id_directory' => $share->directory->id_directory]) : '#';
                        $itemExists = $share->directory !== null;
                    } elseif ($share->id_user_owner) {
                        $itemType = Module::t('Full Storage');
                        $itemIcon = 'fa-database';
                        $itemName = Module::t('Full Storage of {user}', ['user' => $share->owner ? $share->owner->username : Module::t('Unknown')]);
                        $itemUrl = Url::to(['/storage/default/index']);
                        $itemExists = true;
                    }

                    $sharedBy = $share->owner ? $share->owner->username : Module::t('Unknown');
                    $sharedWith = $share->getSharedWithName();
                    $permission = $share->getPermissionLabel();
                    $sharedDate = Yii::$app->formatter->asDatetime($share->date_create, 'medium');
                    $expiresAt = $share->expires_at ? Yii::$app->formatter->asDatetime($share->expires_at, 'medium') : Module::t('Never');
                    $isExpired = $share->isExpired();
                    ?>
                    <?= Html::beginTag('tr', ['class' => $isExpired ? 'text-muted' : '']) ?>
                        <?= Html::beginTag('td') ?>
                            <?= Html::tag('span', 
                                Html::tag('i', '', ['class' => 'fa ' . $itemIcon . ' me-1']) . $itemType,
                                ['class' => 'badge bg-secondary']
                            ) ?>
                        <?= Html::endTag('td') ?>
                        <?= Html::beginTag('td') ?>
                            <?php if ($itemExists && !$isExpired): ?>
                                <?= Html::a(
                                    Html::tag('i', '', ['class' => 'fa ' . $itemIcon . ' me-1']) . Html::encode($itemName),
                                    $itemUrl,
                                    ['target' => '_blank']
                                ) ?>
                            <?php else: ?>
                                <?= Html::tag('span', 
                                    Html::tag('i', '', ['class' => 'fa ' . $itemIcon . ' me-1']) . Html::encode($itemName),
                                    ['class' => 'text-muted']
                                ) ?>
                            <?php endif; ?>
                            <?php if ($isExpired): ?>
                                <?= Html::tag('span', Module::t('Expired'), ['class' => 'badge bg-danger ms-2']) ?>
                            <?php endif; ?>
                        <?= Html::endTag('td') ?>
                        <?= Html::tag('td', Html::encode($sharedBy)) ?>
                        <?= Html::beginTag('td') ?>
                            <?php if ($share->shared_with_type === StorageShare::TYPE_USER): ?>
                                <?= Html::tag('i', '', ['class' => 'fa fa-user me-1']) ?>
                            <?php elseif ($share->shared_with_type === StorageShare::TYPE_WORKSPACE): ?>
                                <?= Html::tag('i', '', ['class' => 'fa fa-users me-1']) ?>
                            <?php elseif ($share->shared_with_type === StorageShare::TYPE_LINK): ?>
                                <?= Html::tag('i', '', ['class' => 'fa fa-link me-1']) ?>
                            <?php endif; ?>
                            <?= Html::encode($sharedWith) ?>
                        <?= Html::endTag('td') ?>
                        <?= Html::beginTag('td') ?>
                            <?php
                            $badgeClass = 'bg-secondary';
                            if ($permission === Module::t('Manage')) {
                                $badgeClass = 'bg-danger';
                            } elseif ($permission === Module::t('Edit')) {
                                $badgeClass = 'bg-warning';
                            } elseif ($permission === Module::t('View')) {
                                $badgeClass = 'bg-info';
                            }
                            ?>
                            <?= Html::tag('span', $permission, ['class' => 'badge ' . $badgeClass]) ?>
                        <?= Html::endTag('td') ?>
                        <?= Html::tag('td', $sharedDate) ?>
                        <?= Html::beginTag('td') ?>
                            <?php if ($share->expires_at): ?>
                                <?php if ($isExpired): ?>
                                    <?= Html::tag('span', $expiresAt, ['class' => 'text-danger']) ?>
                                <?php else: ?>
                                    <?= $expiresAt ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <?= Html::tag('span', Module::t('Never'), ['class' => 'text-muted']) ?>
                            <?php endif; ?>
                        <?= Html::endTag('td') ?>
                        <?= Html::beginTag('td', ['class' => 'text-end']) ?>
                            <?php if ($itemExists && !$isExpired): ?>
                                <?= Html::a(
                                    '',
                                    $itemUrl,
                                    [
                                        'class' => 'fa fa-external-link btn btn-sm btn-info',
                                        'title' => Module::t('Open'),
                                        'target' => '_blank',
                                    ]
                                ) ?>
                            <?php endif; ?>
                        <?= Html::endTag('td') ?>
                    <?= Html::endTag('tr') ?>
                <?php endforeach; ?>
            <?= Html::endTag('tbody') ?>
        <?= Html::endTag('table') ?>
    <?= Html::endTag('div') ?>

    <?php // Statistics ?>
    <?= Html::beginTag('div', ['class' => 'row mt-4']) ?>
        <?= Html::beginTag('div', ['class' => 'col-md-3 col-6']) ?>
            <?= Html::beginTag('div', ['class' => 'card border-0 shadow-sm']) ?>
                <?= Html::beginTag('div', ['class' => 'card-body text-center']) ?>
                    <?= Html::tag('h3', count($shares), ['class' => 'mb-0 text-primary']) ?>
                    <?= Html::tag('small', Module::t('Total Shares'), ['class' => 'text-muted']) ?>
                <?= Html::endTag('div') ?>
            <?= Html::endTag('div') ?>
        <?= Html::endTag('div') ?>
        <?= Html::beginTag('div', ['class' => 'col-md-3 col-6']) ?>
            <?= Html::beginTag('div', ['class' => 'card border-0 shadow-sm']) ?>
                <?= Html::beginTag('div', ['class' => 'card-body text-center']) ?>
                    <?php
                    $fileCount = 0;
                    foreach ($shares as $share) {
                        if ($share->id_storage) $fileCount++;
                    }
                    ?>
                    <?= Html::tag('h3', $fileCount, ['class' => 'mb-0 text-success']) ?>
                    <?= Html::tag('small', Module::t('Files'), ['class' => 'text-muted']) ?>
                <?= Html::endTag('div') ?>
            <?= Html::endTag('div') ?>
        <?= Html::endTag('div') ?>
        <?= Html::beginTag('div', ['class' => 'col-md-3 col-6']) ?>
            <?= Html::beginTag('div', ['class' => 'card border-0 shadow-sm']) ?>
                <?= Html::beginTag('div', ['class' => 'card-body text-center']) ?>
                    <?php
                    $folderCount = 0;
                    foreach ($shares as $share) {
                        if ($share->id_directory) $folderCount++;
                    }
                    ?>
                    <?= Html::tag('h3', $folderCount, ['class' => 'mb-0 text-warning']) ?>
                    <?= Html::tag('small', Module::t('Folders'), ['class' => 'text-muted']) ?>
                <?= Html::endTag('div') ?>
            <?= Html::endTag('div') ?>
        <?= Html::endTag('div') ?>
        <?= Html::beginTag('div', ['class' => 'col-md-3 col-6']) ?>
            <?= Html::beginTag('div', ['class' => 'card border-0 shadow-sm']) ?>
                <?= Html::beginTag('div', ['class' => 'card-body text-center']) ?>
                    <?php
                    $expiredCount = 0;
                    foreach ($shares as $share) {
                        if ($share->isExpired()) $expiredCount++;
                    }
                    ?>
                    <?= Html::tag('h3', $expiredCount, ['class' => 'mb-0 text-danger']) ?>
                    <?= Html::tag('small', Module::t('Expired'), ['class' => 'text-muted']) ?>
                <?= Html::endTag('div') ?>
            <?= Html::endTag('div') ?>
        <?= Html::endTag('div') ?>
    <?= Html::endTag('div') ?>
<?php endif; ?>

<?php Panel::end(); ?>
