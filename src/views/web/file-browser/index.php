<?php

use yii\helpers\Url;
use yii\web\View;
use portalium\widgets\Pjax;
use portalium\widgets\ListView;
use portalium\storage\Module;
use portalium\theme\widgets\Html;
use portalium\theme\widgets\Modal;
use portalium\storage\models\Storage;
use portalium\theme\widgets\Panel;


$this->registerCss(
    <<<CSS
    .storage-item {
        cursor: pointer;
        padding: 10px;
    }
    .storage-item:hover {
        background-color: #f5f5f5;
    }
    
    .storage-item.selected-item {
        background-color: #78D56E;
    }
    CSS
);

$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->csrfToken;

$path = Url::base() . '/' . Yii::$app->setting->getValue('storage::path') . '/';
$variablePrefix = str_replace('-', '_', $name);
$variablePrefix = str_replace(' ', '_', $variablePrefix);
$variablePrefix = str_replace('.', '_', $variablePrefix);


if ($isPicker) {
    $attributesJson = json_encode($attributes);
    $fileExtensionsJson = $fileExtensions ? json_encode($fileExtensions) : json_encode([]);
    Modal::begin([
        'id' => 'file-picker-modal' . $name,
        'size' => Modal::SIZE_LARGE,
        'title' =>  Html::button(Module::t(''), ['class' => 'fa fa-plus btn btn-success', 'style' => 'float:right;', 'id' => 'file-picker-add-button' . $name]) .
            Html::tag('button', '
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                ', ['id' => 'file-picker-add-spinner' . $name, 'class' => 'btn btn-success', 'role' => 'status', 'aria-hidden' => 'true', 'style' => 'display:none;']),

        'footer' => Html::button(Module::t('Close'), ['class' => 'btn btn-warning', 'data-bs-dismiss' => 'modal']) .
            Html::button(Module::t('Select'), ['class' => 'btn btn-success', 'id' => 'file-picker-select' . $name, 'style' => 'float:right; margin-right:10px;']),
        'closeButton' => false
    ]);
} else {
    Panel::begin([
        'id' => 'file-picker-panel' . $name,
        'actions' => [
            'header' => [
                Html::button(Module::t(''), ['class' => 'fa fa-plus btn btn-success', 'style' => 'float:right;', 'id' => 'file-picker-add-button' . $name]) .
                    Html::tag('button', '
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                ', ['id' => 'file-picker-add-spinner' . $name, 'class' => 'btn btn-success', 'role' => 'status', 'aria-hidden' => 'true', 'style' => 'display:none;']),
            ]
        ]
    ]);
}

Pjax::begin(['id' => 'file-picker-pjax' . $name, 'history' => false, 'timeout' => false]);

$viewParams = $isPicker ? [
    'view' => 1,
    'attributes' => $attributes,
    'isJson' => $isJson,
    'widgetName' => $name,
    'multiple' => $multiple,
    'callbackName' => $callbackName,
    'isPicker' => $isPicker,
    'fileExtensions' => $fileExtensions,
] : [
    'view' => 1,
    'isJson' => $isJson,
    'widgetName' => $name,
    'isPicker' => $isPicker,
    'fileExtensions' => $fileExtensions,
];
echo ListView::widget([
    'dataProvider' => $dataProvider,
    'itemView' => '_file',
    'viewParams' => $viewParams,
    'options' => [
        'tag' => 'div',
        'class' => 'row',
        'style' => 'overflow-y: auto; height: calc(100vh - 370px);',
    ],
    'itemOptions' => $isPicker ?
        function ($model, $key, $index, $widget) use ($attributes, $isJson, $name) {
            if (isset($attributes)) {
                if (is_array($attributes)) {
                    if (in_array('id_storage', $attributes)) {
                    } else {
                        $attributes[] = 'id_storage';
                    }
                }
            }
            return [
                'tag' => 'div',
                'class' => 'col-lg-3 col-sm-4 col-md-3',
                'data' => ($isJson == 1) ? json_encode($model->getAttributes($attributes)) : $model->getAttributes($attributes)[$attributes[0]],
                //'onclick' => 'selectItem(this, "' . $name . '")',
            ];
        } :
        function ($model, $key, $index, $widget) use ($isJson, $name) {
            return
                [
                    'tag' => 'div',
                    'class' => 'col-lg-3 col-sm-4 col-md-3',
                    //'onclick' => 'selectItem(this, "' . $name . '")',
                    'data' => ($isJson == 1) ? json_encode($model->getAttributes(['id_storage'])) : $model->getAttributes(['id_storage'])['id_storage'],
                ];
        },
    'summary' => false,
    'layout' => '{items}<div class="clearfix"></div>',

]);
Pjax::end();
if ($isPicker) {
    Modal::end();
} else {
    Panel::end();
}

$modals = Modal::begin([
    'id' => 'file-update-modal' . $name,
    'size' => Modal::SIZE_DEFAULT,
    'footer' => Html::button(Module::t('Close'), ['class' => 'btn btn-warning', 'data-bs-dismiss' => 'modal']) .
        Html::button(Module::t('Create'), ['class' => 'btn btn-success', 'id' => 'update-storage' . $name]) .
        Html::tag('button', '
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                ', ['id' => 'update-storage-spinner' . $name, 'class' => 'btn btn-primary', 'role' => 'status', 'aria-hidden' => 'true', 'style' => 'display:none;']),
    'closeButton' => false,
]);
Pjax::begin(['id' => 'file-update-pjax' . $name, 'history' => false, 'timeout' => false]);
$id_storage = ($storageModel != null && $storageModel->id_storage != '') ? $storageModel->id_storage : "null";
$this->registerJs('id_storage' . $variablePrefix . ' = ' . $id_storage . ';', View::POS_END);
$this->registerJs('
    if (id_storage' . $variablePrefix . ' != null) {
        document.getElementById("file-picker-input-" + name).value = JSON.stringify({id_storage: id_storage' . $variablePrefix . '});
    }
', View::POS_END);
echo $this->render('./_formModal', [
    'model' => ($storageModel != null) ? $storageModel : new Storage(),
    'widgetName' => $name,
    'isPicker' => $isPicker,
    'isJson' => $isJson,
    'multiple' => $multiple,
    'callbackName' => $callbackName,
    'fileExtensions' => $fileExtensions,
]);
Pjax::end();
Modal::end();

if ($isPicker) {
    Modal::begin([
        'id' => 'storage-show-file-modal' . $name,
        'size' => Modal::SIZE_DEFAULT,
        'footer' => Html::button(Module::t('Close'), ['class' => 'btn btn-warning', 'data-bs-dismiss' => 'modal']),
        'closeButton' => false,
    ]);
    echo Html::img('', ['class' => 'img-thumbnail', 'style' => 'width:100%;', 'id' => 'storage-show-file' . $name]);
    Modal::end();
    echo Html::beginTag('div', ['class' => 'd-flex']);
    echo Html::button(Module::t('Select File'), ['class' => 'btn btn-primary', 'style' => 'max-width: 130px;', 'id' => 'file-picker-button' . $name]);

    echo Html::beginTag('div', ['class' => 'col', 'id' => 'file-picker-input-check-selected' . $name, 'style' => 'display:none;']);
    //echo Html::tag('span', '', ['class' => 'fa fa-check', 'style' => 'color:green; font-size:24px; margin-top:7px;']);
    echo Html::beginTag('div', ['class' => 'row', 'style' => 'width: 75px;']);
    echo Html::tag('div', '', ['class' => 'col-6 fa fa-check', 'style' => 'color:green; font-size:24px; margin-top:7px;']);
    echo Html::tag('a', Module::t('Show'), ['class' => 'col-6', 'style' => 'margin-top:7px;', 'id' => 'file-picker-input-check-selected-name' . $name/* , 'data-bs-toggle' => 'modal', 'data-bs-target' => '#storage-show-file-modal' . $name */]);
    echo Html::endTag('div');
    echo Html::endTag('div');
    echo Html::endTag('div');
}

if ($isPicker && $callbackName != null) {
    $this->registerJs(
        <<<JS
        document.getElementById("file-picker-select" + '$name').addEventListener("click", function(){
            $callbackName(selectedValue);
        });
        JS,
        View::POS_END
    );
}
$this->registerJs(
    <<<JS
    // on click item in file-picker-pjaxapp-logo-square in storage-item class
    $('#file-picker-pjax' + '$name').on('click', '.storage-item', function(){
        console.log("storage-item clicked");
        console.log($(this).attr("data"));
        $(this).toggleClass("selected-item");
    });
    JS,
    View::POS_END
);
if ($isPicker) {
    $this->registerJs(
        <<<JS
        // on click item in file-picker-pjaxapp-logo-square in storage-item class
        $('#file-picker-pjax' + '$name').on('click', '.storage-item', function(){
            console.log("storage-item clicked");
            console.log($(this).attr("data"));
        });


        selectedValue = [];
        
        function selectItem(e, name){
            if(selectedValue.indexOf($(e).attr("data")) == -1){
                    if("$multiple" == "1"){
                        selectedValue.push($(e).attr("data"));
                        document.getElementById("file-picker-input-check-selected" + name).style.display = "block";
                        document.getElementById("storage-show-file" + name).src = '$path' + $(e).attr("img-src");
                    }else{
                        selectedValue = [$(e).attr("data")];
                        document.getElementById("file-picker-input-check-selected" + name).style.display = "block";
                        document.getElementById("storage-show-file" + name).src = '$path' + $(e).attr("img-src");
                    }
                    document.getElementById("file-picker-input-" + name).value = selectedValue;
                    
                    updateItemsStatus(name);
            }else{
                selectedValue.splice(selectedValue.indexOf($(e).attr("data")), 1);
                document.getElementById("file-picker-input-" + name).value = selectedValue;
                updateItemsStatus(name);
                if(selectedValue.length == 0){
                    document.getElementById("file-picker-input-check-selected" + '$name').style.display = "none";
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
            
                var pjax = document.getElementById("file-picker-pjax" + name);

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
        document.getElementById("file-picker-button" + '$name').addEventListener("click", function(){
            document.getElementById("file-picker-button" + '$name').innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            $.pjax.reload({container: '#file-picker-pjax' + '$name', url: '/storage/file-browser/index?payload=' + JSON.stringify(payload$variablePrefix)
                            , timeout: false
                        }).done(function (data) {
                            document.getElementById("file-picker-button" + '$name').innerHTML = 'Select File';
                            $('#file-picker-modal' + '$name').modal('show');
                            id_storage$variablePrefix = null;
                        });
            
        });
        // 'file-picker-input-check-selected-name' . $name
        document.getElementById("file-picker-input-check-selected-name" + '$name').addEventListener("click", function(){
            $('#storage-show-file-modal' + '$name').modal('show');
        });
        JS,
        View::POS_END
    );
}
$this->registerJs(
    <<<JS
    document.getElementById("file-picker-add-button" + '$name').addEventListener("click", function(){
        //reload pjax
        $('#file-picker-add-spinner' + '$name').show();
        $('#file-picker-add-button' + '$name').hide();
        //get active url params

        $.pjax.reload({container: "#file-update-pjax" + '$name', url: "/storage/file-browser/create?id_storage=null"+'&name=' + '$name'}).done(function(){
            //update-storage change name to create
            document.getElementById("update-storage" + '$name').innerHTML = "Create";
            document.getElementById("update-storage" + '$name').classList.remove("btn-primary");
            document.getElementById("update-storage" + '$name').classList.add("btn-success");
            //show modal
            $('#file-update-modal' + '$name').modal('show');
            $('#file-picker-add-spinner' + '$name').hide();
            $('#file-picker-add-button' + '$name').show();
            
        });
    });        
    JS,
    View::POS_END
);


if ($isPicker) {
    $this->registerJs(
        <<<JS
        $(document).ready(function () {
            function setName(id) {
                var name = '';
                $.ajax({
                    url: '/storage/file-browser/get-name?id=' + id,
                    type: 'GET',
                    success: function (data) {
                        checkFilePickerInput(data);
                    },
                    error: function (data) {
                        checkFilePickerInput();
                    }
                });
            }
            function checkFilePickerInput(name = null) {
                var input = $('#file-picker-input-' + '$name');
                if (input.val() == undefined || input.val() == '' || input.val() == '[]' || input.val() == '0') {
                    document.getElementById("file-picker-input-check-selected" + '$name').style.display = "none";
                    document.getElementById("storage-show-file" + '$name').src = '';
                }else{
                    document.getElementById("file-picker-input-check-selected" + '$name').style.display = "block";

                    document.getElementById("storage-show-file" + '$name').src = name ? '$path' + name : '$path' + JSON.parse(input.val()).name;
                    //find file-picker-pjaxapp-logo-square in checkedItems[]
                    document.getElementById("file-picker-pjax" + '$name').querySelectorAll("[name='checkedItems[]']").forEach(function(item){
                        if(item.getAttribute("data") == input.val()){
                            item.classList.remove("btn-success");
                            item.classList.remove("fa-check");
                            item.classList.add("btn-danger");
                            item.classList.add("fa-times");
                            item.click();
                        }else{
                            item.classList.remove("btn-danger");
                            item.classList.remove("fa-times");
                            item.classList.add("btn-success");
                            item.classList.add("fa-check");
                        }
                    });
                }
            }

            if ($('#file-picker-input-' + '$name').val() != undefined && $('#file-picker-input-' + '$name').val() != '' && JSON.parse($('#file-picker-input-' + '$name').val()).name == undefined) {
                if (JSON.parse($('#file-picker-input-' + '$name').val()).id_storage != undefined) {
                    setName(JSON.parse($('#file-picker-input-' + '$name').val()).id_storage);
                }else if(JSON.parse($('#file-picker-input-' + '$name').val()) != undefined) {
                    setName(JSON.parse($('#file-picker-input-' + '$name').val()));
                }
            }else {
                checkFilePickerInput();
            }
            
        });
        JS,
        View::POS_END
    );
}
if ($isPicker) {
    $this->registerJs("
    payload$variablePrefix = {
        attribute: 'id_storage',
        multiple: '$multiple',
        isJson: '$isJson',
        attributes: JSON.parse('$attributesJson'),
        name: '$name',
        callbackName: '$callbackName',
        isPicker: '$isPicker',
        fileExtensions: JSON.parse('$fileExtensionsJson'),
    };
", View::POS_END);
} else {
    $this->registerJs("
        payload$variablePrefix = {
            isJson: '$isJson',
            name: '$name',
            isPicker: '$isPicker',
            fileExtensions: JSON.parse('$fileExtensionsJson'),
        };
    ", View::POS_END);
}
$this->registerJs(
    <<<JS
        $(document).ready(function () {
            $('#update-storage' + '$name').click(function () {
                var myFormData = new FormData();
                myFormData.append('title', $('#storage-title' + '$name').val());
                myFormData.append('file', document.getElementById('storage-file' + '$name').files[0]);
                myFormData.append('id_storage', id_storage$variablePrefix);
                myFormData.append('$csrfParam', '$csrfToken');
                $('#update-storage-spinner' + '$name').show();
                $('#update-storage' + '$name').hide();
                $.ajax({
                    url: id_storage$variablePrefix ? '/storage/file-browser/update?id=' + id_storage$variablePrefix : '/storage/file-browser/create',
                    type: 'POST',
                    data: myFormData,
                    contentType: false,
                    processData: false,
                    success: function (data) {
                        $.pjax.reload({container: '#file-picker-pjax' + '$name', url: '/storage/file-browser/index?payload=' + JSON.stringify(payload$variablePrefix)
                            , timeout: false
                        }).done(function (data) {
                            $('#file-update-modal' + '$name').modal('hide');
                            id_storage$variablePrefix = null;
                        });
                    },
                    error: function (data) {
                        $('#storage-error' + '$name').html(data.responseJSON.message);
                        $('#storage-error-modal' + '$name').modal('show');
                    }
                }).always(function () {
                    $('#update-storage-spinner' + '$name').hide();
                    $('#update-storage' + '$name').show();
                });
            });
            $('#file-picker-select' + '$name').click(function () {
                $('#file-picker-modal' + '$name').modal('hide');
            });
        });
        JS,
);
