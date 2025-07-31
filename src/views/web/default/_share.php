<?php

use portalium\storage\Module;
use portalium\theme\widgets\Button;
use portalium\theme\widgets\Html;
use portalium\theme\widgets\Modal;
use yii\widgets\Pjax;


$users = [
    ['name' => 'Sümeyye Demir', 'email' => 'sumeyye.demir@example.com'],
    ['name' => 'Ahmet Yılmaz', 'email' => 'ahmet.yilmaz@example.com'],
    ['name' => 'Elif Kaya', 'email' => 'elif.kaya@example.com'],
    ['name' => 'Elif Kaya', 'email' => 'elif.kaya@example.com'],
    ['name' => 'Elif Kaya', 'email' => 'elif.kaya@example.com'],
    ['name' => 'Elif Kaya', 'email' => 'elif.kaya@example.com'],
    ['name' => 'Elif Kaya', 'email' => 'elif.kaya@example.com'],
    ['name' => 'Elif Kaya', 'email' => 'elif.kaya@example.com'],
    ['name' => 'Elif Kaya', 'email' => 'elif.kaya@example.com'],
];


if (isset($model)) {
    Modal::begin([
        'id' => 'shareModal',
        'title' => Module::t('Share'),
        'dialogOptions' => ['class' => 'modal-dialog-centered'],
        'footer' => Button::widget([
                'label' => Html::tag('i', '', ['class' => 'fa fa-link me-2']) . Module::t('Copy Link'),
                'encodeLabel' => false,
                'options' => [
                    'class' => 'btn btn-outline-secondary',
                    'id' => 'copyLink',
                    'data-copied' => htmlspecialchars(Module::t('Copied!'), ENT_QUOTES, 'UTF-8'),
                    'onclick' => 'handleCopyLink(this)'
                ],
            ]) . ' ' . Button::widget([
                'label' => Module::t('Done'),
                'options' => [
                    'class' => 'btn btn-success',
                    'type' => 'submit',
                    'form' => 'shareForm',
                    'id' => 'doneButton',
                ],
            ]),
    ]);

    echo Html::textInput('searchUser', '', [
        'class' => 'form-control form-control-lg mb-3',
        'id' => 'searchUser',
        'placeholder' => Module::t('Add person or group'),
    ]);

    echo Html::tag('h6', Module::t('People with access'), ['class' => 'fw-bold mb-3 text-secondary']);

    echo Html::beginTag('span', ['class' => 'list-group list-group-flush rounded-3', 'style' => 'max-height: 300px; overflow-y: auto;']);
    foreach ($users as $user) {
        echo Html::tag(
            'span',
            Html::tag(
                'span',
                Html::tag('i', '', ['class' => 'fa fa-user me-2', 'style' => 'font-size: 20px;']) .
                Html::tag(
                    'span',
                    Html::tag('h6', $user['name'], ['class' => 'mb-0 fw-semibold']) .
                    Html::tag('small', $user['email'], ['class' => 'text-muted d-block']),
                    ['class' => 'd-flex flex-column ms-2']
                ),
                ['class' => 'd-flex align-items-center']
            ) .
            Button::widget([
                'label' => Html::tag('i', '', ['class' => 'fa fa-trash']),
                'encodeLabel' => false,
                'options' => [
                    'class' => 'btn btn-light text-danger',
                    'title' => 'Sil',
                    'onclick' => 'handleRemoveUser(this)'
                ],
            ]),
            ['class' => 'd-flex justify-content-between align-items-center p-2']
        );
    }
    echo Html::endTag('span');

    echo Html::label(Module::t('General Access'), null, ['class' => 'fw-bold mb-3 text-secondary mt-4']);

echo Html::tag('div',
    Html::tag('div',
        Html::tag('i', '', [
            'id' => 'access-icon', 
            'class' => 'access-icon fa fa-lock bg-light rounded-circle p-2',
            'style' => 'font-size: 18px; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;'
        ]) .
        Html::tag('div',
            Html::beginTag('div', ['class' => 'dropdown d-inline']) .

                Html::button(
                    Html::tag('span', Module::t('Restricted'), [
                        'id' => 'access-text',
                        'class' => 'access-text fw-semibold d-block',
                        'data-public' => Module::t('Public'),
                        'data-private' => Module::t('Restricted'),
                    ]),
                    [
                        'class' => 'btn btn-light border-0 p-0 text-start',
                        'type' => 'button',
                        'id' => 'accessDropdownBtn',
                        'data-bs-toggle' => 'dropdown',
                        'aria-expanded' => 'false',
                        'style' => 'box-shadow:none;'
                    ]
                ) .

                Html::beginTag('ul', [  
                    'class' => 'dropdown-menu custom-dropdown-align2 position-absolute mt-1',
                    'aria-labelledby' => 'accessDropdownBtn',
                    'style' => 'min-width: 175px; top: 100%; z-index: 1000;'
                ]).

                    Html::tag('li',
                        Html::a(
                            Html::tag('i', '', ['class' => 'fa fa-lock me-2']) . Module::t('Restricted'),
                            '#',
                            [
                                'class' => 'dropdown-item',
                                'onclick' => 'setAccessLevel("private")'
                            ]
                        )
                    ) .

                    Html::tag('li',
                        Html::a(
                            Html::tag('i', '', ['class' => 'fa fa-globe me-2']) . Module::t('Public'),
                            '#',
                            [
                                'class' => 'dropdown-item',
                                'onclick' => 'setAccessLevel("public")'
                            ]
                        )
                    ) .

                Html::endTag('ul') .

            Html::endTag('div') .

            Html::tag('small', Module::t('Only people with access can open it using this link'), [
                'id' => 'access-desc',
                'class' => 'access-desc text-muted d-block mt-1',
                'data-public' => Module::t('Anyone with the link can view the content'),
                'data-private' => Module::t('Only people with access can open it using this link')
            ]),
            ['class' => 'ms-3']
        ),
        ['class' => 'd-flex align-items-center']
    ),
    ['class' => 'file-access mb-3']
);




    Modal::end();
}
else
    Yii::$app->session->setFlash('error', Module::t('File not found!'));
?>

<script>
function setAccessLevel(level) {
    const accessText = document.getElementById('access-text');
    const accessDesc = document.getElementById('access-desc');
    const accessIcon = document.getElementById('access-icon');

    if (!accessText || !accessDesc || !accessIcon) return;

    accessText.textContent = accessText.dataset[level];
    accessDesc.textContent = accessDesc.dataset[level];

    if (level === 'public') {
        accessIcon.classList.remove('fa-lock');
        accessIcon.classList.add('fa-globe');
    } else {
        accessIcon.classList.remove('fa-globe');
        accessIcon.classList.add('fa-lock');
    }
}
</script>

