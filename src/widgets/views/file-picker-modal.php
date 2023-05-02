<?php
use portalium\storage\bundles\LightBoxAsset;
use yii\web\View;
use yii\widgets\Pjax;
use yii\widgets\ListView;
use portalium\theme\widgets\ActiveForm;
use portalium\storage\Module;
use portalium\theme\widgets\Html;
use portalium\theme\widgets\Modal;
use portalium\theme\widgets\Panel;
use portalium\storage\models\Storage;



Modal::begin([
    'id' => 'file-picker-modal',
    'size' => Modal::SIZE_LARGE,
    'title' =>  Html::button(Module::t(''), ['class' => 'fa fa-plus btn btn-success', 'style' => 'float:right;', 'id' => 'file-picker-add-button']),
                
    'footer' => Html::button(Module::t('Close'), ['class' => 'btn btn-warning', 'data-bs-dismiss' => 'modal']) .
                Html::button(Module::t('Select'), ['class' => 'btn btn-success', 'id' => 'file-picker-select', 'style' => 'float:right; margin-right:10px;']),
    'closeButton' => false
    ]);

    Pjax::begin(['id' => 'file-picker-pjax']);
        echo ListView::widget([
            'dataProvider' => $files,
            'itemView' => '_file',
            'viewParams' => [
                'view' => 1,
                'returnAttribute' => $returnAttribute,
                'json' => $json,
            ],
            'options' => [
                'tag' => 'div',
                'class' => 'row',
                'style' => 'overflow-y: auto; height:450px;',
            ],
            'itemOptions' => [
                'tag' => 'div',
                'class' => 'col-lg-3 col-sm-4 col-md-3',
            ],
            'summary' => false,
            'layout' => '{items}<div class="clearfix"></div>',
            
        ]);
    Pjax::end();
Modal::end();


$modals = Modal::begin([
    'id' => 'file-update-modal',
    'size' => Modal::SIZE_DEFAULT,
    'footer' => Html::button(Module::t('Close'), ['class' => 'btn btn-warning', 'data-bs-dismiss' => 'modal']) .
                Html::button(Module::t('Create'), ['class' => 'btn btn-success', 'id' => 'update-storage']),
    'closeButton' => false,
]);
Pjax::begin(['id' => 'file-update-pjax']);
$id_storage = ($storageModel != null && $storageModel->id_storage != '') ? $storageModel->id_storage : "null";
$this->registerJs('id_storage = '.$id_storage.';', View::POS_END);
echo $this->render('./_formModal', [
    'model' => ($storageModel != null) ? $storageModel : new Storage(),
    ]);
Pjax::end();
Modal::end();

echo Html::beginTag('div', ['class' => 'd-flex']);
echo Html::button(Module::t('Select File'), ['class' => 'btn btn-primary col', 'style'=>'max-width: 130px;', 'data-bs-toggle' => 'modal', 'data-bs-target' => '#file-picker-modal']);

echo Html::beginTag('div', ['class' => 'col', 'id' => 'file-picker-input-check-selected', 'style' => 'display:none;']);
echo Html::tag('span', '', ['class' => 'fa fa-check', 'style' => 'color:green; font-size:24px; margin-top:7px;']);
echo Html::endTag('div');
echo Html::endTag('div');
//show image
Pjax::begin(['id' => 'file-picker-input-pjax']);
Pjax::end();
Modal::begin([
    'id' => 'show-image-modal',
    'size' => Modal::SIZE_DEFAULT,
]);
echo Html::img('', ['class' => 'img-thumbnail', 'style' => 'width:100%;', 'id' => 'show-image']);
Modal::end();
$this->registerJs(
    <<<JS
        $('.modal-backdrop').remove();
        selectedValue = [];
        //get all checkedItems[] and search id_storage in data
        try{
            var name = document.getElementById('file-picker-input-image-create').getAttribute("src");
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
        
        function selectItem(e){
            
            if(selectedValue.indexOf($(e).attr("data")) == -1){
                    if("$multiple" == "1"){
                        selectedValue.push($(e).attr("data"));
                        //file-picker-input-check-selected display block
                        document.getElementById("file-picker-input-check-selected").style.display = "block";
                    }else{
                        selectedValue = [$(e).attr("data")];
                        //file-picker-input-check-selected display none
                        document.getElementById("file-picker-input-check-selected").style.display = "block";
                    }
                    document.getElementById("file-picker-input").value = selectedValue;
                    
                    
                    updateItemsStatus();
            }else{
                selectedValue.splice(selectedValue.indexOf($(e).attr("data")), 1);
                document.getElementById("file-picker-input").value = selectedValue;
                updateItemsStatus();
                if(selectedValue.length == 0){
                    document.getElementById("file-picker-input-check-selected").style.display = "none";
                }
            }
        }

        function updateItemsStatus(){
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
            document.getElementsByName("checkedItems[]").forEach(function(item){
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

        document.getElementById("file-picker-add-button").addEventListener("click", function(){
            //reload pjax
            $.pjax.reload({container: "#file-update-pjax", timeout: false});
            //update-storage change name to create
            document.getElementById("update-storage").innerHTML = "Create";
            document.getElementById("update-storage").classList.remove("btn-primary");
            document.getElementById("update-storage").classList.add("btn-success");
            //show modal
            $('#file-update-modal').modal('show');
        });

        showImage = function(e){
            document.getElementById("show-image").src = e.src;
            $('#show-image-modal').modal('show');
        }
        
        JS, View::POS_END
    ); 

    $this->registerJs(
        "
        $(document).ready(function () {
            $('.modal-backdrop').remove();
            function checkFilePickerInput() {
                var input = $('#file-picker-input');
                if (input.val() == undefined || input.val() == '') {
                    document.getElementById(\"file-picker-input-check-selected\").style.display = \"none\";
                }else{
                    document.getElementById(\"file-picker-input-check-selected\").style.display = \"block\";
                }
            }
            checkFilePickerInput();

            $('#update-storage').click(function () {
                var myFormData = new FormData();
                myFormData.append('title', $('#storage-title').val());
                myFormData.append('file', document.getElementById('storage-file').files[0]);
                myFormData.append('id_storage', id_storage);
                myFormData.append('" . Yii::$app->request->csrfParam . "', '" . Yii::$app->request->getCsrfToken() . "');

                $.ajax({
                    url: '/storage/default/create',
                    type: 'POST',
                    data: myFormData,
                    contentType: false,
                    processData: false,
                    success: function (data) {
                        $.pjax.reload({container: '#file-picker-pjax'});
                        $('#file-update-modal').modal('hide');
                    }
                });
            });
            $('#file-picker-select').click(function () {
                $('#file-picker-modal').modal('hide');
                
            });

            $('#file-picker-modal').on('show.bs.modal', function () {

                setTimeout(function(){
                    $('.modal-backdrop').remove();
                }, 100);
            });
        });
        "
    );
    //LightBoxAsset::register($this);