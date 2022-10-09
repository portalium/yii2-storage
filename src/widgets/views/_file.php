<?php

use yii\web\View;
use yii\helpers\Url;
use yii\helpers\Html;
use portalium\theme\widgets\Panel;
use portalium\storage\models\Storage;

$name = $model->name;
$ext = substr($name, strrpos($name, '.') + 1);
$path = Url::base() . Yii::$app->setting->getValue('app::data');
?>
<?php Panel::begin([
    'title' => '',
    'bodyOptions' => ['style' => 'height: 200px; display: block;'],
    'actions' => [
        'header' => ($view == 1) ? [
            Html::tag('a', '', ['class' => 'fa fa-pencil btn btn-primary', 'name' => 'updateItem', 'data' => ($json == 1 ) ? json_encode($model->getAttributes($returnAttribute)) : $model->getAttributes($returnAttribute)[$returnAttribute[0]], 'onclick' => "updatedItem(this)"]),
            Html::tag('i', '', ['class' => 'fa fa-check btn btn-success', 'name' => 'checkedItems[]', 'data' => ($json == 1 ) ? json_encode($model->getAttributes($returnAttribute)) : $model->getAttributes($returnAttribute)[$returnAttribute[0]], 'onclick' => "selectItem(this)"]),
        ] : [],
        'footer' => [
            Html::tag("div",(strlen($model->title) > 25) ? substr(str_replace("’","´",$model->title), 0, 25) . '...' : Html::encode($model->title), ['style' => 'float: left;']),
        ]
    ]
]) ?>

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