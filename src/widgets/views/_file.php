<?php

use yii\web\View;
use yii\helpers\Url;
use yii\helpers\Html;
use yii2assets\pdfjs\PdfJs;
use diginova\storage\Module;
use diginova\media\models\Media;
use portalium\theme\widgets\Modal;
use portalium\theme\widgets\Panel;
use diginova\storage\models\Storage;

$name = $model->name;
$ext = substr($name, strrpos($name, '.') + 1);
$path = Url::base() . '/data/';
?>
<?php Panel::begin([
    'title' => '',
    'bodyOptions' => ['style' => 'height: 200px; display: block;'],
    'actions' => [
        'header' => ($view == 1) ? [
            Html::tag('a', '', ['class' => 'fa fa-pencil btn btn-primary', 'name' => 'updateItem', 'data' => json_encode($model->getAttributes($returnAttribute)), 'onclick' => "updatedItem(this)"]),
            Html::tag('i', '', ['class' => 'fa fa-check btn btn-success', 'name' => 'checkedItems[]', 'data' => json_encode($model->getAttributes($returnAttribute)) , 'onclick' => "selectItem(this)"]),
        ] : [],
        'footer' => [
            Html::tag("div",(strlen($model->title) > 25) ? substr(str_replace("’","´",$model->title), 0, 25) . '...' : Html::encode($model->title), ['style' => 'float: left;']),
        ]
    ]
]) ?>

<?php 
    
    if(isset(Storage::getMimeTypeList()[$model->mime_type])){
        $mimeType = Storage::getMimeTypeList()[$model->mime_type];
    }else{
        $mimeType = "other";
    }
    $mime = explode('/', $mimeType)[0];
    if ($mime == 'image') {
        echo Html::img(Html::encode($path . $model->name), ['width' => '100%', 'height' => '100%']);
    } elseif ($mime == 'video') {
        echo Html::tag('video', Html::tag('source', '', ['src' => $path . $model->name, 'type' => 'video/mp4']), ['controls' => '', 'width' => '100%']);
    } elseif ($mime == 'audio') {
        echo Html::tag('audio', Html::tag('source', '', ['src' => $path . $model->name, 'type' => 'audio/mpeg']), ['controls' => '', 'preload' => 'auto', 'width' => '100%']);
    } else {
        echo Html::tag('i', '', ['class' => 'fa fa-file-o']);
    }
?>
<?php Panel::end() ?>

<?php 
    if($view == 1){
        $this->registerJs(
                <<<JS
                function updatedItem(e){
                    var data = $(e).attr('data');
                    var data = JSON.parse(data);
                    document.getElementById('storage-title').value = data.title;
                    $('#file-update-modal .file-caption-name').attr('title', "");
                    document.getElementById("update-storage").innerHTML = "Update";
                    document.getElementById("update-storage").classList.remove("btn-success");
                    document.getElementById("update-storage").classList.add("btn-primary");
                    //file-update-pjax
                    $.pjax.reload({container: '#file-update-pjax', url: '?id_storage=' + data.id_storage, timeout: false});
                    $('#file-update-modal').modal('show');
                }
                JS, View::POS_END
            ); 
    }
?>