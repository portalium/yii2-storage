<?php

use yii\web\View;
use yii\helpers\Url;
use yii\helpers\Html;
use portalium\theme\widgets\Panel;
use portalium\storage\models\Storage;
use portalium\storage\Module;
use portalium\storage\bundles\FilePickerAsset;

FilePickerAsset::register($this);


$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->csrfToken;

$name = $model->name;
$variablePrefix = str_replace('-', '_', $widgetName);
$variablePrefix = str_replace(' ', '_', $variablePrefix);
$variablePrefix = str_replace('.', '_', $variablePrefix);

$ext = substr($name, strrpos($name, '.') + 1);
// $path = Url::base() . '/' . Yii::$app->setting->getValue('storage::path') . '/';
$path = '/storage/default/get-file?id=';
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
<?php /* ($isModal == 1) ? Panel::begin([
    'title' => '',
    'bodyOptions' => ['style' => 'height: 286px; display: block; overflow: hidden;'],
    'actions' => [
        'header' => ($isModal == 1) ? [
            Yii::$app->user->can('storageWebDefaultUpdate',['id_module'=>'storage'])? Html::tag('a', '', ['class' => 'fa fa-pencil btn btn-primary', 'name' => 'updateItem', 'data' => (($isJson == 1 && $isPicker) ? json_encode($model->getAttributes($attributes)) : ($isPicker)) ? $model->getAttributes($attributes)[$attributes[0]] : $model->getAttributes(['id_storage'])['id_storage'], 'onclick' => "updatedItem(this)", "all-attributes"=>json_encode($model->getAttributes())]) :null,
            Yii::$app->user->can('storageWebDefaultDelete',['id_module'=>'storage']) ? Html::tag('i', '', ['class' => 'fa fa-trash btn btn-danger', 'name' => 'removeItem', 'data' => (($isJson == 1 && $isPicker) ? json_encode($model->getAttributes($attributes)) : ($isPicker)) ? $model->getAttributes($attributes)[$attributes[0]] : $model->getAttributes(['id_storage'])['id_storage'], 'onclick' => "removeItem(this, '" . $widgetName . "')", "all-attributes"=>json_encode($model->getAttributes())]) :null,
            $isPicker ? Html::checkbox('checkedItems[]', false, ['class' => 'btn btn-success', 'style'=>'margin-right: 0px; width: 30px; height: 30px;', 'img-src' => $name, 'data' => ($isJson == 1) ? json_encode($model->getAttributes($attributes)) : $model->getAttributes($attributes)[$attributes[0]], 'onclick' => "selectItem(this, '" . $widgetName . "')"]) : null,
        ] : [],
        'footer' => [
            Html::tag("div", (strlen($model->title) > 25) ? substr(str_replace("’", "´", $model->title), 0, 25) . '...' : Html::encode($model->title), ['style' => 'float: left;']),
        ]
    ]
]) : null */
// convert to card


