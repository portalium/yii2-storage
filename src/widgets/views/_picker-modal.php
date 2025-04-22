<?php

use portalium\storage\Module;
use portalium\theme\widgets\Html;
use portalium\theme\widgets\Modal;

Modal::begin([
    'title' => Module::t('Select File'),
    'id' => 'file-picker-modal',
    'size' => Modal::SIZE_LARGE,
    'titleOptions' => [
        'style' => 'margin-left: 0px !important;'
    ],
    'footer' => Html::button(Module::t('Close'), [
        'class' => 'btn btn-default',
        'data-bs-dismiss' => 'modal',
    ]) . Html::button(Module::t('Select'), [
        'class' => 'btn btn-primary',
        'onclick' => 'saveSelect()',
    ]),
]);

echo $this->render('@portalium/storage/views/web/default/index', [
    'dataProvider' => $dataProvider,
]);
Modal::end();


