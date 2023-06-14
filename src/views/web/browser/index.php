<?php

use portalium\bootstrap5\Html;
use portalium\storage\Module;
use portalium\theme\widgets\Modal;
use portalium\theme\widgets\Panel;
use portalium\widgets\Pjax;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\ListView;
?>

<?php 
    $path = Url::base() . '/'. Yii::$app->setting->getValue('storage::path') .'/';
?>
<?php 
    echo $this->render('_form', [
        'model' => $model,
        'isPicker' => $isPicker,
        'widgetName' => $widgetName,
    ]);
    

    foreach ($dataProvider->getModels() as $modelDataProvider) {
        echo $this->render('_form', [
            'model' => $modelDataProvider,
            'isPicker' => $isPicker,
            'widgetName' => $widgetName,
        ]);
    }
?>
<?php 
    if($isPicker)
    {    
        Modal::begin([
            'id' => 'file-picker-modal' . $widgetName,
            'size' => Modal::SIZE_LARGE,
            'title' =>  Html::button(Module::t(''), ['class' => 'fa fa-plus btn btn-success', 'style' => 'float:right;', 'id' => 'file-picker-add-button' . $widgetName]).
                        Html::tag('button', '
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        ', ['id' => 'file-picker-add-spinner' . $widgetName, 'class' => 'btn btn-success', 'role' => 'status', 'aria-hidden' => 'true', 'style' => 'display:none;']),
            'footer' => Html::button(Module::t('Close'), ['class' => 'btn btn-warning', 'data-bs-toggle' => 'modal']) .
                        Html::button(Module::t('Select'), ['class' => 'btn btn-success', 'id' => 'file-picker-select' . $widgetName, 'style' => 'float:right; margin-right:10px;', "data-bs-toggle"=>"modal", "data-bs-dismiss"=>"modal"]),
            'closeButton' => false
            ]);
    }
?>



<?php 
    if(!$isPicker)
    {    
        Panel::begin([
            'id' => 'file-form-panel',
            'title' => Module::t('Create'),
            'actions' => [
                'header' => [
                    Html::button(Module::t(''), ['class' => 'btn btn-secondary fa fa-plus', 'data-bs-toggle' => 'modal', 'href' => '#file-form-modal']),
                ],
            ]    
        ]);
    }
?>
<?php Pjax::begin(['id' => 'storage-list-pjax' . ($isPicker ? $widgetName : '')]); ?>
    <?= ListView::widget([
            'dataProvider' => $dataProvider,
            'itemView' => '_file',
            'viewParams' => [
                'view' => 1,
                'attributes' => $attributes,
                'isJson' => $isJson,
                'widgetName' => $widgetName,
                'isPicker' => $isPicker,
            ],
            'options' => [
                'tag' => 'div',
                'class' => 'row',
                'style' => 'overflow-y: auto; height:450px;',
            ],
            'itemOptions' => 
            function ($model, $key, $index, $widget) use ($attributes, $isJson, $widgetName) {
                if (isset($attributes)) {
                    if (is_array($attributes)) {
                        if (in_array('id_storage', $attributes)) {
                        }else{
                            $attributes[] = 'id_storage';
                        }
                    }
                }
                return [
                    'tag' => 'div',
                    'class' => 'col-lg-3 col-sm-4 col-md-3',
                    'data' => ($isJson == 1 ) ? json_encode($model->getAttributes($attributes)) : $model->getAttributes($attributes)[$attributes[0]],
                    //'onclick' => 'selectItem(this, "' . '")',
                ];
            },
            'summary' => false,
            'layout' => '{items}<div class="clearfix"></div>',
            
        ]); 
    ?>
<?php Pjax::end(); ?>

<?php 
    if(!$isPicker)
        Panel::end(); 
?>

<?php 
    if($isPicker)
        Modal::end();
?>

<?php
if($isPicker)
{
    Modal::begin([
        'id' => 'storage-show-file-modal' . $widgetName,
        'size' => Modal::SIZE_DEFAULT,
        'footer' => Html::button(Module::t('Close'), ['class' => 'btn btn-warning', 'data-bs-toggle' => 'modal']),
        'closeButton' => false,
    ]);
        echo Html::img('', ['class' => 'img-thumbnail', 'style' => 'width:100%;', 'id' => 'storage-show-file' . $widgetName]);
    Modal::end();


    echo Html::beginTag('div', ['class' => 'd-flex']);
        echo Html::button(Module::t('Select File'), ['class' => 'btn btn-primary col', 'style'=>'max-width: 130px;', 'data-bs-toggle' => 'modal', 'href' => '#file-picker-modal' . $widgetName]);
        echo Html::beginTag('div', ['class' => 'col', 'id' => 'file-picker-input-check-selected' . $widgetName, 'style' => 'display:none;']);
        //echo Html::tag('span', '', ['class' => 'fa fa-check', 'style' => 'color:green; font-size:24px; margin-top:7px;']);
            echo Html::beginTag('div', ['class' => 'row', 'style' => 'width: 75px;']);
                echo Html::tag('div', '', ['class' => 'col-6 fa fa-check', 'style' => 'color:green; font-size:24px; margin-top:7px;']);
                echo Html::tag('a', Module::t('Show'), ['class' => 'col-6', 'style' => 'margin-top:7px;', 'id' => 'file-picker-input-check-selected-name' . $widgetName, 'data-bs-toggle' => 'modal', 'href' => '#storage-show-file-modal' . $widgetName]);
            echo Html::endTag('div');
        echo Html::endTag('div');
    echo Html::endTag('div');

$this->registerJs(
    'var storagePath = "' . Yii::$app->setting->getValue('storage::path') . '";',
    View::POS_HEAD
);
$this->registerJs(
    <<<JS
        selectedValue = [];
        //get all checkedItems[] and search id_storage in data
        try{
            var name = document.getElementById('file-picker-input-image-create' + '$widgetName').getAttribute("src");
            name = name.replace("/" + storagePath + "/", "");
            document.getElementsByName("checkedItems[]").forEach(function(item){
            var data = JSON.parse(item.getAttribute("data"));
            if(data.name == name){
                item.click();
            }
        });
        }
        catch(err){
        }
        
        function selectItem(e, name){
            if(selectedValue.indexOf($(e).attr("data")) == -1){
                    if("$multiple" == "1"){
                        selectedValue.push($(e).attr("data"));
                        document.getElementById("file-picker-input-check-selected" + name).style.display = "block";
                        document.getElementById("storage-show-file" + name).src = JSON.parse($(e).attr("data")).name ? '$path' + JSON.parse($(e).attr("data")).name : '';
                    }else{
                        selectedValue = [$(e).attr("data")];
                        document.getElementById("file-picker-input-check-selected" + name).style.display = "block";
                        document.getElementById("storage-show-file" + name).src = JSON.parse($(e).attr("data")).name ? '$path' + JSON.parse($(e).attr("data")).name : '';

                    }
                    document.getElementById("file-picker-input-" + name).value = selectedValue;
                    
                    
                    updateItemsStatus(name);
            }else{
                selectedValue.splice(selectedValue.indexOf($(e).attr("data")), 1);
                document.getElementById("file-picker-input-" + name).value = selectedValue;
                updateItemsStatus();
                if(selectedValue.length == 0){
                    
                    document.getElementById("file-picker-input-check-selected" + '$widgetName').style.display = "none";
                    document.getElementById("storage-show-file" + name).src = '';
                }
            }
        }

        function updateItemsStatus(name){
            
            if(!Array.isArray(selectedValue)){
                    if(selectedValue == item.getAttribute("data")){
                        item.classList.remove("btn-success");
                        item.classList.remove("fa-check");
                        item.classList.add("btn-danger");
                        item.classList.add("fa-times");
                    }else{
                        item.classList.remove("btn-danger");
                        item.classList.remove("fa-times");
                        item.classList.add("btn-success");
                        item.classList.add("fa-check");
                    }
                    return;
                }
            var pjax = document.getElementById("storage-list-pjax" + name);
            pjax.querySelectorAll("[name='checkedItems[]']").forEach(function(item){
                if(selectedValue.indexOf(item.getAttribute("data")) != -1){
                    item.classList.remove("btn-success");
                    item.classList.remove("fa-check");
                    item.classList.add("btn-danger");
                    item.classList.add("fa-times");
                }else{
                    item.classList.remove("btn-danger");
                    item.classList.remove("fa-times");
                    item.classList.add("btn-success");
                    item.classList.add("fa-check");
                }
            });
        }

        /* document.getElementById("file-picker-add-button" + '$widgetName').addEventListener("click", function(){
            $('#file-form-modal' + '$widgetName').modal('show');
        });  */      
        JS, View::POS_END
    ); 

    $this->registerJs(
        "
        $(document).ready(function () {
            function checkFilePickerInput() {
                var input = $('#file-picker-input-' + '$widgetName');
                if (input.val() == undefined || input.val() == '' || input.val() == '[]' || input.val() == '0') {
                    document.getElementById(\"file-picker-input-check-selected\" + '$widgetName').style.display = \"none\";
                    document.getElementById(\"storage-show-file\" + '$widgetName').src = '';
                }else{
                    document.getElementById(\"file-picker-input-check-selected\" + '$widgetName').style.display = \"block\";
                    document.getElementById(\"storage-show-file\" + '$widgetName').src = '$path' + JSON.parse(input.val()).name;
                    //find storage-list-pjaxapp-logo-square in checkedItems[]
                    document.getElementById(\"storage-list-pjax\" + '$widgetName').querySelectorAll(\"[name='checkedItems[]']\").forEach(function(item){
                        if(item.getAttribute(\"data\") == input.val()){
                            item.classList.remove(\"btn-success\");
                            item.classList.remove(\"fa-check\");
                            item.classList.add(\"btn-danger\");
                            item.classList.add(\"fa-times\");
                        }else{
                            item.classList.remove(\"btn-danger\");
                            item.classList.remove(\"fa-times\");
                            item.classList.add(\"btn-success\");
                            item.classList.add(\"fa-check\");
                        }
                    });
                }
            }
            checkFilePickerInput();

            
            $('#file-picker-select' + '$widgetName').click(function () {
                $('#file-picker-modal' + '$widgetName').modal('hide');
                
            });
        });
        "
    );
}
?>

