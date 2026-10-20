<?php

namespace portalium\storage\controllers\api;

use novavision\app\models\App;
use Yii;
use portalium\storage\Module;
use portalium\storage\models\Storage;
use portalium\storage\models\StorageSearch;
use portalium\rest\ActiveController as RestActiveController;
use yii\web\NotFoundHttpException;

class DefaultController extends RestActiveController
{
    public $modelClass = 'portalium\storage\models\Storage';

    protected function verbs()
    {
        $verbs = parent::verbs();
        $verbs['update'] = ['PUT', 'PATCH', 'POST'];
        return $verbs;
    }

    public function actions()
    {
        $actions = parent::actions();
        $actions['index']['dataFilter'] = [
            'class' => \yii\data\ActiveDataFilter::class,
            'searchModel' => StorageSearch::class,
        ];

        // Users with only the "Own" permission see just their own files and files in the active workspace.
        $actions['index']['prepareSearchQuery'] = function ($query) {
            if (!Yii::$app->user->can('storageApiDefaultIndex', ['id_module' => 'storage'])) {
                $query->andWhere(['or',
                    ['id_user' => Yii::$app->user->id],
                    ['id_workspace' => Yii::$app->workspace->id],
                ]);
            }
            return $query;
        };

        // Unset default actions to use our custom ones with permission checks
        unset($actions['view']);
        unset($actions['update']);
        unset($actions['delete']);

        return $actions;
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // Custom auth behavior - check file access level before requiring auth
        $behaviors['authenticator']['except'] = ['get-file', 'view'];
        
        return $behaviors;
    }

    public function beforeAction($action)
    {
        // For get-file and view actions, check if file is public first
        if (in_array($action->id, ['get-file', 'view'])) {
            $id = Yii::$app->request->get('id');
            if ($id) {
                $file = Storage::findOne($id);
                
                // If file is private (or not found), require authentication
                if (!$file || $file->access == Storage::ACCESS_PRIVATE) {
                    // Try to get token from multiple sources
                    $token = Yii::$app->request->get('access-token') 
                          ?: Yii::$app->request->get('access_token')
                          ?: Yii::$app->request->getHeaders()->get('Authorization');
                    
                    // If Authorization header exists, extract token
                    if ($token && strpos($token, 'Bearer ') === 0) {
                        $token = substr($token, 7); // Remove "Bearer " prefix
                    }
                    
                    if ($token) {
                        // Try to authenticate using the token
                        $user = \portalium\user\models\User::findIdentityByAccessToken($token);
                        if ($user) {
                            Yii::$app->user->login($user);
                        } else {
                            // Check if it's an app token
                            $appModel = App::find()->where(['api_key' => $token])->one();
                            if (!$appModel) {
                                throw new \yii\web\UnauthorizedHttpException(Module::t('Invalid access token.'));
                            }
                            // App token is valid, will be checked in action
                        }
                    } else {
                        // No token provided for private file
                        throw new \yii\web\UnauthorizedHttpException(Module::t('Authentication required for private files.'));
                    }
                }
                // If file is public, no authentication needed - skip to action
            }
        }
        
        if (!parent::beforeAction($action)) {
            return false;
        }

        switch ($action->id) {
            case 'view':
            case 'update':
            case 'delete':
            case 'get-file':
                break;
            case 'create':
                if (!Yii::$app->user->can('storageApiDefaultCreate'))
                    throw new \yii\web\ForbiddenHttpException(Module::t('You do not have permission to create this storage.'));
                break;
            case 'upload':
                break;
            default:
                if (
                    !Yii::$app->user->can('storageApiDefaultIndex', ['id_module' => 'storage']) &&
                    !Yii::$app->user->can('storageApiDefaultIndexOwn', ['id_module' => 'storage'])
                )
                    throw new \yii\web\ForbiddenHttpException(Module::t('You do not have permission to view this storage.'));
                break;
        }

        return true;
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);

        // Public files can be accessed by anyone (including guests)
        if ($model->access == \portalium\storage\models\Storage::ACCESS_PUBLIC) {
            return $model;
        }

        // Check global permissions
        $hasGlobalPermission = \Yii::$app->user->can('storageApiDefaultView')
            || \Yii::$app->user->can('storageApiDefaultViewOwn', ['model' => $model])
            || \Yii::$app->workspace->can('storage', 'storageApiDefaultView', ['model' => $model]);

