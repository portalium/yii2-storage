<?php

use yii\web\View;
use yii\helpers\Url;
use yii\helpers\Html;
use portalium\theme\widgets\Panel;
use portalium\storage\models\Storage;

$name = $model->name;
$ext = substr($name, strrpos($name, '.') + 1);
$path = Url::base() . '/'. Yii::$app->setting->getValue('storage::path') .'/';
if (isset($attributes)) {
    if (is_array($attributes)) {
        if (in_array('id_storage', $attributes)) {
        }else{
            $attributes[] = 'id_storage';
        }
    }
}
?>
<?php ($view == 1) ? Panel::begin([
    'title' => '',
    'bodyOptions' => ['style' => 'height: 200px; display: block; overflow: hidden;'],
    'actions' => [
        'header' => ($view == 1) ? [
            Html::tag('a', '', ['class' => 'fa fa-pencil btn btn-primary', 'name' => 'updateItem', 'data' => ($isJson == 1 ) ? json_encode($model->getAttributes($attributes)) : $model->getAttributes($attributes)[$attributes[0]], 'onclick' => "updatedItem(this)"]),
            Html::tag('i', '', ['class' => 'fa fa-check btn btn-success', 'name' => 'checkedItems[]', 'data' => ($isJson == 1 ) ? json_encode($model->getAttributes($attributes)) : $model->getAttributes($attributes)[$attributes[0]], 'onclick' => "selectItem(this, '" . $widgetName . "')"]),
            Html::tag('i', '', ['class' => 'fa fa-trash btn btn-danger', 'name' => 'removeItem', 'data' => ($isJson == 1 ) ? json_encode($model->getAttributes($attributes)) : $model->getAttributes($attributes)[$attributes[0]], 'onclick' => "removeItem(this, '" . $widgetName . "')"]),
        ] : [],
        'footer' => [
            Html::tag("div",(strlen($model->title) > 25) ? substr(str_replace("’","´",$model->title), 0, 25) . '...' : Html::encode($model->title), ['style' => 'float: left;']),
        ]
    ]
]) : null ?>

<?php 
    if(isset(Storage::getMimeTypeList()[$model->mime_type])){
        $mimeType = Storage::getMimeTypeList()[$model->mime_type];
    }else{
        $mimeType = "other";
    }
    $mime = explode('/', $mimeType)[0];
    if ($mime == 'image') {
        echo Html::img(Html::encode($path . $model->name),['style' => ' 
        object-fit: cover;
        width: 100%;
        height: 177px;
        object-position: center 40%;
        padding-top: 20px;
      ']);
    } elseif ($mime == 'video') {
        echo Html::tag('video', Html::tag('source', '', ['src' => $path . $model->name, 'type' => 'video/mp4']), ['controls' => '', 'width' => '100%']);
    } elseif ($mime == 'audio') {
        echo Html::tag('audio', Html::tag('source', '', ['src' => $path . $model->name, 'type' => 'audio/mpeg']), ['controls' => '', 'preload' => 'auto', 'width' => '100%']);
    } else {
        echo Html::tag('i', '', ['class' => 'fa fa-file-o']);
    }
?>
<?php ($view == 1) ? Panel::end() : null ?>

<?php 
    if($view == 1){
        $this->registerJs(
                <<<JS
                function updatedItem(e){
                    var data = $(e).attr('data');
                    var data = JSON.parse(data);
                    document.getElementById('storage-title' + '$widgetName').value = data.title;
                    $('#file-update-modal' + '$widgetName' + ' .file-caption-name').attr('title', "");
                    document.getElementById("update-storage" + '$widgetName').innerHTML = "Update";
                    document.getElementById("update-storage" + '$widgetName').classList.remove("btn-success");
                    document.getElementById("update-storage" + '$widgetName').classList.add("btn-primary");
                    //add spinner to button
                    var spinner = document.createElement("span");
                    spinner.classList.add("spinner-border");
                    spinner.classList.add("spinner-border-sm");
                    spinner.setAttribute("role", "status");
                    spinner.setAttribute("aria-hidden", "true");
                    e.appendChild(spinner);
                    //remove pencil icon
                    e.classList.remove("fa-pencil");

                    
                    //file-update-pjax
                    $.pjax.reload({container: '#file-update-pjax' + '$widgetName', url: '/storage/default/create?id=' + data.id_storage, timeout: false}).done(function() {
                        $('#file-update-modal' + '$widgetName').modal('show');
                        spinner.remove();
                        e.classList.add("fa-pencil");

                    });
                }

                function removeItem(e, widgetName){
                    var data = $(e).attr('data');
                    var data = JSON.parse(data);
                    document.getElementById('storage-title' + widgetName).value = data.title;
                    $('#file-update-modal .file-caption-name').attr('title', "");
                    //add spinner to button
                    var spinner = document.createElement("span");
                    spinner.classList.add("spinner-border");
                    spinner.classList.add("spinner-border-sm");
                    spinner.setAttribute("role", "status");
                    spinner.setAttribute("aria-hidden", "true");
                    e.appendChild(spinner);
                    //remove pencil icon
                    e.classList.remove("fa-trash");
                    //show confirm
                    if(confirm("Are you sure you want to delete this item?")){
                        $.ajax({
                            url: '/storage/default/delete?id=' + data.id_storage,
                            type: 'post',
                            data: {
                                '_csrf-web': yii.getCsrfToken()
                            },
                            success: function (data) {
                                $.pjax.reload({container: '#file-picker-pjax' + widgetName, timeout: false, url: '/storage/default/index'
                                }).done(function() {
                                spinner.remove();
                                e.classList.add("fa-trash");
                            });
                            }
                        });
                    }else{
                        spinner.remove();
                        e.classList.add("fa-trash");
                    }
                }
                JS, View::POS_END
            ); 
    }
?>