?>
<?php if ($isModal == 1) { ?>
    <style>
        .file-picker-card.selected {
            border: 2px solid #6cbc2d;
            border-radius: 4px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: box-shadow 0.3s ease, border-color 0.3s ease;
        }

        .file-picker-card.selected:hover {
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        }
    </style>

    <style>
        #file-picker-selectapp-logo-wide:disabled {
            background-color: #dcdcdc;
            cursor: not-allowed;
            color: #999;
        }

        #file-picker-selectapp-logo-wide {
            transition: background-color 0.3s ease, color 0.3s ease;
        }
    </style>



    <div id="w2" class="card file-picker-card" onclick="selectItemFromCard(event, this, '<?php echo $widgetName; ?>')" style="display: flex; flex-direction: column; position: relative; ">
        <div class="overlay" style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1;"></div>
        <div class="card-header" style="align-items:center; overflow: auto; position: absolute; width: 100%;  background: #fafafa; justify-content:space-between; width: 100%; padding-left:6px; padding-right:1px; ">
            <div class="panel-title w-100">
                <div style="display:flex;align-items:center">
                    <?php
                    echo $isPicker ? Html::checkbox('checkedItems[]', false, ['class' => 'btn btn-success', 'style' => 'margin-right: 10px; width: 20px; height: 20px;', 'id-src' => $model->id_storage, 'img-src' => $name, 'data' => ($isJson == 1) ? json_encode($model->getAttributes($attributes)) : $model->getAttributes($attributes)[$attributes[0]]]) : null;
                    ?>
                </div>
                <div class="actions" style="float:right; display: flex; justify-content: end; width: 138px;">
                    <?php
                    echo Html::tag('a', '', ['class' => 'fa fa-pencil btn btn-primary', 'style' => 'margin-right: 5px; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center;', 'name' => 'updateItem', 'data' => (($isJson == 1 && $isPicker) ? json_encode($model->getAttributes($attributes)) : ($isPicker)) ? $model->getAttributes($attributes)[$attributes[0]] : $model->getAttributes(['id_storage'])['id_storage'], 'onclick' => "updatedItem(this)", "all-attributes" => json_encode($model->getAttributes())]);
                    echo Html::tag('i', '', ['class' => 'fa fa-trash btn btn-danger', 'style' => 'margin-right: 5px; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center;', 'name' => 'removeItem', 'data' => (($isJson == 1 && $isPicker) ? json_encode($model->getAttributes($attributes)) : ($isPicker)) ? $model->getAttributes($attributes)[$attributes[0]] : $model->getAttributes(['id_storage'])['id_storage'], 'onclick' => "removeItem(this, '" . $widgetName . "')", "all-attributes" => json_encode($model->getAttributes())]);
                    echo Html::tag('i', '', ['class' => 'fa fa-download btn btn-primary', 'style' => 'margin-right: 5px; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center;', 'download-url' => $path . $model->id_storage, 'onclick' => "downloadItem(this)"]);
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
            echo Html::img(Html::encode($path . $model->id_storage), ['style' => ' 
        object-fit: cover;
        width: 100%;
        object-position: center 40%;
        padding-top: 0px;
        height: 100%;
    ']);
        } elseif ($mime == 'video') {
            echo Html::tag('video', Html::tag('source', '', ['src' => $path . $model->id_storage, 'type' => 'video/mp4']), ['controls' => '', 'width' => '100%']);
        } elseif ($mime == 'audio') {
            echo Html::tag('audio', Html::tag('source', '', ['src' => $path . $model->id_storage, 'type' => 'audio/mpeg']), ['controls' => '', 'preload' => 'auto', 'width' => '100%']);
        } else {
            echo Html::tag('i', '', ['class' => 'fa fa-file-o', 'style' => 'display: flex; place-content: center; height: 100%; align-items: center; font-size: xxx-large;']);
        }
        ?>
        <?php /* ($isModal == 1) ? Panel::end() : null */ ?>
        <?php if ($isModal == 1) { ?>
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
$this->registerJs("
    
if (typeof downloadItem === 'undefined') {
    function downloadItem(e) {
        var downloadUrl = e.getAttribute('download-url');
        var a = document.createElement('a');
        a.href = downloadUrl;
        a.download = downloadUrl.split('/').pop();
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }
}

", View::POS_END);
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
if ($isModal == 1) {
    $this->registerJs(
        'var updateText = "' . Module::t('Update') . '";',
        View::POS_BEGIN
    );
    $this->registerJs(
        <<<JS

                $('#file-picker-buttonapp-logo-wide').on('click', function () {
                    restoreCardSelections(); 
                });

                $(document).on('shown.bs.modal', '#file-picker-modalapp-logo-wide', function () {
                    restoreCardSelections();
                });

                function restoreCardSelections() {
                    const allCards = document.querySelectorAll('.file-picker-card');
                    allCards.forEach(card => {
                        const checkbox = card.querySelector('input[type="checkbox"]');
                        if (checkbox && checkbox.checked) {
                            card.classList.add('selected'); 
                        } else {
                            card.classList.remove('selected'); 
                        }
                    });
                }

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

                function selectItemFromCard(event, card, name) {
                    const checkbox = card.querySelector('input[type="checkbox"]');
                    if (checkbox) {
                        if (event.target === checkbox) {
                            if (checkbox.checked) {
                                card.classList.add('selected'); 
                            } else {
                                card.classList.remove('selected'); 
                            }
                        } else {
                            checkbox.checked = !checkbox.checked; 
                            if (checkbox.checked) {
                                card.classList.add('selected');
                            } else {
                                card.classList.remove('selected');
                            }
                        }

                        if ("$multiple" !== "1") {
                            const allCards = document.querySelectorAll('.file-picker-card');
                            allCards.forEach(c => {
                                if (c !== card) {
                                    c.classList.remove('selected');
                                    const cb = c.querySelector('input[type="checkbox"]');
                                    if (cb) cb.checked = false;
                                }
                            });
                        }
                        selectItem(checkbox, name);
                    }
                }

                function updateUseSelectedButtonState(name) {
                    const button = document.getElementById('file-picker-select' + name); 
                    const selectedCards = document.querySelectorAll('.file-picker-card.selected'); 

                    if (selectedCards.length > 0) {
                        button.disabled = false; 
                    } else {
                        button.disabled = true; 
                    }
                }

                document.addEventListener('click', (event) => {
                    if (event.target.closest('.file-picker-card') || event.target.type === 'checkbox') {
                        const modalId = event.target.closest('.modal')?.id;
                        const name = modalId?.replace('file-picker-modal', '') || '';
                        updateUseSelectedButtonState(name);
                    }
                });

                $(document).on('shown.bs.modal', '[id^="file-picker-modal"]', function () {
                    const name = this.id.replace('file-picker-modal', '');
                    updateUseSelectedButtonState(name);
                });

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