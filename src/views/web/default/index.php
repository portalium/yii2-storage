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
function downloadFile(id) {
    event.preventDefault();

    $.post({
        url: '/storage/default/download-file',
        data: { id: id },
        xhrFields: { responseType: 'blob' },
        headers: {
            'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(data, status, xhr) {
            const disposition = xhr.getResponseHeader('Content-Disposition');
            if (disposition && disposition.indexOf('attachment') !== -1) {
                const filename = disposition.split('filename=')[1]?.replace(/["']/g, '') || 'downloaded_file';
                const blobUrl = URL.createObjectURL(data);
                const a = document.createElement('a');
                a.href = blobUrl;
                a.download = decodeURIComponent(filename);
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(blobUrl);
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
}
JS,
    \yii\web\View::POS_END
);



$this->registerJs(
    <<<JS
    function openRenameModal(id) {
        event.preventDefault();
        $.ajax({
            url: '/storage/default/rename-file',
            type: 'GET',
            data: { id: id },
            success: function(response) {
                if (response.success === false) {
                    $.pjax.reload({container: "#pjax-flash-message"}).done(function() {
                        $.pjax.reload({container: '#list-file-pjax'});
                    });
                } else {
                    $.pjax.reload({
                        container: '#rename-file-pjax',
                        type: 'GET',
                        url: '/storage/default/rename-file',
                        data: { id: id },
                    }).done(function() {
                        $('#renameModal').modal('show');
                    });
                }
            },
            error: function(e) {
                console.log('Ajax Error', e);
            }
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
                }
                $.pjax.reload({container: "#pjax-flash-message"}).done(function() {
                    $.pjax.reload({container: '#list-file-pjax'});
                });
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
        $.ajax({
            url: '/storage/default/update-file',
            type: 'GET',
            data: { id: id },
            success: function(response) {
                if (response.success === false) {
                    $.pjax.reload({container: "#pjax-flash-message"}).done(function() {
                        $.pjax.reload({container: '#list-file-pjax'});
                    });
                } else {
                    $.pjax.reload({
                        container: '#update-file-pjax',
                        type: 'GET',
                        url: '/storage/default/update-file',
                        data: { id: id },
                    }).done(function() {
                        $('#updateModal').modal('show');
                    });
                }
            },
            error: function(e) {
                console.log('Ajax Error', e);
            }
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
                }
                $.pjax.reload({container: "#pjax-flash-message"}).done(function() {
                    $.pjax.reload({container: '#list-file-pjax'});
                });
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
    function copyFile(id) {
        event.preventDefault();
        
        $.ajax({
            url: '/storage/default/copy-file',
            type: 'POST',
            data: { id: id },
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {   
                $.pjax.reload({container: "#pjax-flash-message"}).done(function() {
                    $.pjax.reload({container: '#list-file-pjax'});
                });
            },
            error: function() {
                $.pjax.reload({container: "#pjax-flash-message"}).done(function() {
                    $.pjax.reload({container: '#list-file-pjax'});
                });
            }
        });
    }
    JS,
    \yii\web\View::POS_END
);

$this->registerJs(
    <<<JS
    function deleteFile(id) {
        event.preventDefault();
        
        $.ajax({
            url: '/storage/default/delete-file',  
            type: 'POST',
            data: { id: id },  
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')  
            },
            success: function(response) {
                if (response.success) {
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
