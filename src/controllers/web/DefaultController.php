<?php

namespace portalium\storage\controllers\web;

use portalium\storage\models\StorageDirectory;
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

        $behaviors['access']['except'] = ['get-file'];

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

        $directoryQuery = StorageDirectory::find()
            ->andWhere(['id_parent' => $id_directory])
            ->orderBy(['id_directory' => SORT_DESC]);

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


        $directoryQuery = StorageDirectory::find()
            ->andWhere(['id_parent' => $id_directory])
            ->orderBy(['id_directory' => SORT_DESC]);

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
        if (!\Yii::$app->user->can('storageWebDefaultUploadFile') && !\Yii::$app->workspace->can('storage', 'storageWebDefaultUploadFile')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
        $post = Yii::$app->request->post();
        $type = $post['Storage']['type'] ?? 'file';
        $model = ($type === 'folder') ? new StorageDirectory() : new Storage();
        $id_directory = Yii::$app->request->post('id_directory') ?: null;
        $model->id_directory = $id_directory;

        if ($id_directory !== null) {
            $directoryModel = StorageDirectory::findOne($id_directory);
            if (
                !\Yii::$app->user->can('storageWebDefaultManageDirectory') &&
                !\Yii::$app->user->can('storageWebDefaultManageDirectoryOwn', ['model' => $directoryModel]) &&
                !\Yii::$app->workspace->can('storage', 'storageWebDefaultManageDirectory', ['model' => $directoryModel])
            ) {
                throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
            }
        }

        if (Yii::$app->request->isPost) {
            $model->load($post);

            if (!empty($post['Storage']['allowedExtensions'])) {
                $allowedExt = json_decode($post['Storage']['allowedExtensions'], true);
                if (is_array($allowedExt)) {
                    $model->allowedExtensions = $allowedExt;
                }
            }

            $uploadedFiles = UploadedFile::getInstancesByName('Storage[file]');
            $success = false;
            if ($type === 'folder') {
                if (empty($uploadedFiles)) {
                    $model->addError('file', Module::t('No files were uploaded'));
                } else {
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

                        if (!Storage::find()->where(['title' => $filename . $extension, 'id_directory' => $id_directory])->exists()) {
                            $model->title = $filename . $extension;
                        } else {
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


        if ($file) {
            $path = Yii::getAlias('@app') . '/../' . Yii::$app->setting->getValue('storage::path') . '/' . $file->name;

            if (!file_exists($path)) {
                Storage::deleteAll(['id_storage' => $file->id_storage]);
                Yii::$app->session->setFlash('error', Module::t('File not found!'));
            }
            $ext = pathinfo($file->name, PATHINFO_EXTENSION);
            $basename = pathinfo($file->title, PATHINFO_FILENAME);

            $cleanName = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $basename);
            $cleanName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $cleanName);

            $filename = $cleanName . '.' . $ext;

            return Yii::$app->response->sendFile($path, $filename, ['inline' => false]);
        }
        Yii::$app->session->setFlash('error', Module::t('File not found!'));
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

        $hasGlobalPermission = \Yii::$app->user->can('storageWebDefaultRenameFile')
            || \Yii::$app->user->can('storageWebDefaultRenameFileOwn', ["model" => $model])
            || \Yii::$app->workspace->can('storage', 'storageWebDefaultRenameFile', ['model' => $model]);

        $hasSharePermission = \portalium\storage\models\StorageShare::hasAccess(
            \Yii::$app->user->id,
            $model,
            null,
            \portalium\storage\models\StorageShare::PERMISSION_EDIT
        );

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

        $hasGlobalPermission = \Yii::$app->user->can('storageWebDefaultUpdateFile')
            || \Yii::$app->user->can('storageWebDefaultUpdateFileOwn', ["model" => $model])
            || \Yii::$app->workspace->can('storage', 'storageWebDefaultUpdateFile', ['model' => $model]);

        $hasSharePermission = \portalium\storage\models\StorageShare::hasAccess(
            \Yii::$app->user->id,
            $model,
            null,
            \portalium\storage\models\StorageShare::PERMISSION_EDIT
        );

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
            return ['success' => false, 'message' => 'Dosya bulunamadı'];
        }

        if (
            !\Yii::$app->user->can('storageWebDefaultShareFile') &&
            !\Yii::$app->user->can('storageWebDefaultShareFileOwn', ["model" => $model]) &&
            !\Yii::$app->workspace->can('storage', 'storageWebDefaultShareFile', ['model' => $model])
        ) {
            return ['success' => false, 'message' => 'Yetkiniz yok'];
        }

        $model->access = ($access === 'public') ? $model::ACCESS_PUBLIC : $model::ACCESS_PRIVATE;

        if ($model->save(false)) {
            return ['success' => true, 'message' => 'Erişim seviyesi güncellendi'];
        } else {
            return ['success' => false, 'message' => 'Kaydedilirken hata oluştu'];
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

        $hasGlobalPermission = \Yii::$app->user->can('storageWebDefaultShareFile')
            || \Yii::$app->user->can('storageWebDefaultShareFileOwn', ["model" => $model])
            || \Yii::$app->workspace->can('storage', 'storageWebDefaultShareFileOwn', ['model' => $model]);

        $hasManageSharePermission = \portalium\storage\models\StorageShare::hasAccess(
            \Yii::$app->user->id,
            $model,
            null,
            \portalium\storage\models\StorageShare::PERMISSION_MANAGE
        );

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
        $directory = StorageDirectory::findOne($id);
        if (!$directory) {
            throw new \yii\web\NotFoundHttpException(Module::t('Folder not found!'));
        }

        $hasGlobalPermission = \Yii::$app->user->can('storageWebDefaultShareDirectory')
            || \Yii::$app->user->can('storageWebDefaultShareDirectoryOwn', ["model" => $directory])
            || \Yii::$app->workspace->can('storage', 'storageWebDefaultShareDirectory', ['model' => $directory]);

        $hasManageSharePermission = \portalium\storage\models\StorageShare::hasAccess(
            \Yii::$app->user->id,
            null,
            $directory,
            \portalium\storage\models\StorageShare::PERMISSION_MANAGE
        );

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
        if (!Yii::$app->request->isPost)
            throw new \yii\web\BadRequestHttpException('Only POST requests are allowed.');

        $id = Yii::$app->request->post('id');

        if (!$id) {
            Yii::$app->session->setFlash('error', Module::t('File ID is required!'));
            return;
        }

        $sourceModel = Storage::findOne($id);

        if (!\Yii::$app->user->can('storageWebDefaultCopyFile') && !\Yii::$app->user->can('storageWebDefaultCopyFileOwn', ["model" => $sourceModel]) && !\Yii::$app->workspace->can('storage', 'storageWebDefaultCopyFile', ['model' => $sourceModel])) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        $storagePath = Yii::getAlias('@app') . '/../' . Yii::$app->setting->getValue('storage::path');
        $filePath = $storagePath . '/' . $sourceModel->name;

        if (!file_exists($filePath)) {
            Storage::deleteAll(['id_storage' => $sourceModel->id_storage]);
            Yii::$app->session->setFlash('error', Module::t('File not found!'));
            return;
        }

        $newModel = $sourceModel->copyFile();

        if ($newModel) {
            Yii::$app->session->setFlash('success', Module::t('File copied successfully!'));
        } else {
            Yii::$app->session->setFlash('error', Module::t('File could not be copied!'));
        }
    }

    /**
     * Deletes a file from storage.
     *
     * This action handles the deletion of a file by its ID. It validates user permissions,
     * checks if the file exists, and removes it from the storage system.
     *
     * @return void
     * @throws \yii\web\ForbiddenHttpException if user does not have permission to delete the file
     * @throws \yii\web\BadRequestHttpException if request method is not POST
     */
    public function actionDeleteFile()
    {
        $fileId = Yii::$app->request->post('id');

        if (!$fileId) {
            Yii::$app->session->setFlash('error', Module::t('File ID is required!'));
            return;
        }

        $file = Storage::findOne($fileId);

        if (!\Yii::$app->user->can('storageWebDefaultDeleteFile') && !\Yii::$app->user->can('storageWebDefaultDeleteFileOwn', ["model" => $file]) && !\Yii::$app->workspace->can('storage', 'storageWebDefaultDeleteFile', ['model' => $file])) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        if (!Yii::$app->request->isPost) {
            throw new \yii\web\BadRequestHttpException('Only POST requests are allowed.');
        }

        if ($file) {
            $path = Yii::getAlias('@app') . '/../' . Yii::$app->setting->getValue('storage::path') . '/' . $file->name;

            if (!file_exists($path)) {
                Storage::deleteAll(['id_storage' => $file->id_storage]);
                Yii::$app->session->setFlash('error', Module::t('File not found!'));
                return;
            }

            if ($file->deleteFile()) {
                Yii::$app->session->setFlash('success', Module::t('File deleted successfully!'));
            } else {
                Yii::$app->session->setFlash('error', Module::t('File could not be deleted!'));
            }
        } else {
            Yii::$app->session->setFlash('error', Module::t('File not found!'));
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
        $directoryQuery = StorageDirectory::find()
            ->andWhere(['id_parent' => $id_directory])
            ->orderBy(['id_directory' => SORT_DESC]);

        if (!$isPicker || !\Yii::$app->user->can('storageWebDefaultManageDirectory')) {
            StorageQueryService::applyDirectoryShareConditions($directoryQuery, $id_user, $userWorkspaceIds);
        }

        $directoryDataProvider = new ActiveDataProvider([
            'query' => $directoryQuery,
            'pagination' => [
                'pageSize' => self::DEFAULT_PAGE_SIZE - 1,
            ],
        ]);

        $dirAllQuery = StorageDirectory::find()
            ->andWhere(['id_parent' => $id_directory])
            ->orderBy(['id_directory' => SORT_DESC]);
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
        echo $this->renderAjax('@portalium/storage/widgets/views/_picker-modal', [
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

        $directoryQuery = \portalium\storage\models\StorageDirectory::find();

        if ($id_directory !== null) {
            $directoryQuery->andWhere(['id_parent' => $id_directory]);
        } else {
            $directoryQuery->andWhere(['id_parent' => null]);
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
            'sort' => ['defaultOrder' => ['id_directory' => SORT_DESC]],
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
        $model = new StorageDirectory();

        if (Yii::$app->request->isPost) {
            if ($model->load(Yii::$app->request->post())) {
                $id_directory = Yii::$app->request->post('id_directory');
                if ($id_directory === 'null' || $id_directory == 0)
                    $model->id_parent = null;
                else {
                    $model->id_parent = $id_directory;
                    $directoryModel = StorageDirectory::findOne($id_directory);
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

                while (StorageDirectory::find()
                    ->where(['id_parent' => $model->id_parent, 'name' => $name])
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
        $model = StorageDirectory::findOne(['id_directory' => $id]);

        $hasGlobalPermission = \Yii::$app->user->can('storageWebDefaultRenameFolder')
            || \Yii::$app->user->can('storageWebDefaultRenameFolderOwn', ["model" => $model])
            || \Yii::$app->workspace->can('storage', 'storageWebDefaultRenameFolder', ['model' => $model]);

        $hasSharePermission = \portalium\storage\models\StorageShare::hasAccess(
            \Yii::$app->user->id,
            null,
            $model,
            \portalium\storage\models\StorageShare::PERMISSION_EDIT
        );

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
                    while (StorageDirectory::find()
                        ->where(['name' => $name, 'id_parent' => $model->id_parent])
                        ->andWhere(['<>', 'id_directory', $id])
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
     * @param int $id The ID of the folder to delete
     * @param int|null $id_directory The parent directory ID (optional)
     * @return array JSON response with success status and message
     * @throws \yii\web\ForbiddenHttpException if user lacks permission
     */
    public function actionDeleteFolder($id, $id_directory = null)
    {
        $folder = StorageDirectory::findOne(['id_directory' => $id]);

        $hasGlobalPermission = \Yii::$app->user->can('storageWebDefaultDeleteFolder')
            || \Yii::$app->user->can('storageWebDefaultDeleteFolderOwn', ["model" => $folder])
            || \Yii::$app->workspace->can('storage', 'storageWebDefaultDeleteFolder', ['model' => $folder]);

        $hasSharePermission = \portalium\storage\models\StorageShare::hasAccess(
            \Yii::$app->user->id,
            null,
            $folder,
            \portalium\storage\models\StorageShare::PERMISSION_MANAGE
        );

        if (!$hasGlobalPermission && !$hasSharePermission) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;


        if (!$folder) {
            Yii::$app->session->setFlash('error', Module::t('Folder not found!'));
            return ['success' => false, 'message' => Module::t('Folder not found!')];
        }

        $this->deleteFolderRecursive($folder);

        Yii::$app->session->setFlash('success', Module::t('Folder and its contents deleted successfully!'));
    }

    /**
     * Recursively deletes a folder and all its contents.
     *
     * Checks user permissions before deletion. Deletes all subfolders,
     * files, and the folder itself.
     *
     * @param StorageDirectory $folder The folder to delete
     * @throws \yii\web\ForbiddenHttpException If user lacks delete permission
     */
    protected function deleteFolderRecursive($folder)
    {
        if (!\Yii::$app->user->can('storageWebDefaultDeleteFolderRecursive') && !\Yii::$app->user->can('storageWebDefaultDeleteFolderRecursiveOwn', ["model" => $folder]) && !\Yii::$app->workspace->can('storage', 'storageWebDefaultDeleteFolderRecursive', ['model' => $folder])) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
        $subFolders = StorageDirectory::findAll(['id_parent' => $folder->id_directory]);
        foreach ($subFolders as $subFolder) {
            $this->deleteFolderRecursive($subFolder);
        }

        $files = Storage::findAll(['id_directory' => $folder->id_directory]);
        foreach ($files as $file) {
            $filePath = Yii::getAlias('@app') . '/../' . Yii::$app->setting->getValue('storage::path') . '/' . $file->name;
            if (file_exists($filePath))
                @unlink($filePath);
            $file->delete();
        }
        $folder->delete();
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
        $file = null;

        $requestedThumb = Yii::$app->request->get('type') === 'thumb';

        if ($id !== null) {
            try {
                $file = $this->findModel($id);
            } catch (\Exception $e) {
                $file = null;
            }
        }

        if ($file === null && $file_name !== null) {
            $file = Storage::findOne(['name' => $file_name]);
        }

        if ($file === null) {
            Yii::$app->response->statusCode = 404;
            Yii::$app->response->content = Module::t('The requested file does not exist.');
            return Yii::$app->response;
        }

        if ($file->access == Storage::ACCESS_PRIVATE && !Yii::$app->user->can('storageWebDefaultGetFile', ['model' => $file]) && !Yii::$app->workspace->can('storage', 'storageWebDefaultGetFile', ['model' => $file])) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        $storagePath = Yii::$app->basePath . '/../' . Yii::$app->setting->getValue('storage::path');

        if ($requestedThumb) {
            $thumbPath = $storagePath . '/thumb_' . $file->name;
            $servePath = file_exists($thumbPath) ? $thumbPath : ($storagePath . '/' . $file->name);
        } else {
            $servePath = $storagePath . '/' . $file->name;
        }

        $path = $servePath;

        if (file_exists($path)) {
            $fileExtension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $path);
            finfo_close($finfo);

            $response = Yii::$app->response;
            $serveTitle = $requestedThumb ? ('thumb_' . $file->title) : $file->title;

            if ($mimeType === 'application/pdf') {
                $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
                $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'");
                $response->headers->set('Content-Disposition', 'inline; filename="' . $serveTitle . '.pdf"');
                $response->headers->set('Cache-Control', 'public, max-age=3600');

                return $response->sendFile($path, $serveTitle . '.pdf');
            }

            if (strpos($mimeType, 'image/') === 0) {
                $response->headers->set('Access-Control-Allow-Origin', '*');
                $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
                $response->headers->set('Cross-Origin-Resource-Policy', 'cross-origin');
            }

            return $response->sendFile($path, $serveTitle . '.' . $fileExtension);
        } else {
            Yii::$app->response->statusCode = 404;
            Yii::$app->response->content = Module::t('The requested file does not exist.');
            return Yii::$app->response;
        }
    }

    /**
     * Generates missing thumbnails for stored files.
     *
     * This action triggers the thumbnail generation process for any files
     * that are missing their thumbnail images.
     *
     * @return void
     */
    public function actionGenerateMissingThumbnails()
    {
        $updated = Storage::generateMissingThumbnails();
        return;
    }

    /**
     * Download an entire folder as a ZIP archive.
     *
     * GET /storage/default/download-folder?id=<id_directory>
     */
    public function actionDownloadFolder($id)
    {
        $folder = StorageDirectory::findOne(['id_directory' => $id]);

        if (!$folder) {
            throw new \yii\web\NotFoundHttpException(Module::t('Folder not found!'));
        }

        $hasGlobalPermission = \Yii::$app->user->can('storageWebDefaultDownloadFile')
            || \Yii::$app->workspace->can('storage', 'storageWebDefaultDownloadFile', ['model' => $folder]);

        $hasSharePermission = \portalium\storage\models\StorageShare::hasAccess(
            \Yii::$app->user->id,
            null,
            $folder,
            \portalium\storage\models\StorageShare::PERMISSION_VIEW
        );

        $isOwner = $folder->id_user === \Yii::$app->user->id;

        if (!$hasGlobalPermission && !$hasSharePermission && !$isOwner) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        $storagePath = Yii::$app->basePath . '/../' . Yii::$app->setting->getValue('storage::path');

        try {
            $tmpZip = \portalium\storage\helpers\StorageZipHelper::buildZip($folder, $storagePath);
        } catch (\RuntimeException $e) {
            throw new \yii\web\ServerErrorHttpException($e->getMessage());
        }

        $zipName = preg_replace('/[^\w\-.]/', '_', $folder->name) . '.zip';

        Yii::$app->response->on(\yii\base\Event::class, function () use ($tmpZip) {
            @unlink($tmpZip);
        });

        return Yii::$app->response->sendFile($tmpZip, $zipName, ['mimeType' => 'application/zip', 'inline' => false]);
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

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'message' => Module::t('Only POST requests are allowed.')];
        }

        $id_storage = Yii::$app->request->post('id_storage');
        $id_directory = Yii::$app->request->post('id_directory');
        $id_user_owner = Yii::$app->request->post('id_user_owner');
        $shared_with_type = Yii::$app->request->post('shared_with_type');
        $id_shared_with = Yii::$app->request->post('id_shared_with');
        $permission_level = Yii::$app->request->post('permission_level', \portalium\storage\models\StorageShare::PERMISSION_VIEW);
        $expires_at = Yii::$app->request->post('expires_at');

        if ($id_storage) {
            $storage = Storage::findOne($id_storage);
            if (!$storage) {
                return ['success' => false, 'message' => Module::t('File not found!')];
            }

            if (!StoragePermissionHelper::canShareFile(Yii::$app->user->id, $storage, 'storageWebDefaultShareFileOwn', 'storageWebDefaultShareFile')) {
                return ['success' => false, 'message' => Module::t('You are not allowed to share this file.')];
            }
        } elseif ($id_directory) {
            $directory = StorageDirectory::findOne($id_directory);
            if (!$directory) {
                return ['success' => false, 'message' => Module::t('Folder not found!')];
            }

            if (!StoragePermissionHelper::canShareDirectory(Yii::$app->user->id, $directory, 'storageWebDefaultShareDirectoryOwn', 'storageWebDefaultShareDirectory')) {
                return ['success' => false, 'message' => Module::t('You are not allowed to share this folder.')];
            }
        } elseif ($id_user_owner) {
            if (!\Yii::$app->user->can('storageWebDefaultShareFullStorage') && $id_user_owner != Yii::$app->user->id) {
                return ['success' => false, 'message' => Module::t('You are not allowed to access this page.')];
            }
        } else {
            return ['success' => false, 'message' => Module::t('Invalid share target.')];
        }

        $share = new \portalium\storage\models\StorageShare();
        $share->id_storage = $id_storage ?: null;
        $share->id_directory = $id_directory ?: null;
        $share->id_user_owner = $id_user_owner ?: null;
        $share->shared_with_type = $shared_with_type;
        $share->id_shared_with = $id_shared_with ?: null;
        $share->permission_level = $permission_level;
        $share->expires_at = $expires_at ?: null;

        if ($shared_with_type === \portalium\storage\models\StorageShare::TYPE_LINK) {
            $share->generateShareToken();
        }

        if ($share->save()) {
            $generatedLink = '';
            if ($shared_with_type === \portalium\storage\models\StorageShare::TYPE_LINK) {
            
                $generatedLink = \yii\helpers\Url::to(['/storage/default/view-share', 'id' => $share->id_share], true);
            }

            return [
                'success' => true, 
                'message' => Module::t('Share created successfully!'), 
                'share' => $share,
                'link' => $generatedLink 
            ];
        }
        } else {
            return ['success' => false, 'message' => Module::t('Failed to create share!'), 'errors' => $share->errors];
        }
    }

    
    /**
     * View a shared item via link
     */
    public function actionViewShare($id)
    {
        $share = \portalium\storage\models\StorageShare::findOne($id);

        if (!$share || $share->shared_with_type !== \portalium\storage\models\StorageShare::TYPE_LINK || !$share->isValid()) {
            throw new NotFoundHttpException(Module::t('The requested share link is invalid or has expired.'));
        }

        // If it's a direct file share, just serve the file
        if ($share->id_storage !== null) {
            $file = Storage::findOne($share->id_storage);
            if (!$file) {
                throw new NotFoundHttpException(Module::t('The requested file does not exist.'));
            }
            
            // Log access
            $file->access_count = ($file->access_count ?? 0) + 1;
            $file->date_last_access = date('Y-m-d H:i:s');
            $file->save(false, ['access_count', 'date_last_access']);

            // Reuse GetFile logic for returning the file
            $path = Yii::$app->basePath . '/../' . Yii::$app->setting->getValue('storage::path') . '/' . $file->name;
            if (file_exists($path)) {
                $response = Yii::$app->response;
                $fileExtension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                
                if (in_array($file->mime_type, ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'])) {
                    $response->headers->set('Content-Disposition', 'inline; filename="' . $file->title . '.' . $fileExtension . '"');
                } else {
                    $response->headers->set('Content-Disposition', 'attachment; filename="' . $file->title . '.' . $fileExtension . '"');
                }
                return $response->sendFile($path, $file->title . '.' . $fileExtension, ['inline' => true]);
            }
            throw new NotFoundHttpException(Module::t('File not found on disk.'));
        }
        
        // For directory or full storage, redirect to index with specific parameters or render a custom view
        // Since we are creating a generic view for shared folders, we'll render a simple view or redirect
        // For now, redirect to login if guest, or to storage index with a flash message
        if (Yii::$app->user->isGuest) {
            Yii::$app->user->setReturnUrl(['/storage/default/view-share', 'id' => $id]);
            return $this->redirect(['/site/auth/login']);
        }
        
        if ($share->id_directory) {
            return $this->redirect(['/storage/default/index', 'id_directory' => $share->id_directory]);
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
            $directory = StorageDirectory::findOne($id_directory);
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

        $share = \portalium\storage\models\StorageShare::findOne($id);
        if (!$share) {
            return ['success' => false, 'message' => Module::t('Share not found!')];
        }

        if (!StoragePermissionHelper::canManageShare(Yii::$app->user->id, $share, 'storageWebDefaultRevokeShare')) {
            return ['success' => false, 'message' => Module::t('You are not allowed to access this page.')];
        }

        $share->is_active = 0;
        if ($share->save(false)) {
            return ['success' => true, 'message' => Module::t('Share revoked successfully!')];
        } else {
            return ['success' => false, 'message' => Module::t('Failed to revoke share!')];
        }
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
            ->andWhere(['{{%storage_storage_directory}}.id_user' => $userId])
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
