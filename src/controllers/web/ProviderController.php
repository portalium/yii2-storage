<?php

namespace portalium\storage\controllers\web;

use Yii;
use portalium\storage\Module;
use portalium\storage\models\StorageProvider;
use portalium\storage\providers\StorageProviderFactory;
use portalium\web\Controller as WebController;
use yii\data\ActiveDataProvider;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class ProviderController extends WebController
{
    /**
     * Fields that hold credentials and must never be echoed back to the form.
     */
    const SECRET_FIELDS = ['secret'];

    /** Form value meaning "available to the whole site". */
    const SCOPE_SYSTEM = 'system';

    /** Form value meaning "available to this workspace only". */
    const SCOPE_WORKSPACE = 'workspace';

    /**
     * Providers the current workspace may use: its own plus the site's.
     */
    public function actionIndex()
    {
        if (
            !Yii::$app->user->can('storageWebProviderIndex')
            && !Yii::$app->workspace->can('storage', 'storageWebProviderIndex')
        ) {
            throw new ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        $query = StorageProvider::find()
            ->where(['or', ['id_workspace' => null], ['id_workspace' => Yii::$app->workspace->id]]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query->orderBy(['id_workspace' => SORT_ASC, 'name' => SORT_ASC]),
            'pagination' => ['pageSize' => 50],
        ]);

        return $this->render('index', ['dataProvider' => $dataProvider]);
    }

    /**
     * Every provider on the installation, across all workspaces.
     *
     * Separate from index() because seeing other workspaces' providers is an
     * administrative view, not something a workspace member should get just by
     * being able to manage their own.
     */
    public function actionManage()
    {
        if (!Yii::$app->user->can('storageWebProviderManage')) {
            throw new ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        $dataProvider = new ActiveDataProvider([
            'query' => StorageProvider::find()->orderBy(['id_workspace' => SORT_ASC, 'name' => SORT_ASC]),
            'pagination' => ['pageSize' => 50],
        ]);

        return $this->render('manage', ['dataProvider' => $dataProvider]);
    }

    public function actionCreate()
    {
        if (
            !Yii::$app->user->can('storageWebProviderCreate')
            && !Yii::$app->workspace->can('storage', 'storageWebProviderCreateOwn')
        ) {
            throw new ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        $model = new StorageProvider();
        $model->status = StorageProvider::STATUS_ACTIVE;

        // Default to the caller's workspace; the form may widen this to the
        // whole site when the permission allows it.
        $model->id_workspace = self::canDefineSystemProvider()
            ? null
            : Yii::$app->workspace->id;

        if ($this->handleForm($model)) {
            Yii::$app->session->setFlash('success', Module::t('Provider saved.'));

            return $this->redirect(['index']);
        }

        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if (
            !Yii::$app->user->can('storageWebProviderUpdate')
            && !Yii::$app->user->can('storageWebProviderUpdateOwn', ['model' => $model])
            && !Yii::$app->workspace->can('storage', 'storageWebProviderUpdate', ['model' => $model])
        ) {
            throw new ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        if ($this->handleForm($model)) {
            Yii::$app->session->setFlash('success', Module::t('Provider saved.'));

            return $this->redirect(['index']);
        }

        return $this->render('update', ['model' => $model]);
    }

    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        if (
            !Yii::$app->user->can('storageWebProviderDelete')
            && !Yii::$app->user->can('storageWebProviderDeleteOwn', ['model' => $model])
            && !Yii::$app->workspace->can('storage', 'storageWebProviderDelete', ['model' => $model])
        ) {
            throw new ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        $fileCount = $model->getFileCount();

        if ($fileCount > 0) {
            Yii::$app->session->setFlash('error', Module::t(
                'This provider still holds {count} file(s). Deactivate it instead of deleting it.',
                ['count' => $fileCount]
            ));

            return $this->redirect(['index']);
        }

        $model->delete();
        Yii::$app->session->setFlash('success', Module::t('Provider deleted.'));

        return $this->redirect(['index']);
    }

    /**
     * Runs a real write/read/delete round trip against the supplied settings.
     *
     * Credentials that authenticate but cannot write would otherwise only fail
     * at upload time, once the user has already trusted the provider.
     */
    public function actionTest()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (
            !Yii::$app->user->can('storageWebProviderCreate')
            && !Yii::$app->user->can('storageWebProviderUpdate')
            && !Yii::$app->workspace->can('storage', 'storageWebProviderCreateOwn')
        ) {
            throw new ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        $post = Yii::$app->request->post('StorageProvider', []);
        $id = Yii::$app->request->post('id_provider');

        $model = $id ? $this->findModel($id) : new StorageProvider();
        $model->type = $post['type'] ?? StorageProvider::TYPE_S3;
        $model->name = $post['name'] ?? 'test';
        $this->applyConfig($model, $post['config'] ?? []);

        return self::runTest($model);
    }

    /**
     * @return array{success:bool,message:string}
     */
    public static function runTest(StorageProvider $model): array
    {
        if ($model->type === StorageProvider::TYPE_LOCAL) {
            $provider = new \portalium\storage\providers\LocalProvider();
        } else {
            $provider = new \portalium\storage\providers\S3Provider((string) $model->id_provider, $model);
        }

        $key = 'nv-provider-test-' . bin2hex(random_bytes(8)) . '.txt';
        $tmp = tempnam(sys_get_temp_dir(), 'nvtest_');
        file_put_contents($tmp, 'novavision provider test');

        try {
            if (!$provider->write($key, $tmp)) {
                return ['success' => false, 'message' => Module::t('Write failed. Check the credentials and that the key has write access.')];
            }

            $stream = $provider->readStream($key);

            if ($stream === false) {
                $provider->delete($key);

                return ['success' => false, 'message' => Module::t('The file was written but could not be read back.')];
            }

            $content = stream_get_contents($stream);
            fclose($stream);

            $provider->delete($key);

            if (trim((string) $content) !== 'novavision provider test') {
                return ['success' => false, 'message' => Module::t('The file was read back but its contents did not match.')];
            }

            return ['success' => true, 'message' => Module::t('Connection works: write, read and delete all succeeded.')];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        } finally {
            if (is_file($tmp)) {
                @unlink($tmp);
            }
        }
    }

    /**
     * Loads the posted form, tests the connection and saves only on success.
     */
    private function handleForm(StorageProvider $model): bool
    {
        if (!Yii::$app->request->isPost) {
            return false;
        }

        $post = Yii::$app->request->post('StorageProvider', []);

        $model->name = $post['name'] ?? $model->name;
        $model->type = $post['type'] ?? $model->type;
        $model->status = isset($post['status']) ? (int) $post['status'] : $model->status;
        $model->is_default = isset($post['is_default']) ? (int) $post['is_default'] : 0;
        $model->capability = StorageProvider::getCapabilityForType($model->type);

        if ($model->isNewRecord) {
            $model->id_user = Yii::$app->user->id;
        }

        $this->applyScope($model, $post);

        // A workspace provider may never be promoted to the system default,
        // since one workspace must not dictate a site-wide destination.
        if ($model->id_workspace !== null) {
            $model->is_default = 0;
        }

        $this->applyConfig($model, $post['config'] ?? []);

        if (!$model->validate()) {
            return false;
        }

        // Import sources are connected through OAuth, so there is nothing to
        // round-trip here yet; storage providers must prove they work.
        if ($model->capability === StorageProvider::CAPABILITY_STORAGE) {
            $result = self::runTest($model);

            if (!$result['success']) {
                $model->addError('config', Module::t('Connection test failed: {message}', ['message' => $result['message']]));

                return false;
            }
        }

        return $model->save(false);
    }

    /**
     * Whether the caller may publish a provider for the whole site.
     *
     * Kept separate from the create permission so that being allowed to add a
     * provider does not by itself mean being allowed to impose one on everyone.
     */
    public static function canDefineSystemProvider(): bool
    {
        return Yii::$app->user->can('storageWebProviderSystem');
    }

    /**
     * Applies the posted scope, refusing to widen it without the permission.
     */
    private function applyScope(StorageProvider $model, array $post): void
    {
        if (!self::canDefineSystemProvider()) {
            // Without the permission the provider always belongs to the
            // caller's workspace, whatever the request claims.
            if ($model->isNewRecord) {
                $model->id_workspace = Yii::$app->workspace->id;
            }

            return;
        }

        if (!array_key_exists('scope', $post)) {
            return;
        }

        $model->id_workspace = $post['scope'] === self::SCOPE_SYSTEM
            ? null
            : Yii::$app->workspace->id;
    }

    /**
     * Merges posted config over the stored one.
     *
     * Secret fields arrive blank when the user did not retype them, and a blank
     * value must keep the stored secret rather than erase it.
     */
    private function applyConfig(StorageProvider $model, array $posted): void
    {
        $existing = $model->getConfigData();
        $merged = $existing;

        foreach ($posted as $key => $value) {
            $value = is_string($value) ? trim($value) : $value;

            if (in_array($key, self::SECRET_FIELDS, true) && $value === '') {
                continue;
            }

            $merged[$key] = $value;
        }

        $model->setConfigData($merged);
    }

    protected function findModel($id)
    {
        $model = StorageProvider::findOne($id);

        if ($model === null) {
            throw new NotFoundHttpException(Module::t('The requested provider does not exist.'));
        }

        return $model;
    }
}
