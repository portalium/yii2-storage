<?php

use portalium\storage\Module;
use portalium\storage\models\StorageShare;
use portalium\theme\widgets\Panel;
use portalium\theme\widgets\Html;
use portalium\theme\widgets\Nav;
use yii\helpers\Url;

/**
 * My Shares View
 * @var $this yii\web\View
 * @var $fileShares array - Array of file shares
 * @var $directoryShares array - Array of directory shares
 * @var $fullStorageShares array - Array of full storage shares
 */

$this->title = Module::t('My Shares');
$this->params['breadcrumbs'][] = ['label' => Module::t('Storage'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$totalShares = count($fileShares) + count($directoryShares) + count($fullStorageShares);

?>

<?php Panel::begin([
    'title' => Html::tag('i', '', ['class' => 'fa fa-share me-2']) . $this->title,
    'icon' => 'share',
]); ?>

<?php if ($totalShares === 0): ?>
    <?= Html::beginTag('div', ['class' => 'alert alert-info text-center']) ?>
        <?= Html::tag('i', '', ['class' => 'fa fa-info-circle fa-3x mb-3']) ?>
        <?= Html::tag('h5', Module::t('You haven\'t shared anything yet'), ['class' => 'mb-2']) ?>
        <?= Html::tag('p', Module::t('Share files or folders with others to collaborate efficiently.'), ['class' => 'text-muted mb-0']) ?>
    <?= Html::endTag('div') ?>
<?php else: ?>
    <?php // Statistics Cards ?>
    <?= Html::beginTag('div', ['class' => 'row mb-4']) ?>
        <?= Html::beginTag('div', ['class' => 'col-md-3 col-6']) ?>
            <?= Html::beginTag('div', ['class' => 'card border-0 shadow-sm']) ?>
                <?= Html::beginTag('div', ['class' => 'card-body text-center']) ?>
                    <?= Html::tag('h3', $totalShares, ['class' => 'mb-0 text-primary']) ?>
                    <?= Html::tag('small', Module::t('Total Shares'), ['class' => 'text-muted']) ?>
                <?= Html::endTag('div') ?>
            <?= Html::endTag('div') ?>
        <?= Html::endTag('div') ?>
        <?= Html::beginTag('div', ['class' => 'col-md-3 col-6']) ?>
            <?= Html::beginTag('div', ['class' => 'card border-0 shadow-sm']) ?>
                <?= Html::beginTag('div', ['class' => 'card-body text-center']) ?>
                    <?= Html::tag('h3', count($fileShares), ['class' => 'mb-0 text-success']) ?>
                    <?= Html::tag('small', Module::t('Files'), ['class' => 'text-muted']) ?>
                <?= Html::endTag('div') ?>
            <?= Html::endTag('div') ?>
        <?= Html::endTag('div') ?>
        <?= Html::beginTag('div', ['class' => 'col-md-3 col-6']) ?>
            <?= Html::beginTag('div', ['class' => 'card border-0 shadow-sm']) ?>
                <?= Html::beginTag('div', ['class' => 'card-body text-center']) ?>
                    <?= Html::tag('h3', count($directoryShares), ['class' => 'mb-0 text-warning']) ?>
                    <?= Html::tag('small', Module::t('Folders'), ['class' => 'text-muted']) ?>
                <?= Html::endTag('div') ?>
            <?= Html::endTag('div') ?>
        <?= Html::endTag('div') ?>
        <?= Html::beginTag('div', ['class' => 'col-md-3 col-6']) ?>
            <?= Html::beginTag('div', ['class' => 'card border-0 shadow-sm']) ?>
                <?= Html::beginTag('div', ['class' => 'card-body text-center']) ?>
                    <?= Html::tag('h3', count($fullStorageShares), ['class' => 'mb-0 text-info']) ?>
                    <?= Html::tag('small', Module::t('Full Storage'), ['class' => 'text-muted']) ?>
                <?= Html::endTag('div') ?>
            <?= Html::endTag('div') ?>
        <?= Html::endTag('div') ?>
    <?= Html::endTag('div') ?>

    <?php // Tabs ?>
    <?= Nav::widget([
        'options' => ['class' => 'nav-tabs mb-4'],
        'items' => [
            [
                'label' => Html::tag('i', '', ['class' => 'fa fa-file me-1']) . ' ' . Module::t('Files') . ' ' . Html::tag('span', count($fileShares), ['class' => 'badge bg-secondary']),
                'url' => '#files-tab',
                'linkOptions' => [
                    'data-bs-toggle' => 'tab',
                ],
                'active' => true,
                'encode' => false,
            ],
            [
                'label' => Html::tag('i', '', ['class' => 'fa fa-folder me-1']) . ' ' . Module::t('Folders') . ' ' . Html::tag('span', count($directoryShares), ['class' => 'badge bg-secondary']),
                'url' => '#folders-tab',
                'linkOptions' => [
                    'data-bs-toggle' => 'tab',
                ],
                'encode' => false,
            ],
            [
                'label' => Html::tag('i', '', ['class' => 'fa fa-database me-1']) . ' ' . Module::t('Full Storage') . ' ' . Html::tag('span', count($fullStorageShares), ['class' => 'badge bg-secondary']),
                'url' => '#storage-tab',
                'linkOptions' => [
                    'data-bs-toggle' => 'tab',
                ],
                'encode' => false,
            ],
        ],
    ]) ?>

    <?php // Tab Content ?>
    <?= Html::beginTag('div', ['class' => 'tab-content']) ?>
        
        <?php // Files Tab ?>
        <?= Html::beginTag('div', ['class' => 'tab-pane fade show active', 'id' => 'files-tab']) ?>
            <?php if (empty($fileShares)): ?>
                <?= Html::tag('p', Module::t('No file shares yet.'), ['class' => 'text-muted text-center py-4']) ?>
            <?php else: ?>
                <?php echo $this->render('_shares-table', ['shares' => $fileShares, 'type' => 'file']); ?>
            <?php endif; ?>
        <?= Html::endTag('div') ?>

        <?php // Folders Tab ?>
        <?= Html::beginTag('div', ['class' => 'tab-pane fade', 'id' => 'folders-tab']) ?>
            <?php if (empty($directoryShares)): ?>
                <?= Html::tag('p', Module::t('No folder shares yet.'), ['class' => 'text-muted text-center py-4']) ?>
            <?php else: ?>
                <?php echo $this->render('_shares-table', ['shares' => $directoryShares, 'type' => 'directory']); ?>
            <?php endif; ?>
        <?= Html::endTag('div') ?>

        <?php // Full Storage Tab ?>
        <?= Html::beginTag('div', ['class' => 'tab-pane fade', 'id' => 'storage-tab']) ?>
            <?php if (empty($fullStorageShares)): ?>
                <?= Html::tag('p', Module::t('No full storage shares yet.'), ['class' => 'text-muted text-center py-4']) ?>
            <?php else: ?>
                <?php echo $this->render('_shares-table', ['shares' => $fullStorageShares, 'type' => 'storage']); ?>
            <?php endif; ?>
        <?= Html::endTag('div') ?>

    <?= Html::endTag('div') ?>
<?php endif; ?>

<?php Panel::end(); ?>
