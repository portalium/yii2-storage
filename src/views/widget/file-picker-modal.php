<?php

use Yii;
use yii\widgets\ListView;
use portalium\storage\Module;
use portalium\theme\widgets\Html;
use portalium\storage\models\Storage;
use portalium\theme\widgets\Modal;

if ($multiple == 1) {
    echo Html::hiddenInput($model->formName().'['.$attribute.'][]', null, ['id' => 'file-picker-input', 'name' => $attribute.'[]']);
}else{
    echo Html::hiddenInput($model->formName().'['.$attribute.']', null, ['id' => 'file-picker-input', 'name' => $attribute]);
}


Modal::begin([
    'id' => 'file-picker-modal',
    'header' => Module::t('Select File'),
    'size' => Modal::SIZE_LARGE,
    'footer' => Html::button(Module::t('Close'), ['class' => 'btn btn-default', 'data-dismiss' => 'modal'])
]);

echo ListView::widget([
    'dataProvider' => $files,
    'itemView' => '_file',
    'options' => [
        'tag' => 'div',
        'class' => 'row',
    ],
    'itemOptions' => [
        'tag' => 'div',
        'class' => 'col-lg-4 col-sm-3 col-md-4',
    ],
    'summary' => false,
]);

Modal::end();

echo Html::button(Module::t('Select File'), ['class' => 'btn btn-primary', 'data-toggle' => 'modal', 'data-target' => '#file-picker-modal']);
echo '
    <script>
        selectedValue = [];
        document.getElementsByName("checkedItems[]").forEach(function(item){
            item.onclick = function(){
                if(selectedValue.indexOf(this.getAttribute("data")) == -1){
                    if("' . $multiple . '" == "1"){
                        selectedValue.push(this.getAttribute("data"));
                    }else{
                        selectedValue = [this.getAttribute("data")];
                    }
                    document.getElementById("file-picker-input").value = selectedValue;
                    updateItemsStatus();
                }else{
                    selectedValue.splice(selectedValue.indexOf(this.getAttribute("data")), 1);
                    document.getElementById("file-picker-input").value = selectedValue;
                    updateItemsStatus();
                }

            }
        });
        function updateItemsStatus(){

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
        
    </script>
';
