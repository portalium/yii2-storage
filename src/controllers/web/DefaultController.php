<?php

namespace portalium\storage\controllers\web;

use portalium\storage\Module;
use portalium\web\Controller;
use portalium\storage\models\Storage;
use portalium\storage\models\StorageSearch;
use portalium\storage\helpers\StoragePermissionHelper;
use portalium\storage\helpers\StorageQueryService;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

use portalium\data\ActiveDataProvider;


class DefaultController extends Controller
{
    const DEFAULT_PAGE_SIZE = 24;

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // Update access settings without disrupting CORS settings
        if (isset($behaviors['access'])) {
            $behaviors['access']['except'] = [
                'get-file',
                'view-share',
                'copy-file',
                'share-file',
                'create-share',
                'update-share-permission',
                'revoke-share',
                'rename-file',
                'update-file',
                'delete-file',
                'rename-folder',
                'delete-folder',
                'share-directory',
                'share-full-storage'
            ];
        }
        $behaviors['corsGetFile'] = [
            'class' => \yii\filters\Cors::class,
            'only' => ['get-file'],
            'cors' => [
                'Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'OPTIONS'],
                'Access-Control-Allow-Credentials' => false,
                'Access-Control-Max-Age' => 3600,
            ],
        ];

        return $behaviors;
    }

    /**
     * Displays a list of storage files and directories.
     *
     * This action retrieves and displays files and directories based on user permissions,
     * with support for file picker mode and filtering by file extensions.
     *
     * @return string|\yii\web\Response The rendered view or AJAX response.
     * @throws \yii\web\ForbiddenHttpException If user lacks required permissions.
     */
    public function beforeAction($action)
    {
        // Disable CSRF validation for specific actions triggered from shared views
        if (in_array($action->id, ['create-share', 'copy-file', 'update-share-permission', 'revoke-share', 'delete-file', 'delete-folder'])) {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        if (!\Yii::$app->user->can('storageWebDefaultIndex') && !\Yii::$app->workspace->can('storage', 'storageWebDefaultIndex')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        $model = new Storage();
        $searchModel = new StorageSearch();
        $id_directory = Yii::$app->request->get('id_directory');
        $isPicker = Yii::$app->request->get('isPicker', false);
        $allowFolderSelection = (bool) Yii::$app->request->get('allowFolderSelection', false);

        $fileExtensions = Yii::$app->request->get('fileExtensions', []);
        $fileExtensions = StorageQueryService::normalizeFileExtensions($fileExtensions);


        $id_user = Yii::$app->user->id;
        $fileDataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $fileDataProvider->query->andWhere(['id_directory' => $id_directory]);

        $userWorkspaceIds = StorageQueryService::getUserWorkspaceIds($id_user);

        if (!$isPicker || !\Yii::$app->user->can('storageWebDefaultManage')) {
            StorageQueryService::applyFileShareConditions($fileDataProvider->query, $id_user, $userWorkspaceIds);
        }

        StorageQueryService::applyFileExtensionFilter($fileDataProvider->query, $fileExtensions);

        $fileDataProvider->pagination->pageSize = self::DEFAULT_PAGE_SIZE;

        $directoryQuery = Storage::find()
            ->where(['type' => Storage::TYPE_DIRECTORY])
            ->andWhere(['id_directory' => $id_directory])
            ->orderBy(['id_storage' => SORT_DESC]);

        if (!$isPicker || !\Yii::$app->user->can('storageWebDefaultManageDirectory')) {
            StorageQueryService::applyDirectoryShareConditions($directoryQuery, $id_user, $userWorkspaceIds);
        }

        $directoryDataProvider = new ActiveDataProvider([
            'query' => $directoryQuery,
            'pagination' => [
                'pageSize' => self::DEFAULT_PAGE_SIZE - 1,
            ],
        ]);

        if (Yii::$app->request->isPjax) {
            if (Yii::$app->request->get('_pjax') === '#pjax-flash-message') {
                return \portalium\site\widgets\FlashMessage::widget();
            }

            return $this->renderAjax('_item-list', [
                'directoryDataProvider' => $directoryDataProvider,
                'fileDataProvider' => $fileDataProvider,
                'isPicker' => $isPicker,
                'actionId' => "index",
                'allowFolderSelection' => $allowFolderSelection,
            ]);
        }

        return $this->render('index', [
            'model' => $model,
            'dataProvider' => $searchModel->search($this->request->queryParams),
            'fileDataProvider' => $fileDataProvider,
            'directoryDataProvider' => $directoryDataProvider,
            'isPicker' => $isPicker,
            'actionId' => 'index',
            'allowFolderSelection' => $allowFolderSelection,
        ]);
    }

    /**
     * Displays the management interface for storage files and directories.
     *
     * This action retrieves and displays files and directories with management capabilities,
     * based on user permissions. It supports file picker mode and filtering by file extensions.
     *
     * @return string|\yii\web\Response The rendered view or AJAX response.
     * @throws \yii\web\ForbiddenHttpException If user lacks required permissions.
     */
    public function actionManage()
    {
        if (!\Yii::$app->user->can('storageWebDefaultManage')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
        //       Storage::generateMissingThumbnails();
        $model = new Storage();
        $searchModel = new StorageSearch();
        $id_directory = Yii::$app->request->get('id_directory');
        $isPicker = Yii::$app->request->get('isPicker', false);

        $fileExtensions = Yii::$app->request->get('fileExtensions', []);
        $fileExtensions = StorageQueryService::normalizeFileExtensions($fileExtensions);

        $fileDataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $fileDataProvider->query->andWhere(['id_directory' => $id_directory]);

        StorageQueryService::applyFileExtensionFilter($fileDataProvider->query, $fileExtensions);

        $fileDataProvider->pagination->pageSize = self::DEFAULT_PAGE_SIZE;


        $directoryQuery = Storage::find()
            ->where(['type' => Storage::TYPE_DIRECTORY])
            ->andWhere(['id_directory' => $id_directory])
            ->orderBy(['id_storage' => SORT_DESC]);

        $directoryDataProvider = new ActiveDataProvider([
            'query' => $directoryQuery,
            'pagination' => [
                'pageSize' => self::DEFAULT_PAGE_SIZE - 1,
            ],
        ]);

        if (Yii::$app->request->isPjax) {
            if (Yii::$app->request->get('_pjax') === '#pjax-flash-message') {
                return \portalium\site\widgets\FlashMessage::widget();
            }

            return $this->renderAjax('_item-list', [
                'directoryDataProvider' => $directoryDataProvider,
                'fileDataProvider' => $fileDataProvider,
                'isPicker' => $isPicker,
                'actionId' => "manage"
            ]);
        }

        return $this->render('index', [
            'model' => $model,
            'dataProvider' => $searchModel->search($this->request->queryParams),
            'fileDataProvider' => $fileDataProvider,
            'directoryDataProvider' => $directoryDataProvider,
            'isPicker' => $isPicker,
            'actionId' => 'manage',
        ]);
    }

    /**
     * Handles the upload of files or folders to the storage.
     *
     * This action processes file or folder uploads, validates user permissions,
     * and manages the storage of uploaded items.
     *
     * @return string|\yii\web\Response The rendered view or AJAX response.
     * @throws \yii\web\ForbiddenHttpException If user lacks required permissions.
     */
    public function actionUploadFile()
    {
        $post = Yii::$app->request->post();
        $type = $post['Storage']['type'] ?? 'file';
        $model = new Storage();
        if ($type === 'folder') {
            $model->type = Storage::TYPE_DIRECTORY;
        }
        $id_directory = Yii::$app->request->post('id_directory') ?: null;
        $model->id_directory = $id_directory;
        $currentUserId = Yii::$app->user->id;

        if ($id_directory !== null) {
            $directoryModel = Storage::findOne(['id_storage' => $id_directory, 'type' => Storage::TYPE_DIRECTORY]);
            // Global + Own + Workspace permission check
            $hasGlobalPermission = \Yii::$app->user->can('storageWebDefaultUpload')
                || \Yii::$app->user->can('storageWebDefaultUploadOwn', ['model' => $directoryModel])
                || \Yii::$app->workspace->can('storage', 'storageWebDefaultUpload', ['model' => $directoryModel]);

            // Share edit permission check
            $hasSharePermission = \portalium\storage\models\StorageShare::hasAccess(
                $currentUserId,
                null,
                $directoryModel,
                \portalium\storage\models\StorageShare::PERMISSION_EDIT
            );

            if (!$hasGlobalPermission && !$hasSharePermission)
                throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        } else if (
            !\Yii::$app->user->can('storageWebDefaultUpload') &&
            !\Yii::$app->workspace->can('storage', 'storageWebDefaultUpload')
        )
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));

        if (Yii::$app->request->isPost) {
            $model->load($post);
            if (!empty($post['Storage']['allowedExtensions'])) {
                $allowedExt = json_decode($post['Storage']['allowedExtensions'], true);
                if (is_array($allowedExt))
                    $model->allowedExtensions = $allowedExt;
            }

            $uploadedFiles = UploadedFile::getInstancesByName('Storage[file]');
            $success = false;
            if ($type === 'folder') {
                if (empty($uploadedFiles))
                    $model->addError('file', Module::t('No files were uploaded'));
                else {
                    if (empty($model->name)) {
                        $firstFile = $uploadedFiles[0];
                        $model->name = explode('/', $firstFile->name)[0] ?? 'Uploaded Folder';
                    }
                    $success = $model->uploadFolder($uploadedFiles, $id_directory);
                }
            } else {
                if (empty($uploadedFiles)) {
                    $model->addError('file', Module::t('No files were uploaded'));
                } else {
                    $model->file = $uploadedFiles[0];
                    if (!empty($post['Storage']['title'])) {
                        $info = pathinfo(trim($post['Storage']['title']));
                        $extension = isset($info['extension']) ? '.' . $info['extension'] : '';

                        $filename = $info['filename'];

                        if (preg_match('/^(.*)\((\d+)\)$/', $filename, $matches)) {
                            $filename = $matches[1];
                        }

                        if (!Storage::find()->where([
                            'title' => $filename . $extension,
                            'id_directory' => $id_directory,
                            'id_user' => $currentUserId
                        ])->exists()) {
                            $model->title = $filename . $extension;
                        } else {
                            $counter = 1;
                            $newTitle = "{$filename} ({$counter}){$extension}";
                            while (Storage::find()->where([
                                'title' => $newTitle,
                                'id_directory' => $id_directory,
                                'id_user' => $currentUserId
                            ])->exists()) {
                                $counter++;
                                $newTitle = "{$filename} ({$counter}){$extension}";
                            }
                            $model->title = $newTitle;
                        }
                    }


                    if (!Storage::find()->where(['title' => $filename . $extension, 'id_directory' => $id_directory])->exists())
                        $model->title = $filename . $extension;
                    else {
                        $counter = 1;
                        $newTitle = "{$filename} ({$counter}){$extension}";
                        while (Storage::find()->where(['title' => $newTitle, 'id_directory' => $id_directory])->exists()) {
                            $counter++;
                            $newTitle = "{$filename} ({$counter}){$extension}";
                        }
                        $model->title = $newTitle;
                    }
                }
                $hash_file = md5_file($model->file->tempName);
                $model->hash_file = $hash_file;
                $success = $model->upload();
            }

            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                return $success
                    ? ['success' => true]
                    : ['success' => false, 'errors' => $model->errors];
            }
        }

        return $this->renderAjax('_upload-file', [
            'model' => $model,
        ]);
    }


    /**
     * Handles the download of a storage file.
     *
     * This action checks user permissions and initiates the download of the specified file.
     *
     * @return \yii\web\Response The file download response.
     * @throws \yii\web\ForbiddenHttpException If user lacks required permissions.
     */
    public function actionDownloadFile()
    {
        $id = Yii::$app->request->post('id');
        $file = Storage::findOne($id);

        $hasGlobalPermission = \Yii::$app->user->can('storageWebDefaultDownloadFile')
            || \Yii::$app->user->can('storageWebDefaultDownloadFileOwn', ["model" => $file])
            || \Yii::$app->workspace->can('storage', 'storageWebDefaultIndex', ['model' => $file]);

        $hasSharePermission = \portalium\storage\models\StorageShare::hasAccess(
            \Yii::$app->user->id,
            $file,
            null,
            \portalium\storage\models\StorageShare::PERMISSION_VIEW
        );

        if (!$hasGlobalPermission && !$hasSharePermission) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        if (!$file) {
            Yii::$app->session->setFlash('error', Module::t('File not found!'));
            return;
        }

        return \portalium\storage\helpers\StorageFileServer::serve($file);
    }

    /**
     * Handles the renaming of a storage file.
     *
     * This action checks user permissions and processes the renaming of the specified file.
     *
     * @param int $id The ID of the file to rename.
     * @return string|\yii\web\Response The rendered view or AJAX response.
     * @throws \yii\web\ForbiddenHttpException If user lacks required permissions.
     */
    public function actionRenameFile($id)
    {
        $model = Storage::findOne($id);
        $id_share = Yii::$app->request->get('id_share') ?: Yii::$app->request->post('id_share');

        $hasGlobalPermission = \Yii::$app->user->can('storageWebDefaultRenameFile')
            || \Yii::$app->user->can('storageWebDefaultRenameFileOwn', ["model" => $model])
            || \Yii::$app->workspace->can('storage', 'storageWebDefaultRenameFile', ['model' => $model]);

        $hasSharePermission = false;
        if ($id_share) {
            $share = \portalium\storage\models\StorageShare::findOne($id_share);
            if (
                $share && $share->isValid() &&
                ($share->permission_level == \portalium\storage\models\StorageShare::PERMISSION_EDIT ||
                    $share->permission_level == \portalium\storage\models\StorageShare::PERMISSION_MANAGE)
            ) {
                $hasSharePermission = true;
            }
        }

        if (!$hasGlobalPermission && !$hasSharePermission) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        if (!$model) {
            Yii::$app->session->setFlash('error', Module::t('File not found!'));
            return '';
        }
        $storagePath = Yii::getAlias('@app') . '/../' . Yii::$app->setting->getValue('storage::path');
        $filePath = $storagePath . '/' . $model->name;
        if (!file_exists($filePath)) {
            Storage::deleteAll(['id_storage' => $model->id_storage]);
            Yii::$app->session->setFlash('error', Module::t('File not found!'));
            return '';
        }
        if (Yii::$app->request->isPost) {
            if ($model->load(Yii::$app->request->post()) && $model->save())
                Yii::$app->session->setFlash('success', Module::t('File renamed successfully!'));
            else
                Yii::$app->session->setFlash('error', Module::t('File name could not be changed!'));
        }

        return $this->renderPartial('_rename-file', ['model' => $model]);
    }

    /**
     * Handles the updating of a storage file.
     *
     * This action checks user permissions and processes the updating of the specified file.
     *
     * @param int $id The ID of the file to update.
     * @return string|\yii\web\Response The rendered view or AJAX response.
     * @throws \yii\web\ForbiddenHttpException If user lacks required permissions.
     */
    public function actionUpdateFile($id)
    {
        $model = Storage::findOne($id);
        $id_share = Yii::$app->request->get('id_share') ?: Yii::$app->request->post('id_share');

        $hasGlobalPermission = \Yii::$app->user->can('storageWebDefaultUpdateFile')
            || \Yii::$app->user->can('storageWebDefaultUpdateFileOwn', ["model" => $model])
            || \Yii::$app->workspace->can('storage', 'storageWebDefaultUpdateFile', ['model' => $model]);

        $hasSharePermission = false;
        if ($id_share) {
            $share = \portalium\storage\models\StorageShare::findOne($id_share);
            if (
                $share && $share->isValid() &&
                ($share->permission_level == \portalium\storage\models\StorageShare::PERMISSION_EDIT ||
                    $share->permission_level == \portalium\storage\models\StorageShare::PERMISSION_MANAGE)
            ) {
                $hasSharePermission = true;
            }
        }

        if (!$hasGlobalPermission && !$hasSharePermission) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        if (!$model) {
            Yii::$app->session->setFlash('error', Module::t('File not found!'));
            return '';
        }

        $storagePath = Yii::getAlias('@app') . '/../' . Yii::$app->setting->getValue('storage::path');
        $oldFilePath = $storagePath . '/' . $model->name;

        if (!file_exists($oldFilePath)) {
            Storage::deleteAll(['id_storage' => $model->id_storage]);
            Yii::$app->session->setFlash('error', Module::t('File not found!'));
            return '';
        }

        if (Yii::$app->request->isPost) {
            $model->file = UploadedFile::getInstance($model, 'file');

            if ($model->file) {
                $path = realpath(Yii::getAlias('@app') . '/../data');
                $newFileName = md5(rand()) . '.' . $model->file->extension;
                $hash = md5_file($model->file->tempName);

                if ($model->file->saveAs($path . '/' . $newFileName)) {

                    if (file_exists($oldFilePath)) {
                        @unlink($oldFilePath);
                    }

                    $model->name = $newFileName;
                    $model->hash_file = $hash;
                    $model->mime_type = Storage::MIME_TYPE[$model->getMIMEType($path . '/' . $newFileName)];
                    $model->date_update = date('Y-m-d H:i:s');

                    if (in_array($model->file->extension, ['jpg', 'jpeg', 'png'])) {
                        if (!empty($model->thumbnail)) {
                            $oldThumbPath = $path . '/' . $model->thumbnail;
                            if (file_exists($oldThumbPath)) {
                                @unlink($oldThumbPath);
                            }
                        }

                        $thumbName = 'thumb_' . $newFileName;
                        $thumbPath = $path . '/' . $thumbName;

                        if ($model->generateThumbnail($path . '/' . $newFileName, $thumbPath)) {
                            $model->thumbnail = $thumbName;
                        } else {
                            $model->thumbnail = null;
                        }
                    } else {
                        $model->thumbnail = null;
                    }

                    if ($model->save(false)) {
                        Yii::$app->session->setFlash('success', Module::t('File updated successfully!'));
                    } else {
                        Yii::$app->session->setFlash('error', Module::t('File update failed!'));
                    }
                } else {
                    Yii::$app->session->setFlash('error', Module::t('File could not be saved!'));
                }
            } else {
                Yii::$app->session->setFlash('error', Module::t('Invalid file format!'));
            }
        }

        return $this->renderPartial('_update', ['model' => $model]);
    }

    /**
     * Handles the updating of a storage file's access level.
     *
     * This action checks user permissions and processes the updating of the specified file's access level.
     *
     * @return array The JSON response indicating success or failure of the operation.
     * @throws \yii\web\ForbiddenHttpException If user lacks required permissions.
     */
    public function actionUpdateAccess()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $id = Yii::$app->request->post('id');
        $access = Yii::$app->request->post('access');

        $model = \portalium\storage\models\Storage::findOne($id);
        if (!$model) {
            return ['success' => false, 'message' => 'File not found'];
        }

        if (
            !\Yii::$app->user->can('storageWebDefaultShareFile') &&
            !\Yii::$app->user->can('storageWebDefaultShareFileOwn', ["model" => $model]) &&
            !\Yii::$app->workspace->can('storage', 'storageWebDefaultShareFile', ['model' => $model])
        ) {
            return ['success' => false, 'message' => 'You do not have permission'];
        }

        $model->access = ($access === 'public') ? $model::ACCESS_PUBLIC : $model::ACCESS_PRIVATE;

        if ($model->save(false)) {
            return ['success' => true, 'message' => 'Access level updated'];
        } else {
            return ['success' => false, 'message' => 'Error occurred while saving'];
        }
    }

    /**
     * Share a file
     * This action checks user permissions and renders the sharing interface for the specified file.
     * @param int $id The ID of the file to share.
     * @return string|\yii\web\Response The rendered view or AJAX response.
     * @throws \yii\web\ForbiddenHttpException If user lacks required permissions.
     */
    public function actionShareFile($id)
    {
        $model = Storage::findOne($id);
        if (!$model) {
            throw new \yii\web\NotFoundHttpException(Module::t('File not found!'));
        }

        $id_share = Yii::$app->request->get('id_share');

        $hasGlobalPermission = \Yii::$app->user->can('storageWebDefaultShareFile')
            || \Yii::$app->user->can('storageWebDefaultShareFileOwn', ["model" => $model])
            || \Yii::$app->workspace->can('storage', 'storageWebDefaultShareFileOwn', ['model' => $model]);

        $hasManageSharePermission = false;
        if ($id_share) {
            $share = \portalium\storage\models\StorageShare::findOne($id_share);
            if ($share && $share->isValid() && $share->permission_level == \portalium\storage\models\StorageShare::PERMISSION_MANAGE) {
                $hasManageSharePermission = true;
            }
        } else {
            $hasManageSharePermission = \portalium\storage\models\StorageShare::hasAccess(
                \Yii::$app->user->id,
                $model,
                null,
                \portalium\storage\models\StorageShare::PERMISSION_MANAGE
            );
        }

        if (!$hasGlobalPermission && !$hasManageSharePermission) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        return $this->renderPartial('_share', [
            'model' => $model,
            'shareType' => 'file',
        ]);
    }

    /**
     * Share a directory (folder)
     */
    public function actionShareDirectory($id)
    {
        $directory = Storage::findOne(['id_storage' => $id, 'type' => Storage::TYPE_DIRECTORY]);
        $id_share_context = Yii::$app->request->get('id_share');

        $hasGlobalPermission = \Yii::$app->user->can('storageWebDefaultShareDirectory')
            || \Yii::$app->user->can('storageWebDefaultShareDirectoryOwn', ["model" => $directory])
            || \Yii::$app->workspace->can('storage', 'storageWebDefaultShareDirectory', ['model' => $directory]);

        $hasManageSharePermission = false;
        if ($id_share_context) {
            $contextShare = \portalium\storage\models\StorageShare::findOne($id_share_context);
            if ($contextShare && $contextShare->isValid() && $contextShare->permission_level == \portalium\storage\models\StorageShare::PERMISSION_MANAGE) {
                $hasManageSharePermission = true;
            }
        } else {
            $hasManageSharePermission = \portalium\storage\models\StorageShare::hasAccess(
                \Yii::$app->user->id,
                null,
                $directory,
                \portalium\storage\models\StorageShare::PERMISSION_MANAGE
            );
        }

        if (!$hasGlobalPermission && !$hasManageSharePermission) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        return $this->renderPartial('_share', [
            'directory' => $directory,
            'shareType' => 'directory',
        ]);
    }

    /**
     * Share full storage of a user
     */
    public function actionShareFullStorage($id = null)
    {
        $userId = $id ?? Yii::$app->user->id;

        if ($userId != Yii::$app->user->id && !\Yii::$app->user->can('storageWebDefaultShareFullStorage')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        return $this->renderPartial('_share', [
            'userId' => $userId,
            'shareType' => 'storage',
        ]);
    }


    /**
     * Copies an existing file in the storage.
     *
     * This action handles POST requests to duplicate a file. It verifies user permissions,
     * checks if the source file exists, and creates a copy of it.
     *
     * @throws \yii\web\BadRequestHttpException if the request is not POST
     * @throws \yii\web\ForbiddenHttpException if the user does not have permission to copy files
     *
     * @return void
     */
    public function actionCopyFile()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'message' => 'Only POST requests are accepted.'];
        }

        $id = Yii::$app->request->post('id');
        $id_share = Yii::$app->request->post('id_share');

        $sourceModel = Storage::findOne($id);
        if (!$sourceModel) {
            return ['success' => false, 'message' => 'Source file not found.'];
        }

        // Permission Check
        $hasSharePermission = false;
        if ($id_share) {
            $share = \portalium\storage\models\StorageShare::findOne($id_share);
            if ($share && $share->isValid() && $share->permission_level >= 2) {
                $hasSharePermission = true;
            }
        }

        if (!Yii::$app->user->can('storageWebDefaultCopyFile') && !$hasSharePermission) {
            return ['success' => false, 'message' => 'You do not have permission'];
        }

        // Copying Logic
        $path = Yii::getAlias('@app') . '/../' . Yii::$app->setting->getValue('storage::path');

        if ($sourceModel->isDirectory()) {
            // Folder copying (recursive)
            $result = $this->copyFolderRecursive($sourceModel, Yii::$app->user->id, $sourceModel->id_directory, $path, true);
            return $result;
        } else {
            // File copying
            $newModel = new Storage();
            $newModel->attributes = $sourceModel->attributes;
            $newModel->id_storage = null;
            $newModel->title = "Copy of " . $sourceModel->title;
            $newModel->id_user = Yii::$app->user->id;
            $newModel->date_create = date('Y-m-d H:i:s');
            $newModel->date_update = date('Y-m-d H:i:s');

            $newFileName = md5(microtime()) . '.' . pathinfo($sourceModel->name, PATHINFO_EXTENSION);

            if (copy($path . '/' . $sourceModel->name, $path . '/' . $newFileName)) {
                $newModel->name = $newFileName;
                if ($newModel->save()) {
                    return ['success' => true];
                }
            }

            return ['success' => false, 'message' => 'File copy failed.'];
        }
    }

    /**
     * Deletes a file or folder from storage.
     *
     * @return array|void
     * @throws \yii\web\ForbiddenHttpException
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionDeleteFile()
    {
        // Force JSON response for AJAX requests
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        }

        if (!Yii::$app->request->isPost) {
            throw new \yii\web\BadRequestHttpException('Only POST requests are allowed.');
        }

        $fileId = Yii::$app->request->post('id');
        $id_share = Yii::$app->request->post('id_share') ?: Yii::$app->request->get('id_share');

        // FALLBACK: If JavaScript fails to send id_share, try to extract it from HTTP_REFERER
        if (!$id_share && isset($_SERVER['HTTP_REFERER'])) {
            $referer = $_SERVER['HTTP_REFERER'];
            $parsedUrl = parse_url($referer);
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
                if (isset($queryParams['token']) && strpos($parsedUrl['path'], 'view-share') !== false) {
                    $refShare = \portalium\storage\models\StorageShare::findOne(['share_token' => $queryParams['token']]);
                    $id_share = $refShare ? $refShare->id_share : null;
                }
            }
        }

        if (!$fileId) {
            return ['success' => false, 'message' => Module::t('File ID is required!')];
        }

        $file = Storage::findOne($fileId);

        if (!$file) {
            // Boss requirement: Display specific error message when not found
            return ['success' => false, 'message' => Module::t('Folder/File cannot be reached.')];
        }

        // Determine item type for specific permission check
        $isFolder = ($file->type == Storage::TYPE_DIRECTORY);
        $globalPermission = $isFolder ? 'storageWebDefaultDeleteFolder' : 'storageWebDefaultDeleteFile';

        // 1. Check Global & Workspace Permissions (Portalium Standard)
        $hasGlobalPermission = \Yii::$app->user->can($globalPermission)
            || \Yii::$app->user->can($globalPermission . 'Own', ["model" => $file])
            || \Yii::$app->workspace->can('storage', $globalPermission, ['model' => $file]);

        // 2. Check Share Permissions
        $hasSharePermission = false;
        if ($id_share) {
            $share = \portalium\storage\models\StorageShare::findOne($id_share);
            // Allow deletion if the share is valid and grants EDIT or MANAGE access
            if ($share && $share->isValid() && in_array($share->permission_level, [
                \portalium\storage\models\StorageShare::PERMISSION_EDIT,
                \portalium\storage\models\StorageShare::PERMISSION_MANAGE
            ])) {
                $hasSharePermission = true;
            }
        } else {
            // 3. Last Resort: Use Portalium's internal access checker if id_share is completely missing
            $hasSharePermission = \portalium\storage\models\StorageShare::hasAccess(
                \Yii::$app->user->id,
                $file,
                null,
                \portalium\storage\models\StorageShare::PERMISSION_MANAGE
            ) || \portalium\storage\models\StorageShare::hasAccess(
                \Yii::$app->user->id,
                $file,
                null,
                \portalium\storage\models\StorageShare::PERMISSION_EDIT
            );
        }

        // Deny access if no valid permissions are found
        if (!$hasGlobalPermission && !$hasSharePermission) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        // EXECUTE DELETION
        if ($isFolder) {
            // Recursively delete folder contents, passing the bypass flag for share links
            $this->deleteFolderRecursive($file, $hasSharePermission);
            Yii::$app->session->setFlash('success', Module::t('Folder has been deleted.'));
            return ['success' => true, 'message' => Module::t('Folder has been deleted.')];
        } else {
            $path = Yii::getAlias('@app') . '/../' . Yii::$app->setting->getValue('storage::path') . '/' . $file->name;

            if (!file_exists($path)) {
                Storage::deleteAll(['id_storage' => $file->id_storage]);
                return ['success' => false, 'message' => Module::t('Folder/File cannot be reached.')];
            }

            if ($file->deleteFile()) {
                Yii::$app->session->setFlash('success', Module::t('File has been deleted.'));
                return ['success' => true, 'message' => Module::t('File has been deleted.')];
            } else {
                return ['success' => false, 'message' => Module::t('File could not be deleted!')];
            }
        }
    }

    /**
     * Displays a modal picker for selecting files and directories from storage.
     *
     * This action renders a file/folder picker modal with filtering capabilities.
     * It applies permission checks and workspace restrictions based on user access.
     *
     * @return string The rendered HTML output of the picker modal
     * @throws \yii\web\ForbiddenHttpException When user lacks permission to access picker
     *
     * Query Parameters:
     * - id_directory: ID of the parent directory to browse
     * - fileExtensions: Array of allowed file extensions
     * - allowedExtensions: JSON string of additional allowed extensions
     * - isPicker: Whether this is a picker context (default: true)
     * - multiple: Allow multiple file selection (default: false)
     * - isJson: Return data as JSON (default: true)
     * - attributes: Array of attributes to return (default: ['id_storage'])
     * - allowFolderSelection: Allow selecting folders (default: false)
     */
    public function actionPickerModal()
    {
        if (!\Yii::$app->user->can('storageWebDefaultPickerModal') && !\Yii::$app->workspace->can('storage', 'storageWebDefaultPickerModal')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        $id_directory = Yii::$app->request->get('id_directory');
        $fileExtensions = Yii::$app->request->get('fileExtensions', []);
        $allowedExtensions = Yii::$app->request->get('allowedExtensions', []);
        $isPicker = Yii::$app->request->get('isPicker', true);
        $multiple = Yii::$app->request->get('multiple', false);
        $isJson = Yii::$app->request->get('isJson', true);
        $attributes = Yii::$app->request->get('attributes', ['id_storage']);
        $allowFolderSelection = (bool) Yii::$app->request->get('allowFolderSelection', false);
        $fileExtensions = StorageQueryService::normalizeFileExtensions($fileExtensions);

        if (is_string($allowedExtensions) && !empty($allowedExtensions)) {
            $allowedExtensions = json_decode($allowedExtensions, true) ?: [];
        }
        if (!is_array($allowedExtensions)) {
            $allowedExtensions = [];
        }

        $id_user = Yii::$app->user->id;
        $userWorkspaceIds = StorageQueryService::getUserWorkspaceIds($id_user);

        $query = Storage::find();
        $query->andWhere(['id_directory' => $id_directory]);

        StorageQueryService::applyFileExtensionFilter($query, $fileExtensions);

        if (!$isPicker || !\Yii::$app->user->can('storageWebDefaultManage')) {
            StorageQueryService::applyFileShareConditions($query, $id_user, $userWorkspaceIds);
        }

        $searchModel = new StorageSearch();
        $fileDataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $fileDataProvider->query = $query;
        $fileDataProvider->pagination->pageSize = self::DEFAULT_PAGE_SIZE;

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => self::DEFAULT_PAGE_SIZE,
            ],
            'sort' => [
                'defaultOrder' => ['id_storage' => SORT_DESC],
            ],
        ]);
        $directoryQuery = Storage::find()
            ->where(['type' => Storage::TYPE_DIRECTORY])
            ->andWhere(['id_directory' => $id_directory])
            ->orderBy(['id_storage' => SORT_DESC]);

        if (!$isPicker || !\Yii::$app->user->can('storageWebDefaultManageDirectory')) {
            StorageQueryService::applyDirectoryShareConditions($directoryQuery, $id_user, $userWorkspaceIds);
        }

        $directoryDataProvider = new ActiveDataProvider([
            'query' => $directoryQuery,
            'pagination' => [
                'pageSize' => self::DEFAULT_PAGE_SIZE - 1,
            ],
        ]);

        $dirAllQuery = Storage::find()
            ->where(['type' => Storage::TYPE_DIRECTORY])
            ->andWhere(['id_directory' => $id_directory])
            ->orderBy(['id_storage' => SORT_DESC]);
        if (!$isPicker || !\Yii::$app->user->can('storageWebDefaultManageDirectory')) {
            StorageQueryService::applyDirectoryShareConditions($dirAllQuery, $id_user, $userWorkspaceIds);
        }
        $directories = $dirAllQuery->all();

        $filesQuery = Storage::find()->andWhere(['id_directory' => $id_directory]);
        StorageQueryService::applyFileExtensionFilter($filesQuery, $fileExtensions);
        if (!$isPicker || !\Yii::$app->user->can('storageWebDefaultManage')) {
            StorageQueryService::applyFileShareConditions($filesQuery, $id_user, $userWorkspaceIds);
        }

        $files = $filesQuery->orderBy(['id_storage' => SORT_DESC])->all();

        $pagination = $dataProvider->getPagination();

        ob_start();
        echo $this->renderPartial('@portalium/storage/widgets/views/_picker-modal', [
            'dataProvider' => $dataProvider,
            'directoryDataProvider' => $directoryDataProvider,
            'directories' => $directories,
            'files' => $files,
            'pagination' => $pagination,
            'fileExtensions' => $fileExtensions,
            'allowedExtensions' => $allowedExtensions,
            'isPicker' => $isPicker,
            'multiple' => $multiple,
            'isJson' => $isJson,
            'attributes' => $attributes,
            'allowFolderSelection' => $allowFolderSelection,
        ]);
        $output = ob_get_clean();

        if ($isPicker) {
            $output = preg_replace(
                '#<link[^>]+href=["\']?/assets/[^"\']+/bootstrap\.css[^"\']*["\'][^>]*>#i',
                '',
                $output
            );
            $output = preg_replace(
                '#<link[^>]+href=["\']?/assets/[^"\']+/(font-awesome\.min\.css|site\.css|custom\.css|dashboard\.css|sidebar\.css|panel\.css|jquery\.js|yii\.js|bootstrap\.bundle\.js|tab\.js|jquery\.min\.js)[^"\']*["\'][^>]*>#i',
                '',
                $output
            );
            $output = preg_replace(
                '#<script[^>]+src=["\']?/assets/[^"\']+/(jquery\.js|yii\.js|bootstrap\.bundle\.js|tab\.js|jquery\.min\.js)[^"\']*["\'][^>]*>#i',
                '',
                $output
            );
        }

        return $output;
    }

    /**
     * Lists storage files with permission checks and filtering.
     * 
     * Retrieves a paginated list of storage files filtered by extension.
     * Requires 'storageWebDefaultFileList' permission.
     * 
     * @return string Rendered partial view with file list
     * @throws \yii\web\ForbiddenHttpException If user lacks required permissions
     */
    public function actionFileList()
    {
        if (!\Yii::$app->user->can('storageWebDefaultFileList') && !\Yii::$app->workspace->can('storage', 'storageWebDefaultFileList')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }


        $fileExtensions = Yii::$app->request->get('fileExtensions', []);
        $fileExtensions = StorageQueryService::normalizeFileExtensions($fileExtensions);

        $query = Storage::find();

        StorageQueryService::applyFileExtensionFilter($query, $fileExtensions);

        $dataProvider = new \portalium\data\ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => self::DEFAULT_PAGE_SIZE],
            'sort' => ['defaultOrder' => ['id_storage' => SORT_DESC]],
        ]);

        return $this->renderPartial('_file-list', [
            'dataProvider' => $dataProvider,
            'isPicker' => true,
        ]);
    }

    /**
     * Searches for files and directories based on query parameters.
     *
     * Checks user permissions for storage access. Filters files and directories
     * by search query, directory ID, and file extensions. Applies share conditions
     * if user lacks full index access.
     *
     * @return string Rendered partial view with file and directory data providers
     * @throws \yii\web\ForbiddenHttpException If user lacks search permission
     */
    public function actionSearch()
    {
        if (!\Yii::$app->user->can('storageWebDefaultSearch') && !\Yii::$app->workspace->can('storage', 'storageWebDefaultSearch')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
        Yii::$app->response->format = \yii\web\Response::FORMAT_HTML;

        $q = Yii::$app->request->get('q', '');
        $id_directory = Yii::$app->request->get('id_directory');
        $isPicker = Yii::$app->request->get('isPicker', false);
        $fileExtensions = Yii::$app->request->get('fileExtensions', []);
        $fileExtensions = StorageQueryService::normalizeFileExtensions($fileExtensions);

        $id_user = Yii::$app->user->id;
        $fileQuery = Storage::find();

        if (!empty($q)) {
            $fileQuery->andFilterWhere(['like', 'title', $q]);
        }
        if ($id_directory !== null) {
            $fileQuery->andWhere(['id_directory' => $id_directory]);
        }

        $userWorkspaceIds = StorageQueryService::getUserWorkspaceIds($id_user);

        if (
            !\Yii::$app->user->can('storageWebDefaultIndex') &&
            !\Yii::$app->workspace->can('storage', 'storageWebDefaultIndex')
        ) {
            StorageQueryService::applyFileShareConditions($fileQuery, $id_user, $userWorkspaceIds);
        }

        StorageQueryService::applyFileExtensionFilter($fileQuery, $fileExtensions);

        $fileDataProvider = new \yii\data\ActiveDataProvider([
            'query' => $fileQuery,
            'pagination' => ['pageSize' => self::DEFAULT_PAGE_SIZE],
            'sort' => ['defaultOrder' => ['id_storage' => SORT_DESC]],
        ]);

        $directoryQuery = Storage::find()->where(['type' => Storage::TYPE_DIRECTORY]);

        if ($id_directory !== null) {
            $directoryQuery->andWhere(['id_directory' => $id_directory]);
        } else {
            $directoryQuery->andWhere(['id_directory' => null]);
        }

        if (!empty($q)) {
            $directoryQuery->andFilterWhere(['like', 'name', $q]);
        }

        if (
            !\Yii::$app->user->can('storageWebDefaultIndex') &&
            !\Yii::$app->workspace->can('storage', 'storageWebDefaultIndex')
        ) {
            StorageQueryService::applyDirectoryShareConditions($directoryQuery, $id_user, $userWorkspaceIds);
        }

        $directoryDataProvider = new \yii\data\ActiveDataProvider([
            'query' => $directoryQuery,
            'pagination' => ['pageSize' => self::DEFAULT_PAGE_SIZE - 1],
            'sort' => ['defaultOrder' => ['id_storage' => SORT_DESC]],
        ]);

        return $this->renderPartial('_item-list', [
            'fileDataProvider' => $fileDataProvider,
            'directoryDataProvider' => $directoryDataProvider,
            'isPicker' => $isPicker,
        ]);
    }

    /**
     * Creates a new folder in the storage directory.
     *
     * This action handles the creation of new folders with permission checks.
     * It validates user permissions before allowing folder creation and handles
     * duplicate folder names by appending a counter.
     *
     * @return string Rendered view with the folder creation form
     * @throws \yii\web\ForbiddenHttpException if user lacks required permissions
     */
    public function actionNewFolder()
    {
        if (
            !\Yii::$app->user->can('storageWebDefaultNewFolder') &&
            !\Yii::$app->user->can('storageWebDefaultNewFolderOwn') &&
            !\Yii::$app->workspace->can('storage', 'storageWebDefaultNewFolder')
        ) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
        $model = new Storage();
        $model->type = Storage::TYPE_DIRECTORY;
        $model->mime_type = 0;

        if (Yii::$app->request->isPost) {
            if ($model->load(Yii::$app->request->post())) {
                $id_directory = Yii::$app->request->post('id_directory');
                if ($id_directory === 'null' || $id_directory == 0)
                    $model->id_directory = null;
                else {
                    $model->id_directory = $id_directory;
                    $directoryModel = Storage::findOne(['id_storage' => $id_directory, 'type' => Storage::TYPE_DIRECTORY]);
                    if (
                        !\Yii::$app->user->can('storageWebDefaultManageDirectory') &&
                        !\Yii::$app->user->can('storageWebDefaultManageDirectoryOwn', ['model' => $directoryModel]) &&
                        !\Yii::$app->workspace->can('storage', 'storageWebDefaultManageDirectory', ['model' => $directoryModel])
                    ) {
                        throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
                    }
                }

                $baseName = trim($model->name) !== '' ? $model->name : Module::t('New Folder');
                $name = $baseName;
                $counter = 1;

                while (Storage::find()
                    ->where(['type' => Storage::TYPE_DIRECTORY])
                    ->andWhere(['id_directory' => $model->id_directory, 'name' => $name])
                    ->exists()
                ) {
                    $name = $baseName . ' (' . $counter . ')';
                    $counter++;
                }

                $model->name = $name;

                if ($model->save()) {
                    Yii::$app->session->setFlash('success', Module::t('Folder created successfully!'));
                } else {
                    Yii::$app->session->setFlash('error', Module::t('Failed to create folder!'));
                }
            } else {
                Yii::$app->session->setFlash('error', Module::t('Failed to create folder!'));
            }
        }

        return $this->renderPartial('_new-folder', [
            'model' => $model
        ]);
    }

    /**
     * Renames a storage directory.
     *
     * This action handles renaming of storage directories with proper permission checks.
     * It verifies both global and share-based permissions before allowing the rename operation.
     * If a directory with the same name already exists in the parent directory, a counter
     * suffix is automatically appended to make the name unique.
     *
     * @param int $id The ID of the directory to rename
     * @return string The rendered partial view for the rename folder form
     * @throws \yii\web\ForbiddenHttpException If user lacks required permissions
     */
    public function actionRenameFolder($id)
    {
        $model = Storage::findOne(['id_storage' => $id, 'type' => Storage::TYPE_DIRECTORY]);

        $id_share = Yii::$app->request->get('id_share') ?: Yii::$app->request->post('id_share');

        $hasGlobalPermission = \Yii::$app->user->can('storageWebDefaultRenameFolder')
            || \Yii::$app->user->can('storageWebDefaultRenameFolderOwn', ["model" => $model])
            || \Yii::$app->workspace->can('storage', 'storageWebDefaultRenameFolder', ['model' => $model]);

        $hasSharePermission = false;
        if ($id_share) {
            $share = \portalium\storage\models\StorageShare::findOne($id_share);
            if ($share && $share->isValid() && $share->permission_level >= \portalium\storage\models\StorageShare::PERMISSION_EDIT) {
                $hasSharePermission = true;
            }
        }

        if (!$hasGlobalPermission && !$hasSharePermission) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
        if (!$model) {
            Yii::$app->session->setFlash('error', Module::t('Folder not found!'));
            return '';
        }
        if (Yii::$app->request->post()) {
            $oldName = $model->name;
            if ($model->load(Yii::$app->request->post()) && $model->validate()) {
                if ($oldName !== $model->name) {
                    $baseName = $model->name;
                    $name = $baseName;
                    $counter = 1;
                    while (Storage::find()
                        ->where(['type' => Storage::TYPE_DIRECTORY])
                        ->andWhere(['name' => $name, 'id_directory' => $model->id_directory])
                        ->andWhere(['<>', 'id_storage', $id])
                        ->exists()
                    ) {
                        $name = $baseName . ' (' . $counter . ')';
                        $counter++;
                    }
                    $model->name = $name;
                    if ($model->save()) {
                        Yii::$app->session->setFlash('success', Module::t('Folder renamed to "{name}"', ['name' => $model->name]));
                    } else {
                        Yii::$app->session->setFlash('error', Module::t('Folder name could not be changed in the database!'));
                    }
                } else {
                    Yii::$app->session->setFlash('error', Module::t('No changes were made to the folder name!'));
                }
            } else {
                Yii::$app->session->setFlash('error', Module::t('Folder name could not be changed!'));
            }
        }

        return $this->renderPartial('_rename-folder', ['model' => $model]);
    }

    /**
     * Deletes a folder and all its contents.
     *
     * @param int|null $id The ID of the folder to delete (can be passed via POST as well)
     * @param int|null $id_directory The parent directory ID (optional)
     * @return array JSON response with success status and message
     * @throws \yii\web\ForbiddenHttpException if user lacks permission
     */
    public function actionDeleteFolder($id = null, $id_directory = null)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // Extract folder ID from POST if not provided in URL
        $folderId = Yii::$app->request->post('id') ?: $id;
        $id_share = Yii::$app->request->post('id_share') ?: Yii::$app->request->get('id_share');

        // FALLBACK: Extract id_share from HTTP_REFERER if missing
        if (!$id_share && isset($_SERVER['HTTP_REFERER'])) {
            $referer = $_SERVER['HTTP_REFERER'];
            $parsedUrl = parse_url($referer);
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
                if (isset($queryParams['token']) && strpos($parsedUrl['path'], 'view-share') !== false) {
                    $refShare = \portalium\storage\models\StorageShare::findOne(['share_token' => $queryParams['token']]);
                    $id_share = $refShare ? $refShare->id_share : null;
                }
            }
        }

        $folder = Storage::findOne(['id_storage' => $folderId, 'type' => Storage::TYPE_DIRECTORY]);

        if (!$folder) {
            // Boss requirement
            Yii::$app->session->setFlash('error', Module::t('Folder cannot be reached.'));
            return ['success' => false, 'message' => Module::t('Folder cannot be reached.')];
        }

        // 1. Check Global Permissions
        $hasGlobalPermission = \Yii::$app->user->can('storageWebDefaultDeleteFolder')
            || \Yii::$app->user->can('storageWebDefaultDeleteFolderOwn', ["model" => $folder])
            || \Yii::$app->workspace->can('storage', 'storageWebDefaultDeleteFolder', ['model' => $folder]);

        // 2. Check Share Permissions
        $hasSharePermission = false;
        if ($id_share) {
            $share = \portalium\storage\models\StorageShare::findOne($id_share);
            if ($share && $share->isValid() && in_array($share->permission_level, [
                \portalium\storage\models\StorageShare::PERMISSION_EDIT,
                \portalium\storage\models\StorageShare::PERMISSION_MANAGE
            ])) {
                $hasSharePermission = true;
            }
        } else {
            // 3. Fallback to internal check
            $hasSharePermission = \portalium\storage\models\StorageShare::hasAccess(
                \Yii::$app->user->id,
                null,
                $folder,
                \portalium\storage\models\StorageShare::PERMISSION_MANAGE
            ) || \portalium\storage\models\StorageShare::hasAccess(
                \Yii::$app->user->id,
                null,
                $folder,
                \portalium\storage\models\StorageShare::PERMISSION_EDIT
            );
        }

        if (!$hasGlobalPermission && !$hasSharePermission) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        // Delete folder recursively
        $this->deleteFolderRecursive($folder, $hasSharePermission);

        // Boss requirement
        Yii::$app->session->setFlash('success', Module::t('Folder has been deleted.'));
        return ['success' => true, 'message' => Module::t('Folder has been deleted.')];
    }

    /**
     * Recursively deletes a folder and all its contents.
     *
     * Checks user permissions before deletion. Deletes all subfolders,
     * files, and the folder itself.
     *
     * @param Storage $folder The folder to delete (type=directory)
     * @param bool $bypassPermission If true, skips the internal RBAC check (used for valid share links)
     * @throws \yii\web\ForbiddenHttpException If user lacks delete permission
     */
    protected function deleteFolderRecursive($folder, $bypassPermission = false)
    {
        // Skip internal RBAC check if the action was authorized via a valid share link
        if (!$bypassPermission) {
            if (
                !\Yii::$app->user->can('storageWebDefaultDeleteFolderRecursive')
                && !\Yii::$app->user->can('storageWebDefaultDeleteFolderRecursiveOwn', ["model" => $folder])
                && !\Yii::$app->workspace->can('storage', 'storageWebDefaultDeleteFolderRecursive', ['model' => $folder])
            ) {
                throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
            }
        }

        // Recursively find and delete sub-folders
        $subFolders = Storage::findAll(['id_directory' => $folder->id_storage, 'type' => Storage::TYPE_DIRECTORY]);
        foreach ($subFolders as $subFolder) {
            $this->deleteFolderRecursive($subFolder, $bypassPermission);
        }

        // Find and physically delete files inside the current folder
        $files = Storage::findAll(['id_directory' => $folder->id_storage, 'type' => Storage::TYPE_FILE]);
        foreach ($files as $file) {
            $filePath = Yii::getAlias('@app') . '/../' . Yii::$app->setting->getValue('storage::path') . '/' . $file->name;
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            $file->delete();
        }

        // Remove the folder record from the database
        $folder->delete();
    }
    /**
     * Recursively copies a folder and all its contents (files and sub-folders).
     *
     * @param Storage $sourceFolder Source folder to copy
     * @param int $userId User ID for the new folder owner
     * @param int|null $targetDirectoryId Parent directory ID for the new folder
     * @param string $storagePath Absolute path to the storage directory on disk
     * @param bool $isTopLevel Whether this is the top-level folder being copied
     * @return array JSON response with success status
     */
    protected function copyFolderRecursive(Storage $sourceFolder, $userId, $targetDirectoryId, $storagePath, $isTopLevel = false)
    {
        $newFolder = new Storage();
        $newFolder->attributes = $sourceFolder->attributes;
        $newFolder->id_storage = null;
        $newFolder->id_user = $userId;
        $newFolder->id_directory = $targetDirectoryId;
        $newFolder->date_create = date('Y-m-d H:i:s');
        $newFolder->date_update = date('Y-m-d H:i:s');

        if ($isTopLevel) {
            $newFolder->name = 'Copy of ' . $sourceFolder->name;
            if (!empty($sourceFolder->title)) {
                $newFolder->title = 'Copy of ' . $sourceFolder->title;
            }
        }

        if (!$newFolder->save()) {
            return ['success' => false, 'message' => Module::t('Folder copy failed.')];
        }

        $newFolderId = $newFolder->id_storage;

        // Copy files inside the folder
        $files = Storage::findAll(['id_directory' => $sourceFolder->id_storage, 'type' => Storage::TYPE_FILE]);
        foreach ($files as $file) {
            $newFile = new Storage();
            $newFile->attributes = $file->attributes;
            $newFile->id_storage = null;
            $newFile->id_user = $userId;
            $newFile->id_directory = $newFolderId;
            $newFile->date_create = date('Y-m-d H:i:s');
            $newFile->date_update = date('Y-m-d H:i:s');

            $ext = pathinfo($file->name, PATHINFO_EXTENSION);
            $newFileName = md5(microtime() . rand(0, 10000)) . ($ext ? '.' . $ext : '');

            if (file_exists($storagePath . '/' . $file->name)) {
                if (copy($storagePath . '/' . $file->name, $storagePath . '/' . $newFileName)) {
                    $newFile->name = $newFileName;
                    $newFile->save();
                }
            }
        }

        // Recursively copy sub-folders
        $subFolders = Storage::findAll(['id_directory' => $sourceFolder->id_storage, 'type' => Storage::TYPE_DIRECTORY]);
        foreach ($subFolders as $subFolder) {
            $this->copyFolderRecursive($subFolder, $userId, $newFolderId, $storagePath, false);
        }

        return ['success' => true];
    }

    /**
     * Retrieves file attributes and access URL for a given file ID.
     *
     * Checks if the user has permission to access the file either through
     * global permissions or file share permissions. Returns file metadata
     * and download URL if authorized.
     *
     * @param int $id The file ID
     * @return array File attributes and URL, or error message if not found or unauthorized
     */
    public function actionGetFileAttributes($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $file = \portalium\storage\models\Storage::findOne($id);
        if (!$file) {
            return ['error' => 'File not found'];
        }

        $hasGlobalPermission = \Yii::$app->user->can('storageWebDefaultIndex')
            || \Yii::$app->workspace->can('storage', 'storageWebDefaultIndex', ['model' => $file]);

        $hasSharePermission = \portalium\storage\models\StorageShare::hasAccess(
            \Yii::$app->user->id,
            $file,
            null,
            \portalium\storage\models\StorageShare::PERMISSION_VIEW
        );

        if (!$hasGlobalPermission && !$hasSharePermission) {
            return ['error' => 'You are not allowed to access this file'];
        }

        return [
            'attributes' => [
                'id_storage' => $file->id_storage,
                'name' => $file->name,
                'title' => $file->title,
                'mime_type' => $file->mime_type,
                'icon_class_php' => $file->getIconClass(),
            ],
            'url' => \yii\helpers\Url::to(['/storage/default/get-file', 'id' => $file->id_storage]),
        ];
    }

    /**
     * Retrieves and serves a file from storage.
     *
     * @param int|null $id The ID of the file model
     * @param string|null $file_name The name of the file
     * @param string|null $access_token The access token (for authentication)
     * @return \yii\web\Response The response object with file content or error message
     * @throws \yii\web\ForbiddenHttpException If user does not have permission to access private files
     */
    public function actionGetFile($id = null, $file_name = null, $access_token = null)
    {
        if ($id !== null) {
            $model = Storage::findOne($id);
        } elseif ($file_name !== null) {
            $model = Storage::findOne(['name' => $file_name]);
        } else {
            $model = null;
        }

        if ($model === null) {
            Yii::$app->response->statusCode = 404;
            return Module::t('The requested file does not exist.');
        }

        return \portalium\storage\helpers\StorageFileServer::serve($model, [
            'thumb'      => Yii::$app->request->get('type') === 'thumb',
            'permPrefix' => 'storageWebDefault',
        ]);
    }


    /**
     * Track file access when preview modal is opened
     * This updates access_count and date_last_access
     */
    public function actionTrackAccess($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        try {
            $file = $this->findModel($id);

            $file->access_count = ($file->access_count ?? 0) + 1;
            $file->date_last_access = date('Y-m-d H:i:s');

            if ($file->save(false, ['access_count', 'date_last_access'])) {
                return ['success' => true];
            }

            return ['success' => false, 'message' => 'Failed to update access tracking'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Finds a Storage model by its ID.
     *
     * @param mixed $id The ID of the Storage model to find.
     * @return Storage The Storage model instance.
     * @throws NotFoundHttpException If the Storage model is not found.
     */
    protected function findModel($id)
    {
        if (($model = Storage::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException(Module::t('The requested file does not exist.'));
    }

    /**
     * Create a new share for file, directory, or full storage
     */
    public function actionCreateShare()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $post = Yii::$app->request->post();
        $share = new \portalium\storage\models\StorageShare();

        // Load data into the model
        if (isset($post['permission_level'])) {
            $share->load($post, '');
        } else {
            $share->load($post);
        }

        // --- FIX: Capture the ID from the frontend and assign it to the correct field ---
        $itemId = Yii::$app->request->post('id_storage') ?: Yii::$app->request->post('id');

        if ($itemId) {
            $item = Storage::findOne($itemId);
            if ($item) {
                if ($item->type == Storage::TYPE_DIRECTORY) {
                    $share->id_directory = $item->id_storage;
                    $share->id_storage = null; // If it's a folder, the file ID must be empty.
                } else {
                    $share->id_storage = $item->id_storage;
                    $share->id_directory = null; // If it's a file, the folder ID must be empty.
                }
            }
        }

        // Prevent conflicts: If the share is file/folder based, the owner must be null
        if ($share->id_storage || $share->id_directory) {
            $share->id_user_owner = null;
        }

        // For user/workspace shares: prevent duplicates — update permission if already exists
        if (in_array($share->shared_with_type, [
            \portalium\storage\models\StorageShare::TYPE_USER,
            \portalium\storage\models\StorageShare::TYPE_WORKSPACE,
        ]) && $share->id_shared_with) {
            $dupQuery = \portalium\storage\models\StorageShare::find()
                ->where([
                    'shared_with_type' => $share->shared_with_type,
                    'id_shared_with'   => $share->id_shared_with,
                    'is_active'        => 1,
                ]);

            if ($share->id_storage) {
                $dupQuery->andWhere(['id_storage' => $share->id_storage]);
            } elseif ($share->id_directory) {
                $dupQuery->andWhere(['id_directory' => $share->id_directory]);
            } elseif ($share->id_user_owner) {
                $dupQuery->andWhere(['id_user_owner' => $share->id_user_owner]);
            }

            $duplicate = $dupQuery->one();
            if ($duplicate) {
                $duplicate->permission_level = $share->permission_level;
                $duplicate->save(false);
                return ['success' => true, 'updated' => true];
            }
        }

        // For link shares: enforce one active link per item (Drive-style)
        if ($share->shared_with_type == \portalium\storage\models\StorageShare::TYPE_LINK) {
            $existingQuery = \portalium\storage\models\StorageShare::find()
                ->where(['shared_with_type' => \portalium\storage\models\StorageShare::TYPE_LINK, 'is_active' => 1])
                ->andWhere(['OR', ['expires_at' => null], ['>', 'expires_at', date('Y-m-d H:i:s')]]);

            if ($share->id_storage) {
                $existingQuery->andWhere(['id_storage' => $share->id_storage]);
            } elseif ($share->id_directory) {
                $existingQuery->andWhere(['id_directory' => $share->id_directory]);
            } elseif ($share->id_user_owner) {
                $existingQuery->andWhere(['id_user_owner' => $share->id_user_owner]);
            }

            $existing = $existingQuery->one();
            if ($existing) {
                return [
                    'success' => true,
                    'link'     => \yii\helpers\Url::to(['/storage/default/view-share', 'token' => $existing->share_token], true),
                    'share_id' => $existing->id_share,
                    'existing' => true,
                ];
            }

            $share->generateShareToken();
        }

        if ($share->save()) {
            return [
                'success' => true,
                'link'     => \yii\helpers\Url::to(['/storage/default/view-share', 'token' => $share->share_token], true),
                'share_id' => $share->id_share
            ];
        }

        return [
            'success' => false,
            'message' => 'Validation Error',
            'errors' => $share->getErrors()
        ];
    }
    /**
     * View a shared item via link
     */
    public function actionViewShare($token, $download = false, $file_id = null)
    {
        $share = \portalium\storage\models\StorageShare::findOne(['share_token' => $token]);

        if (!$share || $share->shared_with_type !== \portalium\storage\models\StorageShare::TYPE_LINK || !$share->isValid()) {
            throw new NotFoundHttpException(Module::t('The requested share link is invalid or has expired.'));
        }

        // --- PERMISSION CHECKS START ---
        // Default: No access to sensitive actions
        $hasEditAccess = false;
        $hasManageAccess = false;

        /**
         * SECURITY FIX: 
         * We only allow Edit and Manage actions if the user is logged into the system.
         * Guest users should only be able to view and download, regardless of the link's permission level.
         */
        if (!Yii::$app->user->isGuest) {
            if ($share->permission_level == \portalium\storage\models\StorageShare::PERMISSION_EDIT) {
                $hasEditAccess = true;
            } elseif ($share->permission_level == \portalium\storage\models\StorageShare::PERMISSION_MANAGE) {
                $hasEditAccess = true; // Users with Manage permission can also Edit.
                $hasManageAccess = true;
            }
        }
        // --- PERMISSION CHECKS END ---

        // If it's a direct file share, serve the file or render preview
        if ($share->id_storage !== null) {
            $file = Storage::findOne($share->id_storage);
            if (!$file) {
                throw new NotFoundHttpException(Module::t('File cannot be reached.'));
            }

            if ($download || Yii::$app->request->get('type') === 'thumb') {
                $file->access = Storage::ACCESS_PUBLIC; // bypass auth check for public share links
                return \portalium\storage\helpers\StorageFileServer::serve($file, [
                    'thumb' => Yii::$app->request->get('type') === 'thumb'
                ]);
            }

            // Render preview page with calculated permissions
            return $this->render('view-share', [
                'model' => $file,
                'share' => $share,
                'hasEditAccess' => $hasEditAccess,
                'hasManageAccess' => $hasManageAccess,
            ]);
        }
        if ($share->id_directory !== null) {
            $directory = Storage::findOne(['id_storage' => $share->id_directory, 'type' => Storage::TYPE_DIRECTORY]);
            if (!$directory) {
                throw new NotFoundHttpException(Module::t('Folder cannot be reached.'));
            }

            // If a specific file or subfolder is requested from this shared folder
            if ($file_id !== null) {
                $item = Storage::findOne(['id_storage' => $file_id]);
                if (!$item) {
                    throw new NotFoundHttpException(Module::t('The requested item does not exist.'));
                }

                $isInSharedFolder = false;
                $currentDirId = $item->id_directory;
                while ($currentDirId) {
                    if ($currentDirId == $directory->id_storage) {
                        $isInSharedFolder = true;
                        break;
                    }
                    $parent = Storage::findOne($currentDirId);
                    $currentDirId = $parent ? $parent->id_directory : null;
                }

                if (!$isInSharedFolder && $item->id_storage != $directory->id_storage) {
                    throw new \yii\web\ForbiddenHttpException(Module::t('You do not have permission to access this item.'));
                }

                if ($item->type === Storage::TYPE_FILE) {
                    $item->access = Storage::ACCESS_PUBLIC; // Bypass auth check since share is valid
                    return \portalium\storage\helpers\StorageFileServer::serve($item, [
                        'thumb' => Yii::$app->request->get('type') === 'thumb'
                    ]);
                } else {
                    // Item is a sub-directory, overwrite $directory to render its contents
                    $directory = $item;
                }
            }

            if ($download) {
                // Share link already validated — bypass StorageFileServer's own permission checks
                $storagePath = Yii::$app->basePath . '/../' . Yii::$app->setting->getValue('storage::path');
                try {
                    $tmpZip = \portalium\storage\helpers\StorageZipHelper::buildZip($directory, $storagePath);
                } catch (\RuntimeException $e) {
                    throw new \yii\web\ServerErrorHttpException($e->getMessage());
                }
                $zipName = preg_replace('/[^\w\-.]/', '_', $directory->name) . '.zip';

                Yii::$app->response->on(\yii\web\Response::EVENT_AFTER_SEND, function () use ($tmpZip) {
                    @unlink($tmpZip);
                });

                return Yii::$app->response->sendFile($tmpZip, $zipName, [
                    'mimeType' => 'application/zip',
                    'inline'   => false,
                ]);
            }

            $searchModel = new \portalium\storage\models\StorageSearch();

            $fileDataProvider = $searchModel->search(Yii::$app->request->queryParams);
            $fileDataProvider->query->andWhere(['id_directory' => $directory->id_storage]);

            $directoryDataProvider = new \portalium\data\ActiveDataProvider([
                'query' => Storage::find()
                    ->where(['type' => Storage::TYPE_DIRECTORY])
                    ->andWhere(['id_directory' => $directory->id_storage])
                    ->orderBy(['id_storage' => SORT_DESC]),
                'pagination' => [
                    'pageSize' => 24,
                ],
            ]);

            return $this->render('view-share-folder', [
                'model' => $directory,
                'fileDataProvider' => $fileDataProvider,
                'directoryDataProvider' => $directoryDataProvider,
                'share' => $share,
                'hasEditAccess' => $hasEditAccess,
                'hasManageAccess' => $hasManageAccess,
            ]);
        }

        return $this->redirect(['/storage/default/index']);
    }


    /**
     * Get all shares for a file, directory, or user storage
     */
    public function actionGetShares()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $id_storage = Yii::$app->request->get('id_storage');
        $id_directory = Yii::$app->request->get('id_directory');
        $id_user_owner = Yii::$app->request->get('id_user_owner');

        if ($id_storage) {
            $storage = Storage::findOne($id_storage);
            if (!$storage) {
                return ['success' => false, 'message' => Module::t('File not found!')];
            }

            if (!StoragePermissionHelper::canViewFileShares(Yii::$app->user->id, $storage, 'storageWebDefaultViewShares', 'storageWebDefaultViewSharesOwn', 'storageWebDefaultViewShares')) {
                return ['success' => false, 'message' => Module::t('You are not allowed to access this page.')];
            }
            $shares = \portalium\storage\models\StorageShare::getShares($storage)->all();
        } elseif ($id_directory) {
            $directory = Storage::findOne(['id_storage' => $id_directory, 'type' => Storage::TYPE_DIRECTORY]);
            if (!$directory) {
                return ['success' => false, 'message' => Module::t('Folder not found!')];
            }

            if (!StoragePermissionHelper::canViewDirectoryShares(Yii::$app->user->id, $directory, 'storageWebDefaultViewShares', 'storageWebDefaultViewSharesOwn', 'storageWebDefaultViewShares')) {
                return ['success' => false, 'message' => Module::t('You are not allowed to access this page.')];
            }
            $shares = \portalium\storage\models\StorageShare::getShares(null, $directory)->all();
        } elseif ($id_user_owner) {
            if (!\Yii::$app->user->can('storageWebDefaultViewShares') && $id_user_owner != Yii::$app->user->id) {
                return ['success' => false, 'message' => Module::t('You are not allowed to access this page.')];
            }
            $shares = \portalium\storage\models\StorageShare::getShares(null, null, $id_user_owner)->all();
        } else {
            return ['success' => false, 'message' => Module::t('Invalid share target.')];
        }

        $html = $this->renderPartial('_shares-list', [
            'shares' => $shares,
        ]);

        return ['success' => true, 'shares' => $shares, 'html' => $html];
    }

    /**
     * Revoke a share
     */
    public function actionRevokeShare($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // 1. Change the binding of the person performing the action (id_share)
        $id_share_context = Yii::$app->request->get('id_share') ?: Yii::$app->request->post('id_share');
        $shareToDelete = \portalium\storage\models\StorageShare::findOne($id);
        if (!$shareToDelete) {
            return ['successful' => false, 'message' => Module::t('Share not found!')];
        }

        // 2. PERMIT CHECK
        $hasManagePermission = false;

        // If accessed via an id_share connection (If the operation is performed via a shared link)
        if ($id_share_context) {
            $contextShare = \portalium\storage\models\StorageShare::findOne($id_share_context);
            if (
                $contextShare && $contextShare->isValid() &&
                $contextShare->permission_level == \portalium\storage\models\StorageShare::PERMISSION_MANAGE
            ) {
                $hasManagePermission = true;
            }
        }

        // Global authorization check (For logged-in users)
        if (!$hasManagePermission && !StoragePermissionHelper::canManageShare(Yii::$app->user->id, $shareToDelete, 'storageWebDefaultRevokeShare')) {
            return ['success' => false, 'message' => Module::t('You are not allowed to access this page.')];
        }

        // 3. DELETION (DECEIVING) OPERATION
        $shareToDelete->is_active = 0; // Portalium usually deletes by setting is_active=0
        if ($shareToDelete->save(false)) {
            return ['success' => true, 'message' => Module::t('Sharing successfully cancelled!')];
        }

        return ['success' => false, 'message' => Module::t('Failed to cancel sharing!')];
    }
    /**
     * Update share permission level
     */
    public function actionUpdateSharePermission($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'message' => Module::t('Only POST requests are allowed.')];
        }

        $share = \portalium\storage\models\StorageShare::findOne($id);
        if (!$share) {
            return ['success' => false, 'message' => Module::t('Share not found!')];
        }

        $permission_level = Yii::$app->request->post('permission_level');
        if (!in_array($permission_level, [
            \portalium\storage\models\StorageShare::PERMISSION_VIEW,
            \portalium\storage\models\StorageShare::PERMISSION_EDIT,
            \portalium\storage\models\StorageShare::PERMISSION_MANAGE
        ])) {
            return ['success' => false, 'message' => Module::t('Invalid permission level.')];
        }

        if (!StoragePermissionHelper::canManageShare(Yii::$app->user->id, $share, 'storageWebDefaultUpdateSharePermission')) {
            return ['success' => false, 'message' => Module::t('You are not allowed to access this page.'), 'share' => $share->attributes];
        }

        $share->permission_level = $permission_level;
        if ($share->save(false)) {
            return ['success' => true, 'message' => Module::t('Share permission updated successfully!')];
        } else {
            return ['success' => false, 'message' => Module::t('Failed to update share permission!')];
        }
    }

    /**
     * View shared items (items shared with current user)
     */
    public function actionSharedWithMe()
    {
        if (!\Yii::$app->user->can('storageWebDefaultViewShares')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        $userId = Yii::$app->user->id;
        $userWorkspaceIds = \portalium\workspace\models\WorkspaceUser::find()
            ->select('id_workspace')
            ->where(['id_user' => $userId])
            ->column();

        $shares = \portalium\storage\models\StorageShare::find()
            ->where(['is_active' => 1])
            ->andWhere([
                'OR',
                ['expires_at' => null],
                ['>', 'expires_at', date('Y-m-d H:i:s')]
            ])
            ->andWhere([
                'OR',
                ['shared_with_type' => \portalium\storage\models\StorageShare::TYPE_USER, 'id_shared_with' => $userId],
                ['shared_with_type' => \portalium\storage\models\StorageShare::TYPE_WORKSPACE, 'id_shared_with' => $userWorkspaceIds],
            ])
            ->with(['storage', 'directory', 'owner'])
            ->all();

        return $this->render('shared-with-me', [
            'shares' => $shares,
        ]);
    }

    /**
     * View items shared by current user (my shares)
     */
    public function actionMyShares()
    {
        if (!\Yii::$app->user->can('storageWebDefaultManageShares')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        $userId = Yii::$app->user->id;

        $fileShares = \portalium\storage\models\StorageShare::find()
            ->joinWith('storage')
            ->where(['is_active' => 1])
            ->andWhere(['{{%storage_storage}}.id_user' => $userId])
            ->andWhere(['IS NOT', '{{%' . Module::$tablePrefix . 'storage_share}}.id_storage', null])
            ->all();

        $directoryShares = \portalium\storage\models\StorageShare::find()
            ->joinWith('directory')
            ->where(['is_active' => 1])
            ->andWhere(['{{%storage_storage}}.id_user' => $userId])
            ->andWhere(['{{%storage_storage}}.type' => Storage::TYPE_DIRECTORY])
            ->andWhere(['IS NOT', '{{%' . Module::$tablePrefix . 'storage_share}}.id_directory', null])
            ->all();

        $fullStorageShares = \portalium\storage\models\StorageShare::find()
            ->where(['is_active' => 1])
            ->andWhere(['id_user_owner' => $userId])
            ->all();

        return $this->render('my-shares', [
            'fileShares' => $fileShares,
            'directoryShares' => $directoryShares,
            'fullStorageShares' => $fullStorageShares,
        ]);
    }
}
