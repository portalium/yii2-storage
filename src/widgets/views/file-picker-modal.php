<?php
use yii\helpers\Url;
use yii\web\View;
use portalium\widgets\Pjax;
use yii\widgets\ListView;
use portalium\storage\Module;
use portalium\theme\widgets\Html;
use portalium\theme\widgets\Modal;
use portalium\storage\models\Storage;


$path = Url::base() . '/'. Yii::$app->setting->getValue('storage::path') .'/';
Modal::begin([
    'id' => 'file-picker-modal' . $name,
    'size' => Modal::SIZE_LARGE,
    'title' =>  Html::button(Module::t(''), ['class' => 'fa fa-plus btn btn-success', 'style' => 'float:right;', 'id' => 'file-picker-add-button' . $name]).
                Html::tag('button', '
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                ', ['id' => 'file-picker-add-spinner' . $name, 'class' => 'btn btn-success', 'role' => 'status', 'aria-hidden' => 'true', 'style' => 'display:none;']),
                
    'footer' => Html::button(Module::t('Close'), ['class' => 'btn btn-warning', 'data-bs-dismiss' => 'modal']) .
                Html::button(Module::t('Select'), ['class' => 'btn btn-success', 'id' => 'file-picker-select' . $name, 'style' => 'float:right; margin-right:10px;']),
    'closeButton' => false
    ]);

    Pjax::begin(['id' => 'file-picker-pjax' . $name, 'history' => false, 'timeout' => false]);
        echo ListView::widget([
            'dataProvider' => $files,
            'itemView' => '_file',
            'viewParams' => [
                'view' => 1,
                'returnAttribute' => $returnAttribute,
                'json' => $json,
                'widgetName' => $name
            ],
            'options' => [
                'tag' => 'div',
                'class' => 'row',
                'style' => 'overflow-y: auto; height:450px;',
            ],
            'itemOptions' => 
            function ($model, $key, $index, $widget) use ($returnAttribute, $json, $name) {
                if (isset($returnAttribute)) {
                    if (is_array($returnAttribute)) {
                        if (in_array('id_storage', $returnAttribute)) {
                        }else{
                            $returnAttribute[] = 'id_storage';
                        }
                    }
                }
                return [
                    'tag' => 'div',
                    'class' => 'col-lg-3 col-sm-4 col-md-3',
                    'data' => ($json == 1 ) ? json_encode($model->getAttributes($returnAttribute)) : $model->getAttributes($returnAttribute)[$returnAttribute[0]],
                    //'onclick' => 'selectItem(this, "' . $name . '")',
                ];
            },
            'summary' => false,
            'layout' => '{items}<div class="clearfix"></div>',
            
        ]);
    Pjax::end();
Modal::end();


$modals = Modal::begin([
    'id' => 'file-update-modal' . $name,
    'size' => Modal::SIZE_DEFAULT,
    'footer' => Html::button(Module::t('Close'), ['class' => 'btn btn-warning', 'data-bs-dismiss' => 'modal']) .
                Html::button(Module::t('Create'), ['class' => 'btn btn-success', 'id' => 'update-storage' . $name]).
                Html::tag('button', '
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                ', ['id' => 'update-storage-spinner' . $name, 'class' => 'btn btn-primary', 'role' => 'status', 'aria-hidden' => 'true', 'style' => 'display:none;']),
    'closeButton' => false,
]);
Pjax::begin(['id' => 'file-update-pjax' . $name, 'history' => false, 'timeout' => false]);
$id_storage = ($storageModel != null && $storageModel->id_storage != '') ? $storageModel->id_storage : "null";
$this->registerJs('id_storage = '.$id_storage.';', View::POS_END);
echo $this->render('./_formModal', [
    'model' => ($storageModel != null) ? $storageModel : new Storage(),
    'widgetName' => $name,
    ]);
Pjax::end();
Modal::end();
Modal::begin([
    'id' => 'storage-show-file-modal' . $name,
    'size' => Modal::SIZE_DEFAULT,
    'footer' => Html::button(Module::t('Close'), ['class' => 'btn btn-warning', 'data-bs-dismiss' => 'modal']),
    'closeButton' => false,
]);
    echo Html::img('', ['class' => 'img-thumbnail', 'style' => 'width:100%;', 'id' => 'storage-show-file' . $name]);
Modal::end();
echo Html::beginTag('div', ['class' => 'd-flex']);
echo Html::button(Module::t('Select File'), ['class' => 'btn btn-primary col', 'style'=>'max-width: 130px;' . 'display: ' . ($visible ? 'block' : 'none'), 'data-bs-toggle' => 'modal', 'data-bs-target' => '#file-picker-modal' . $name]);

echo Html::beginTag('div', ['class' => 'col', 'id' => 'file-picker-input-check-selected' . $name, 'style' => 'display:none;']);
//echo Html::tag('span', '', ['class' => 'fa fa-check', 'style' => 'color:green; font-size:24px; margin-top:7px;']);
echo Html::beginTag('div', ['class' => 'row', 'style' => 'width: 75px;']);
echo Html::tag('div', '', ['class' => 'col-6 fa fa-check', 'style' => 'color:green; font-size:24px; margin-top:7px;']);
echo Html::tag('a', Module::t('Show'), ['class' => 'col-6', 'style' => 'margin-top:7px;', 'id' => 'file-picker-input-check-selected-name' . $name, 'data-bs-toggle' => 'modal', 'data-bs-target' => '#storage-show-file-modal' . $name]);
echo Html::endTag('div');
echo Html::endTag('div');
echo Html::endTag('div');

$this->registerJs(
    <<<JS
        selectedValue = [];
        //get all checkedItems[] and search id_storage in data
        try{
            
            var name = document.getElementById('file-picker-input-image-create' + '$name').getAttribute("src");
            name = name.replace("/data/", "");
            document.getElementsByName("checkedItems[]").forEach(function(item){
            var data = JSON.parse(item.getAttribute("data"));
            if(data.name == name){
                //click item
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

        document.getElementById("file-picker-add-button" + '$name').addEventListener("click", function(){
            //reload pjax
            $('#file-picker-add-spinner' + '$name').show();
            $('#file-picker-add-button' + '$name').hide();
            $.pjax.reload({container: "#file-update-pjax" + '$name', url: "?id_storage=null"}).done(function(){
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
        JS, View::POS_END
    ); 

    $this->registerJs(
        "
        $(document).ready(function () {
            function checkFilePickerInput() {
                var input = $('#file-picker-input-' + '$name');
                if (input.val() == undefined || input.val() == '' || input.val() == '[]' || input.val() == '0') {
                    document.getElementById(\"file-picker-input-check-selected\" + '$name').style.display = \"none\";
                    document.getElementById(\"storage-show-file\" + '$name').src = '';
                }else{
                    document.getElementById(\"file-picker-input-check-selected\" + '$name').style.display = \"block\";
                    document.getElementById(\"storage-show-file\" + '$name').src = '$path' + JSON.parse(input.val()).name;
                    //find file-picker-pjaxapp-logo-square in checkedItems[]
                    document.getElementById(\"file-picker-pjax\" + '$name').querySelectorAll(\"[name='checkedItems[]']\").forEach(function(item){
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

            $('#update-storage' + '$name').click(function () {
                var myFormData = new FormData();
                myFormData.append('title', $('#storage-title' + '$name').val());
                myFormData.append('file', document.getElementById('storage-file' + '$name').files[0]);
                myFormData.append('id_storage', id_storage);
                myFormData.append('" . Yii::$app->request->csrfParam . "', '" . Yii::$app->request->getCsrfToken() . "');
                $('#update-storage-spinner' + '$name').show();
                $('#update-storage' + '$name').hide();
                $.ajax({
                    url: '/storage/default/create',
                    type: 'POST',
                    data: myFormData,
                    contentType: false,
                    processData: false,
                    success: function (data) {
                        $.pjax.reload({container: '#file-picker-pjax' + '$name'}).done(function(){
                            
                            $('#file-update-modal' + '$name').modal('hide');
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

            $('#file-picker-modal' + '$name').on('show.bs.modal', function () {

                setTimeout(function(){
                }, 100);
            });
        });
        "
    );
    //LightBoxAsset::register($this);