        // Check share permissions - VIEW permission is enough
        $hasSharePermission = \portalium\storage\models\StorageShare::hasAccess(
            \Yii::$app->user->id,
            $model,
            null,
            \portalium\storage\models\StorageShare::PERMISSION_VIEW
        );

        if (!$hasGlobalPermission && !$hasSharePermission) {
            $appModel = App::find()->where(['api_key' => Yii::$app->request->get('access-token')])->one();
            if (!$appModel)
                throw new \yii\web\ForbiddenHttpException(Module::t('You do not have permission to view this storage.'));
        }

        // Additional check for private files
        $isOwner = $model->id_user == \Yii::$app->user->id;

        if (!$isOwner && !$hasGlobalPermission && !$hasSharePermission && (!isset($appModel) || $appModel === null)) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You do not have permission to view this private file.'));
        }

        return $model;
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        // Check global permissions
        $hasGlobalPermission = \Yii::$app->user->can('storageApiDefaultUpdate')
            || \Yii::$app->user->can('storageApiDefaultUpdateOwn', ['model' => $model])
            || \Yii::$app->workspace->can('storage', 'storageApiDefaultUpdate', ['model' => $model]);

        // Check share permissions - need EDIT or MANAGE permission
        $hasSharePermission = \portalium\storage\models\StorageShare::hasAccess(
            \Yii::$app->user->id,
            $model,
            null,
            \portalium\storage\models\StorageShare::PERMISSION_EDIT
        );

        if (!$hasGlobalPermission && !$hasSharePermission) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You do not have permission to update this storage.'));
        }

        // Additional check for private files
        if ($model->access == \portalium\storage\models\Storage::ACCESS_PRIVATE) {
            $isOwner = $model->id_user == \Yii::$app->user->id;

            if (!$isOwner && !$hasGlobalPermission && !$hasSharePermission) {
                throw new \yii\web\ForbiddenHttpException(Module::t('You do not have permission to update this private file.'));
            }
        }

        // Load and validate request data
        $model->load(\Yii::$app->request->getBodyParams(), '');

        // Handle file upload if a new file is provided
        $uploadedFile = \yii\web\UploadedFile::getInstanceByName('file');
        $isImageUpload = false;

        if ($uploadedFile) {
            $provider = \portalium\storage\providers\StorageProviderFactory::getDefault();
            $oldProvider = $model->getProvider();
            $oldFileName = $model->name;
            $oldThumbName = $model->thumbnail;

            $filename = md5(rand()) . '.' . $uploadedFile->extension;
            $hash = md5_file($uploadedFile->tempName);

            if ($provider->write($filename, $uploadedFile->tempName)) {
                // Remove the previous bytes only once the new ones are stored.
                if ($oldFileName) {
                    $oldProvider->delete($oldFileName);
                }
                if ($oldThumbName) {
                    $oldProvider->delete($oldThumbName);
                }

                $model->disk = $provider->getName();
                $model->id_provider = $provider->getProviderId();
                $model->name = $filename;
                $model->hash_file = $hash;
                $model->thumbnail = null;

                try {
                    $model->mime_type = Storage::MIME_TYPE[$model->getMIMEType($filename)];
                } catch (\Throwable $th) {
                    $model->mime_type = Storage::MIME_TYPE['video/mpeg'];
                }

                $isImageUpload = in_array(strtolower($uploadedFile->extension), ['jpg', 'jpeg', 'png']);
            }
        }

        if ($model->save()) {
            if ($isImageUpload) {
                $model->ensureThumbnail();
            }

            return $model;
        }

        return $model;
    }

    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        // Check global permissions
        $hasGlobalPermission = \Yii::$app->user->can('storageApiDefaultDelete')
            || \Yii::$app->user->can('storageApiDefaultDeleteOwn', ['model' => $model])
            || \Yii::$app->workspace->can('storage', 'storageApiDefaultDelete', ['model' => $model]);

        // Check share permissions - need MANAGE permission for deletion
        $hasSharePermission = \portalium\storage\models\StorageShare::hasAccess(
            \Yii::$app->user->id,
            $model,
            null,
            \portalium\storage\models\StorageShare::PERMISSION_MANAGE
        );

        if (!$hasGlobalPermission && !$hasSharePermission) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You do not have permission to delete this storage.'));
        }

        // Additional check for private files
        if ($model->access == \portalium\storage\models\Storage::ACCESS_PRIVATE) {
            $isOwner = $model->id_user == \Yii::$app->user->id;

            if (!$isOwner && !$hasGlobalPermission && !$hasSharePermission) {
                throw new \yii\web\ForbiddenHttpException(Module::t('You do not have permission to delete this private file.'));
            }
        }

        if ($model->isDirectory() ? $model->delete() : $model->deleteFile()) {
            \Yii::$app->response->statusCode = 204;
            return null;
        }

        throw new \yii\web\ServerErrorHttpException('Failed to delete the object for unknown reason.');
    }

    public function actionUpload()
    {
        // Check upload permission
        if (
            !\Yii::$app->user->can('storageApiDefaultUpload') &&
            !\Yii::$app->workspace->can('storage', 'storageApiDefaultUpload') &&
            !\Yii::$app->user->can('storageApiDefaultUploadOwn') &&
            !\Yii::$app->workspace->can('storage', 'storageApiDefaultUploadOwn')
        ) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to upload files.'));
        }

        $id_directory = Yii::$app->request->post('id_directory') ?: null;

        // Check directory permission if uploading to a specific directory
        if ($id_directory !== null) {
            $directoryModel = Storage::findOne(['id_storage' => $id_directory, 'type' => Storage::TYPE_DIRECTORY]);
            if ($directoryModel) {
                // Check global permissions for directory
                $hasGlobalDirPermission = \Yii::$app->user->can('storageApiDefaultManageDirectory')
                    || \Yii::$app->user->can('storageApiDefaultManageDirectoryOwn', ['model' => $directoryModel])
                    || \Yii::$app->workspace->can('storage', 'storageApiDefaultManageDirectory', ['model' => $directoryModel]);

                // Check share permissions for directory - need MANAGE permission
                $hasDirSharePermission = \portalium\storage\models\StorageShare::hasAccess(
                    \Yii::$app->user->id,
                    null,
                    $directoryModel,
                    \portalium\storage\models\StorageShare::PERMISSION_MANAGE
                );

                if (!$hasGlobalDirPermission && !$hasDirSharePermission) {
                    throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to upload to this directory.'));
                }
            }
        }

        $model = new \yii\base\DynamicModel([
            'file' => '',
            'title' => '',
        ]);
        $model->addRule('file', 'required');
        $model->addRule('title', 'string');
        $model->title = Yii::$app->request->post('title');
        $model->file = \yii\web\UploadedFile::getInstanceByName('file');
        try {
            if ($model->file && $model->validate()) {
                $provider = \portalium\storage\providers\StorageProviderFactory::getDefault();
                $filename = md5(rand()) . "." . $model->file->extension;
                $hash = md5_file($model->file->tempName);

                if ($provider->write($filename, $model->file->tempName)) {
                    $storage = new Storage();
                    $storage->disk = $provider->getName();
                    $storage->id_provider = $provider->getProviderId();
                    $storage->name = $filename;
                    $storage->title = $model->title;
                    $storage->id_workspace = Yii::$app->workspace->id;
                    $storage->id_directory = $id_directory;
                    $storage->hash_file = $hash;
                    $storage->thumbnail = null;
                    $storage->access = Storage::ACCESS_PRIVATE;

                    try {
                        $storage->mime_type = Storage::MIME_TYPE[$storage->getMIMEType($filename)];
                    } catch (\Throwable $th) {
                        $storage->mime_type = Storage::MIME_TYPE['video/mpeg'];
                    }

                    if ($storage->save(false)) {
                        if (in_array(strtolower($model->file->extension), ['jpg', 'jpeg', 'png'])) {
                            $storage->ensureThumbnail();
                        }

                        return $storage;
                    }

                    $provider->delete($filename);
                }
            }
        } catch (\Throwable $th) {
            Yii::error($th->getMessage(), __METHOD__);
        }

        return [
            'status' => 'FAIL',
            'message' => 'File upload failed.'
        ];
    }

    public function actionGetFile($id)
    {
        $model = Storage::findOne($id);

        if (!$model) {
            throw new NotFoundHttpException(Module::t('The requested file does not exist.'));
        }

        return \portalium\storage\helpers\StorageFileServer::serve($model, [
            'thumb'      => Yii::$app->request->get('type') === 'thumb',
            'permPrefix' => 'storageApiDefault',
            'appToken'   => Yii::$app->request->get('access-token'),
        ]);
    }

    protected function findModel($id)
    {
        if (($model = Storage::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Module::t('The requested storage does not exist.'));
    }
}
