<?php

use portalium\storage\bundles\FilePickerAsset;
use portalium\storage\Module;
use portalium\theme\widgets\Alert;
use portalium\theme\widgets\Button;
use portalium\theme\widgets\Html;
use portalium\widgets\Pjax;
use yii\helpers\Url;

FilePickerAsset::register($this);

/* @var $this yii\web\View */
/* @var $form portalium\theme\widgets\ActiveForm */
/* @var $model portalium\storage\models\Storage */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Module::t('Storage');
$this->params['breadcrumbs'][] = $this->title;

?>
<?php
echo Html::beginTag('span', ['class' => 'col-md-4 d-flex gap-2']);
echo Html::tag(
    'span',
    Html::textInput('file', '', [
        'class' => 'form-control',
        'placeholder' => Module::t('Search file..')
    ]) .
        Html::tag('span', Html::tag('i', '', ['class' => 'fa fa-search', 'aria-hidden' => 'true']), ['class' => 'input-group-text']),
    ['class' => 'input-group']
);

echo Button::widget([
    'label' => Html::tag('i', '', ['class' => 'fa fa-upload', 'aria-hidden' => 'true']) .
        Html::tag('span', Module::t('Upload'), ['class' => 'ms-2']),
    'encodeLabel' => false,
    'options' => [
        'type' => 'button',
        'class' => 'btn btn-success btn-md d-flex',
        'onclick' => 'openUploadModal()',
    ],
]);

echo Html::endTag('span');

?>
<br />
<?php
Pjax::begin([
    'id' => 'upload-file-pjax',
    'history' => false,
    'timeout' => false,
    'enablePushState' => false,
]);
Pjax::end();

Pjax::begin([
    'id' => 'list-file-pjax'
]);
echo $this->render('_file-list', [
    'dataProvider' => $dataProvider,
]);
Pjax::end();

Pjax::begin([
    'id' => 'rename-file-pjax',
    'history' => false,
    'timeout' => false,
    'enablePushState' => false
]);


Pjax::end();

Pjax::begin([
    'id' => 'update-file-pjax',
    'history' => false,
    'timeout' => false,
    'enablePushState' => false
]);

Pjax::end();

Pjax::begin([
    'id' => 'share-file-pjax',
]);
Pjax::end();

?>

<?php
$this->registerJs(
    <<<JS
    function openUploadModal() {
        event.preventDefault();
        $.pjax.reload({
            container: '#upload-file-pjax',
            type: 'GET',
            url: '/storage/default/upload-file',
        }).done(function() {
            setTimeout(function() {
                $('#uploadModal').modal('show');
            }, 1000);
        }).fail(function(e) {
            console.log(e);
        });
    }
    $(document).on('click', '#uploadButton', function(e) {
        e.preventDefault();
    
        var form = document.getElementById('uploadForm');
        var formData = new FormData(form);
    
        $.ajax({
            url: form.action,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                $('#uploadModal').modal('hide');
                $.pjax.reload({container: "#pjax-flash-message"}).done(function() {
                    $.pjax.reload({container: '#list-file-pjax'});
                });
            },
            error: function(xhr, status, error) {
                $('#uploadModal').modal('hide');
                $.pjax.reload({container: "#pjax-flash-message"}).done(function() {
                    $.pjax.reload({container: '#list-file-pjax'});
                });
                console.error('AJAX Error: ' + error);
            }
        });
    });
JS,
    \yii\web\View::POS_END
);

$this->registerJs(
    <<<JS
    
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

JS,
    \yii\web\View::POS_END
);

