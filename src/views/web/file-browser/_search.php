<?php

use portalium\storage\models\Storage;
use yii\helpers\Html;
use portalium\theme\widgets\ActiveForm;
use portalium\storage\Module;
/* @var $this yii\web\View */
/* @var $model portalium\storage\models\StorageSearch */
/* @var $form yii\widgets\ActiveForm */
?>



<?php $form = ActiveForm::begin([
    'action' => [Yii::$app->controller->action->id == 'manage' ? 'manage' : 'index'],
    'id' => 'storage-search-form-' . $name,
    'method' => 'get',
    'options' => [
        'data-pjax' => 1,
        'style' => 'width: 100%;'
    ],

]); ?>

<div class="form-group" style="display: flex; justify-content: flex-end;">
    <?= Html::beginTag('div', ['class' => 'd-flex', 'style' => 'gap: 10px;']); ?> 
    <?= $form->field($model, 'title', [
        'options' => [
            'style' => 'margin-bottom:0px !important; width: 150px; margin-right: 5px;',
            'class' => '',
        ]
    ])->label(false)->textInput([
        'placeholder' => Module::t('Search for file...'),
        'style' => 'width: 150px;',
        'id' => 'storage-search-title-' . $name
    ]) ?>
    <?= Html::button(Module::t(''), [
        'class' => 'fa fa-search btn btn-success',
        'id' => 'storage-search-button-' . $name,
        'style' => 'margin-right: 10px;' 
    ]) ?>
    <?= Html::endTag('div'); ?>
    <?php 
    
    if(isset($manage) && $manage == true){
        echo '<div style="display: flex;justify-content: space-between;">';
        echo $form->field($model, 'id_workspace', ['options' => ['style' => 'margin-bottom:0px !important;width: 150px; margin-right: 10px;', 'id' => 'storage-search-form-workspace-' . $name, 'class' => '']])->dropDownList(Storage::getWorkspaces(), ['prompt' => Module::t('All Workspace'), 'style'=>'width: 150px;', 'id'=>'storage-search-workspace-'.$name])->label(false);
    }
    ?>

        <?php
        $accesses = Storage::getAccesses();

        foreach ($accesses as $key => $value) {
            if ($value === 'Private') {
                $accesses[$key] = 'Restricted';
            }
            if ($value === 'Public') {
                $accesses[$key] = 'Anyone with link';
            }
        }

        echo $form->field($model, 'access', [
            'options' => [
                'style' => 'margin-bottom:0px !important;width: 150px; margin-right: 10px;',
                'id' => 'storage-search-form-access-' . $name,
                'class' => ''
            ]
        ])->dropDownList($accesses, [
            'prompt' => Module::t('All'),
            'style' => 'width: 150px;',
            'id' => 'storage-search-access-' . $name
        ])->label(false);
        ?>

    <?php 
    
    if(isset($manage) && $manage == true){
        echo '</div>';
    }
    ?>
</div>

<?php ActiveForm::end(); ?>



<?php

$this->registerJs(
    "
    $('#storage-search-button-$name').click(function(){
        // 'file-picker-list' + $name
        // $('#file-picker-list$name').hide();
        document.getElementsByName('file-picker-list$name')[0].style.display = 'none';
        // $('#file-picker-spinner$name').
        document.getElementsByName('file-picker-spinner$name')[0].style.display = 'flex';
        let data = {
            'StorageSearch[title]': $('#storage-search-title-$name').val(),
            'StorageSearch[access]': $('#storage-search-access-$name').val(),
            'StorageSearch[id_workspace]': $('#storage-search-workspace-$name').val(),
        };
        $.pjax.reload({container: '#file-picker-pjax$name', data: data, url: '" . ($isPicker ? '/storage/file-browser/index' : ((isset($manage) && $manage == false ) ? '/storage/default/index':'/storage/default/manage' )). "'}).done(function() {
            // $('#file-picker-spinner$name').hide();
            document.getElementsByName('file-picker-spinner$name')[0].style.display = 'none';
        });
    });
    $('#storage-search-form-access-$name').change(function(){
        // $('#file-picker-list$name').hide();
        document.getElementsByName('file-picker-list$name')[0].style.display = 'none';
        // $('#file-picker-spinner$name').
        document.getElementsByName('file-picker-spinner$name')[0].style.display = 'flex';
        let data = {
            'StorageSearch[title]': $('#storage-search-title-$name').val(),
            'StorageSearch[access]': $('#storage-search-access-$name').val(),
            'StorageSearch[id_workspace]': $('#storage-search-workspace-$name').val(),
        };
        $.pjax.reload({container: '#file-picker-pjax$name', data: data, url: '" . ($isPicker ? '/storage/file-browser/index' : ((isset($manage) && $manage == false ) ? '/storage/default/index':'/storage/default/manage')) . "'}).done(function() {
            // $('#file-picker-spinner$name').hide();
            document.getElementsByName('file-picker-spinner$name')[0].style.display = 'none';
        });
    });

    $('#storage-search-form-workspace-$name').change(function(){
        // $('#file-picker-list$name').hide();
        document.getElementsByName('file-picker-list$name')[0].style.display = 'none';
        // $('#file-picker-spinner$name').
        document.getElementsByName('file-picker-spinner$name')[0].style.display = 'flex';
        let data = {
            'StorageSearch[title]': $('#storage-search-title-$name').val(),
            'StorageSearch[access]': $('#storage-search-access-$name').val(),
            'StorageSearch[id_workspace]': $('#storage-search-workspace-$name').val(),
        };
        $.pjax.reload({container: '#file-picker-pjax$name', data: data, url: '" . ($isPicker ? '/storage/file-browser/index' : ((isset($manage) && $manage == false ) ? '/storage/default/index':'/storage/default/manage')) . "'}).done(function() {
            // $('#file-picker-spinner$name').hide();
            document.getElementsByName('file-picker-spinner$name')[0].style.display = 'none';
        });
    });

    // disable enter key form submit
    $('#storage-search-form-$name').on('keypress', function(e) {
        // trigger click on search button
        if (e.which === 13) {
            $('#storage-search-button-$name').trigger('click');
            return false;
        }
    });
"
);
