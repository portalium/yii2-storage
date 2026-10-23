<?php

use portalium\storage\bundles\ProviderAsset;
use portalium\storage\controllers\web\ProviderController;
use portalium\storage\Module;
use portalium\storage\models\StorageProvider;
use portalium\theme\widgets\ActiveForm;
use portalium\theme\widgets\Panel;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model portalium\storage\models\StorageProvider */

ProviderAsset::register($this);

$config = $model->getConfigData();
$hasSecret = !empty($config['secret']);

$this->registerJs(
    'window.StorageProviderConfig = ' . \yii\helpers\Json::encode([
        'testUrl' => Url::to(['provider/test']),
        'idProvider' => $model->id_provider,
        'messages' => [
            'testing' => Module::t('Testing connection...'),
        ],
    ]) . ';',
    \yii\web\View::POS_HEAD
);
?>

<?php $form = ActiveForm::begin(['id' => 'provider-form']); ?>

<?php Panel::begin([
    'title' => $model->isNewRecord ? Module::t('New Provider') : Module::t('Edit Provider'),
    'actions' => [
        'footer' => [
            Html::button(Module::t('Test Connection'), [
                'class' => 'btn btn-info',
                'id' => 'provider-test-button',
            ]),
            Html::submitButton(Module::t('Save'), ['class' => 'btn btn-success']),
        ],
    ],
]) ?>

<?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

<?= $form->field($model, 'type')->dropDownList(StorageProvider::getTypes(), [
    'id' => 'provider-type',
]) ?>

<div id="provider-s3-fields">
    <?= $form->field($model, 'config[endpoint]')->textInput([
        'value' => $config['endpoint'] ?? '',
        'placeholder' => 'https://storage.googleapis.com',
    ])->label(Module::t('Endpoint'))->hint(Module::t('B2: https://s3.&lt;region&gt;.backblazeb2.com &middot; AWS: https://s3.&lt;region&gt;.amazonaws.com &middot; R2: https://&lt;account-id&gt;.r2.cloudflarestorage.com &middot; GCS: https://storage.googleapis.com')) ?>

    <?= $form->field($model, 'config[region]')->textInput([
        'value' => $config['region'] ?? '',
        'placeholder' => 'auto',
    ])->label(Module::t('Region'))->hint(Module::t('Must match the endpoint exactly. R2 and GCS use: auto')) ?>

    <?= $form->field($model, 'config[bucket]')->textInput([
        'value' => $config['bucket'] ?? '',
    ])->label(Module::t('Bucket')) ?>

    <?= $form->field($model, 'config[key]')->textInput([
        'value' => $config['key'] ?? '',
    ])->label(Module::t('Access Key ID'))->hint(Module::t('B2: keyID &middot; AWS/R2: Access Key ID &middot; GCS: HMAC Access ID')) ?>

    <?= $form->field($model, 'config[secret]')->passwordInput([
        'value' => '',
        'placeholder' => $hasSecret ? Module::t('Leave blank to keep the current value') : '',
    ])->label(Module::t('Secret Key'))->hint(Module::t('Never displayed again once saved.')) ?>

    <?= $form->field($model, 'config[prefix]')->textInput([
        'value' => $config['prefix'] ?? '',
    ])->label(Module::t('Key Prefix'))->hint(Module::t('Optional folder prefix inside the bucket.')) ?>
</div>

<?php if (ProviderController::canDefineSystemProvider()) : ?>
    <?= $form->field($model, 'scope')->dropDownList([
        ProviderController::SCOPE_SYSTEM => Module::t('System-wide (everyone)'),
        ProviderController::SCOPE_WORKSPACE => Module::t('This workspace only'),
    ], [
        'options' => [
            ($model->id_workspace === null
                ? ProviderController::SCOPE_SYSTEM
                : ProviderController::SCOPE_WORKSPACE) => ['selected' => true],
        ],
    ])->label(Module::t('Scope'))->hint(Module::t('A system-wide provider is offered to every user; a workspace provider only to its members.')) ?>
<?php endif; ?>

<?= $form->field($model, 'status')->dropDownList([
    StorageProvider::STATUS_ACTIVE => Module::t('Active'),
    StorageProvider::STATUS_PASSIVE => Module::t('Passive'),
])->hint(Module::t('A passive provider accepts no new uploads, but existing files stay readable.')) ?>

<div id="provider-test-result" class="mt-3"></div>

<?php Panel::end() ?>

<?php ActiveForm::end(); ?>