$this->registerJs(
    <<<JS
    function openRenameModal(id) {
        event.preventDefault();
        $.pjax.reload({
            container: '#rename-file-pjax',
            type: 'GET',
            url: '/storage/default/rename-file',
            data: { id: id },
        }).done(function() {
            setTimeout(function() {
                $('#renameModal').modal('show');
            }, 1000);
        }).fail(function(e) {
            console.log(e);
        });
    }
    $(document).on('click', '#renameButton', function(e) {
        e.preventDefault();
        var form = $('#renameForm');
    $.ajax({
        url: form.attr('action'),
        type: 'POST',
        data: form.serialize(),
        headers: {
        'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content') 
    },
        success: function(response) {
            if (response.success) {
                $('#renameModal').modal('hide');
                $.pjax.reload({container: "#pjax-flash-message"}).done(function() {
                $.pjax.reload({container: '#list-file-pjax'});
            }); 
            } else {
               $.pjax.reload({container: "#pjax-flash-message"}).done(function() {
                $.pjax.reload({container: '#list-file-pjax'});
            });
            }
        },
        error: function() {
            $.pjax.reload({container: "#pjax-flash-message"}).done(function() {
                $.pjax.reload({container: '#list-file-pjax'});
            });
        }
    });
    });
    JS,
    \yii\web\View::POS_END
);

$this->registerJs(
    <<<JS
    function openUpdateModal(id) {
        event.preventDefault();
        $.pjax.reload({
            container: '#update-file-pjax',
            type: 'GET',
            url: '/storage/default/update-file',
            data: { id: id },
        }).done(function() {
            setTimeout(function() {
                $('#updateModal').modal('show');
            }, 1000);
        }).fail(function(e) {
            console.log(e);
        });
    }

    $(document).on('click', '#updateButton', function(e) {
        e.preventDefault();
        var form = $('#updateForm')[0];
        var formData = new FormData(form);

        $.ajax({
            url: $(form).attr('action'),
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $('#updateModal').modal('hide');
                    $.pjax.reload({container: "#pjax-flash-message"}).done(function() {
                        $.pjax.reload({container: '#list-file-pjax'});
                    });
                } else {
                    $.pjax.reload({container: "#pjax-flash-message"}).done(function() {
                        $.pjax.reload({container: '#list-file-pjax'});
                    });
                }
            },
            error: function() {
                $.pjax.reload({container: "#pjax-flash-message"}).done(function() {
                    $.pjax.reload({container: '#list-file-pjax'});
                });
            }
        });
    });
    JS,
    \yii\web\View::POS_END
);

$copyUrl = Url::to(['default/copy']);
$this->registerJs(
    <<<JS
    function copyFile(element) {
        event.preventDefault();
        var id = element.getAttribute('data-id');

        fetch('{$copyUrl}?id=' + id, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $.pjax.reload({container: '#list-file-pjax', async: false});
            }
        })
        .catch(error => {
            console.error('Request error', error);
        });
    }
    JS,
    \yii\web\View::POS_END
);

$this->registerJs(
    <<<JS
function removeItem(el) {
    var id = el.getAttribute('data-id');
    var url = el.getAttribute('data-url');
    var csrfToken = $('meta[name="csrf-token"]').attr("content");
    
    if (!id || !url) {
        return;
    }

    var formData = new FormData();
    formData.append('id', id);
    formData.append(yii.getCsrfParam(), csrfToken);

    fetch(url, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(function(response) {
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.status);
        }
        return response.json();
    })
    .then(function(data) {
        if (data.success) {
            if ($.pjax && $('#list-file-pjax').length) {
                $.pjax.reload({container: '#list-file-pjax', async: false});
            } else {
                window.location.reload();
            }
        }
    })
    .catch(function(error) {
        console.error("Fetch error:", error);
    });
}
JS,
    \yii\web\View::POS_END
);

$this->registerJs(
    <<<JS

function bindContextMenus() {
    $('[id^="menu-trigger-"]').off('click').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var id = $(this).attr('id').replace('menu-trigger-', '');
        var currentMenu = $('#context-menu-' + id);

        $('.dropdown-menu').not(currentMenu).hide();

        currentMenu.toggle();
    });
    
    $(document).off('click.contextmenu-close').on('click.contextmenu-close', function() {
        $('.dropdown-menu').hide();
    });
}

bindContextMenus();

$(document).on('pjax:end', function() {
    bindContextMenus();
});
JS
);
?>
