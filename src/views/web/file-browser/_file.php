<?php

use yii\web\View;
use yii\helpers\Url;
use yii\helpers\Html;
use portalium\theme\widgets\Panel;
use portalium\storage\models\Storage;
use portalium\storage\Module;

$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->csrfToken;

$name = $model->name;
$variablePrefix = str_replace('-', '_', $widgetName);
$variablePrefix = str_replace(' ', '_', $variablePrefix);
$variablePrefix = str_replace('.', '_', $variablePrefix);

$ext = substr($name, strrpos($name, '.') + 1);
$path = Url::base() . '/' . Yii::$app->setting->getValue('storage::path') . '/';

$fileExtensionsJson = json_encode($fileExtensions);
if ($isPicker) {
    $attributesJson = json_encode($attributes);


    if (isset($attributes)) {
        if (is_array($attributes)) {
            if (in_array('id_storage', $attributes)) {
            } else {
                $attributes[] = 'id_storage';
            }
        }
    }
}
?>
<?php /* ($view == 1) ? Panel::begin([
    'title' => '',
    'bodyOptions' => ['style' => 'height: 286px; display: block; overflow: hidden;'],
    'actions' => [
        'header' => ($view == 1) ? [
            Html::tag('a', '', ['class' => 'fa fa-pencil btn btn-primary', 'name' => 'updateItem', 'data' => (($isJson == 1 && $isPicker) ? json_encode($model->getAttributes($attributes)) : ($isPicker)) ? $model->getAttributes($attributes)[$attributes[0]] : $model->getAttributes(['id_storage'])['id_storage'], 'onclick' => "updatedItem(this)", "all-attributes" => json_encode($model->getAttributes())]),
            Html::tag('i', '', ['class' => 'fa fa-trash btn btn-danger', 'name' => 'removeItem', 'data' => (($isJson == 1 && $isPicker) ? json_encode($model->getAttributes($attributes)) : ($isPicker)) ? $model->getAttributes($attributes)[$attributes[0]] : $model->getAttributes(['id_storage'])['id_storage'], 'onclick' => "removeItem(this, '" . $widgetName . "')", "all-attributes" => json_encode($model->getAttributes())]),
            $isPicker ? Html::checkbox('checkedItems[]', false, ['class' => 'btn btn-success', 'style' => 'margin-right: 0px; width: 30px; height: 30px;', 'img-src' => $name, 'data' => ($isJson == 1) ? json_encode($model->getAttributes($attributes)) : $model->getAttributes($attributes)[$attributes[0]], 'onclick' => "selectItem(this, '" . $widgetName . "')"]) : null,
        ] : [],
        'footer' => [
            Html::tag("div", (strlen($model->title) > 25) ? substr(str_replace("’", "´", $model->title), 0, 25) . '...' : Html::encode($model->title), ['style' => 'float: left;']),
        ]
    ]
]) : null */
// convert to card
?>
<?php if ($view == 1) { ?>
    <div id="w2" class="card" style="display: flex; flex-direction: column;">
        <div class="card-header" style="overflow: auto;position: absolute;width: 100%;background: #fafafa; opacity: 0.8;">
            <div class="panel-title"><span></span>
                <div class="actions" style="float:right;margin-top:-2px; display: flex; justify-content: end; width: 118px;">
                    <?php
                    echo Html::tag('a', '', ['class' => 'fa fa-pencil btn btn-primary', 'style'=>'margin-right: 5px;', 'name' => 'updateItem', 'data' => (($isJson == 1 && $isPicker) ? json_encode($model->getAttributes($attributes)) : ($isPicker)) ? $model->getAttributes($attributes)[$attributes[0]] : $model->getAttributes(['id_storage'])['id_storage'], 'onclick' => "updatedItem(this)", "all-attributes" => json_encode($model->getAttributes())]);
                    echo Html::tag('i', '', ['class' => 'fa fa-trash btn btn-danger', 'style'=>'margin-right: 5px;', 'name' => 'removeItem', 'data' => (($isJson == 1 && $isPicker) ? json_encode($model->getAttributes($attributes)) : ($isPicker)) ? $model->getAttributes($attributes)[$attributes[0]] : $model->getAttributes(['id_storage'])['id_storage'], 'onclick' => "removeItem(this, '" . $widgetName . "')", "all-attributes" => json_encode($model->getAttributes())]);
                    echo $isPicker ? Html::checkbox('checkedItems[]', false, ['class' => 'btn btn-success', 'style' => 'margin-right: 0px; width: 30px; height: 30px;', 'img-src' => $name, 'data' => ($isJson == 1) ? json_encode($model->getAttributes($attributes)) : $model->getAttributes($attributes)[$attributes[0]], 'onclick' => "selectItem(this, '" . $widgetName . "')"]) : null;
                    ?>
                </div>
            </div>
        </div>
        <div class="card-body" style="height: 167px; border-radius: 4px; display: block;overflow: hidden;padding: 0px;overflow-x: auto;">
        <?php } ?>
        <?php
        if (isset(Storage::getMimeTypeList()[$model->mime_type])) {
            $mimeType = Storage::getMimeTypeList()[$model->mime_type];
        } else {
            $mimeType = "other";
        }
        $mime = explode('/', $mimeType)[0];

        if ($mime == 'image') {
            echo Html::img(Html::encode($path . $model->name), ['style' => ' 
        object-fit: cover;
        width: 100%;
        object-position: center 40%;
        padding-top: 0px;
        height: 100%;
    ']);
        } elseif ($mime == 'video') {
            echo Html::tag('video', Html::tag('source', '', ['src' => $path . $model->name, 'type' => 'video/mp4']), ['controls' => '', 'width' => '100%']);
        } elseif ($mime == 'audio') {
            echo Html::tag('audio', Html::tag('source', '', ['src' => $path . $model->name, 'type' => 'audio/mpeg']), ['controls' => '', 'preload' => 'auto', 'width' => '100%']);
        } else {
            echo Html::tag('i', '', ['class' => 'fa fa-file-o', 'style'=>'display: flex; place-content: center; height: 100%; align-items: center; font-size: xxx-large;']);
        }
        ?>
        <?php /* ($view == 1) ? Panel::end() : null */ ?>
        <?php if ($view == 1) { ?>
        </div>
        <div class="card-footer" style="overflow: auto; position:absolute; bottom:0px; background: #fafafa; width: 100%; opacity: 0.8;"><span></span>
            <div class="actions" style="float:right;margin-top:-2px;">
                <div style="float: left;">
                    <?php echo (strlen($model->title) > 25) ? substr(str_replace("’", "´", $model->title), 0, 25) . '...' : Html::encode($model->title); ?>
                </div>
            </div>
        </div>
    </div>
<?php } ?>

<?php
if ($isPicker) {
    $this->registerJs("
    payload$variablePrefix = {
        attribute: 'id_storage',
        multiple: '$multiple',
        isJson: '$isJson',
        attributes: JSON.parse('$attributesJson'),
        name: '$widgetName',
        callbackName: '$callbackName',
        isPicker: '$isPicker',
        id_storage: 'idStorage',
        fileExtensions: JSON.parse('$fileExtensionsJson'),
    };
", View::POS_END);
} else {
    $this->registerJs("
        payload$variablePrefix = {
            isJson: '$isJson',
            name: '$widgetName',
            isPicker: '$isPicker',
            id_storage: 'idStorage',
            fileExtensions: JSON.parse('$fileExtensionsJson'),
        };
    ", View::POS_END);
}
if ($view == 1) {
    $this->registerJs(
      'var updateText = "' . Module::t('Update') . '";',
        View::POS_BEGIN
    );
    $this->registerJs(
        <<<JS
                function updatedItem(e) {
                    var data = $(e).attr("data");
                    var allAttributes = $(e).attr("all-attributes");
                    var parsedData = '';
                    try {
                        parsedData = JSON.parse(data);   
                    } catch (error) {
                        parsedData = JSON.parse(allAttributes);
                    }
                    var widgetName = "$widgetName";

                    updateStorageInput(parsedData, widgetName);
                    removeTitleAttribute(widgetName);
                    updateButtonContent(widgetName);
                    addSpinnerToButton(e);
                    removePencilIcon(e);
                    var idStorage = parsedData.id_storage ? parsedData.id_storage : parsedData;
                    payload$variablePrefix.id_storage = idStorage;
                    reloadFileUpdatePjax(payload$variablePrefix, widgetName, e);
                }

                function updateStorageInput(parsedData, widgetName) {
                    document.getElementById("storage-title" + widgetName).value = parsedData.title ? parsedData.title : parsedData;
                }

                function removeTitleAttribute(widgetName) {
                    $("#file-update-modal" + widgetName + " .file-caption-name").attr("title", "");
                }

                function updateButtonContent(widgetName) {
                    document.getElementById("update-storage" + widgetName).innerHTML = updateText;
                    document.getElementById("update-storage" + widgetName).classList.remove("btn-success");
                    document.getElementById("update-storage" + widgetName).classList.add("btn-primary");
                }

                function addSpinnerToButton(e) {
                    var spinner = document.createElement("span");
                    spinner.classList.add("spinner-border");
                    spinner.classList.add("spinner-border-sm");
                    spinner.setAttribute("role", "status");
                    spinner.setAttribute("aria-hidden", "true");
                    e.appendChild(spinner);
                }

                function removePencilIcon(e) {
                    e.classList.remove("fa-pencil");
                }

                function reloadFileUpdatePjax(payload$variablePrefix, widgetName, e) {
                    $.pjax
                        .reload({
                            container: "#file-update-pjax" + widgetName,
                            url: "/storage/file-browser/index?payload=" + JSON.stringify(payload$variablePrefix),
                            timeout: false,
                            })
                            .done(function () {
                            $("#file-update-modal" + widgetName).modal("show");
                            e.lastChild.remove();
                            e.classList.add("fa-pencil");
                        });
                }

                function removeItem(e, widgetName) {
                    var data = $(e).attr('data');
                    var allAttributes = $(e).attr("all-attributes");
                    var parsedData = '';
                    try {
                        parsedData = JSON.parse(data);   
                    } catch (error) {
                        parsedData = JSON.parse(allAttributes);
                    }
                    document.getElementById('storage-title' + widgetName).value = parsedData.title;
                    $('#file-update-modal .file-caption-name').attr('title', "");

                    addSpinnerToButton(e);
                    removeTrashIcon(e);

                    showConfirmationDialog(parsedData, widgetName, e);
                }

                function addSpinnerToButton(e) {
                    var spinner = document.createElement("span");
                    spinner.classList.add("spinner-border", "spinner-border-sm");
                    spinner.setAttribute("role", "status");
                    spinner.setAttribute("aria-hidden", "true");
                    e.appendChild(spinner);
                }

                function removeTrashIcon(e) {
                    e.classList.remove("fa-trash");
                }

                function showConfirmationDialog(parsedData, widgetName, e) {
                    if (confirm("Are you sure you want to delete this item?")) {
                        deleteItem(parsedData, widgetName, e);
                    } else {
                        removeSpinnerFromButton(e);
                        addTrashIcon(e);
                    }
                }

                function deleteItem(parsedData, widgetName, e) {
                    $.ajax({
                        url: '/storage/file-browser/delete',
                        type: 'post',
                        data: {
                            '_csrf-web': yii.getCsrfToken(),
                            'id': parsedData.id_storage ? parsedData.id_storage : parsedData,
                            'payload': JSON.stringify(payload$variablePrefix),
                        },
                        success: function (data) {
                            reloadFilePickerPjax(widgetName, e);
                        },
                        error: function (data) {
                            reloadFilePickerPjax(widgetName, e);
                        }
                    });
                }

                function reloadFilePickerPjax(widgetName, e) {
                    $.pjax.reload({
                        container: '#file-picker-pjax' + widgetName,
                        url: '/storage/file-browser/index?payload=' + JSON.stringify(payload$variablePrefix),
                        timeout: false
                    }).done(function() {
                        removeSpinnerFromButton(e);
                        addTrashIcon(e);
                    });
                }

                function removeSpinnerFromButton(e) {
                    e.lastChild.remove();
                }

                function addTrashIcon(e) {
                    e.classList.add("fa-trash");
                }

                JS,
        View::POS_END
    );
}
?>