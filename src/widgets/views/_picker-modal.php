<?php

use portalium\storage\Module;
use portalium\theme\widgets\Html;
use portalium\theme\widgets\Modal;

/* @var $dataProvider yii\data\ActiveDataProvider */

Modal::begin([
    'title' => Module::t('Select File'),
    'id' => 'file-picker-modal',
    'size' => Modal::SIZE_EXTRA_LARGE,
    'closeButton' => false,
    'footer' => Html::button(Module::t('Close'), [
            'class' => 'btn btn-danger',
            'data-bs-dismiss' => 'modal',
        ]) . Html::button(Module::t('Select'), [
            'class' => 'btn btn-success',
            'onclick' => 'saveSelect()',
            'disabled' => true,
            'id' => 'btn-select-file'
        ]),
    'dialogOptions' => ['class' => 'modal-dialog-centered']

]); ?>


<?php echo $this->render('@portalium/storage/views/web/default/index', [
    'dataProvider' => $dataProvider,
    'isPicker' => true
]);
Modal::end(); ?>

