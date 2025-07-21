<?php

namespace portalium\storage\controllers\web;

use portalium\storage\models\StorageDirectory;
use portalium\storage\Module;
use portalium\web\Controller;
use portalium\storage\models\Storage;
use portalium\storage\models\StorageSearch;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

use portalium\data\ActiveDataProvider;


class DefaultController extends Controller
{
    const DEFAULT_PAGE_SIZE = 24;

    public function actionIndex()
    {
        if (!\Yii::$app->user->can('storageWebDefaultIndexOwn')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        $model = new Storage();
        $searchModel = new StorageSearch();
        $id_directory = Yii::$app->request->get('id_directory');
        $isPicker = Yii::$app->request->get('isPicker', false);

        // ★ YENİ: fileExtensions parametresini al ★
        $fileExtensions = Yii::$app->request->get('fileExtensions', []);

        // Normalize fileExtensions
        if (is_string($fileExtensions) && !empty($fileExtensions)) {
            $fileExtensions = explode(',', $fileExtensions);
        }
        if (!is_array($fileExtensions)) {
            $fileExtensions = [];
        }

        // Boş string'leri temizle
        $fileExtensions = array_filter($fileExtensions, function ($ext) {
            return !empty(trim($ext));
        });


        $id_user = Yii::$app->user->id;
        $fileDataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $fileDataProvider->query->andWhere(['id_directory' => $id_directory])->andWhere(['id_user' => $id_user]);


        // ★ YENİ: fileExtensions filtresini uygula ★
        if (!empty($fileExtensions) && is_array($fileExtensions)) {
            $orConditions = ['or'];
            foreach ($fileExtensions as $extension) {
                $cleanExtension = '.' . ltrim(trim($extension), '.');
                // ★ DOĞRU: % işaretini başa koy ★
                $orConditions[] = ['like', 'name', '%' . $cleanExtension, false];
            }

            if (count($orConditions) > 1) {
                $fileDataProvider->query->andWhere($orConditions);
            }
        }

        $fileDataProvider->pagination->pageSize = self::DEFAULT_PAGE_SIZE;

        $directoryDataProvider = new ActiveDataProvider([
            'query' => StorageDirectory::find()
                ->andWhere(['id_parent' => $id_directory])
                ->andWhere(['id_user' => $id_user])
                ->orderBy(['id_directory' => SORT_DESC]),
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
                'actionId' => "index"
            ]);
        }

        return $this->render('index', [
            'model' => $model,
            'dataProvider' => $searchModel->search($this->request->queryParams),
            'fileDataProvider' => $fileDataProvider,
            'directoryDataProvider' => $directoryDataProvider,
            'isPicker' => $isPicker,
            'actionId' => 'index',
        ]);
    }

    public function actionManage()
    {
        if (!\Yii::$app->user->can('storageWebDefaultIndex')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        $model = new Storage();
        $searchModel = new StorageSearch();
        $id_directory = Yii::$app->request->get('id_directory');
        $isPicker = Yii::$app->request->get('isPicker', false);

        // ★ YENİ: fileExtensions parametresini al ★
        $fileExtensions = Yii::$app->request->get('fileExtensions', []);

        // Normalize fileExtensions
        if (is_string($fileExtensions) && !empty($fileExtensions)) {
            $fileExtensions = explode(',', $fileExtensions);
        }
        if (!is_array($fileExtensions)) {
            $fileExtensions = [];
        }

        // Boş string'leri temizle
        $fileExtensions = array_filter($fileExtensions, function ($ext) {
            return !empty(trim($ext));
        });

        $fileDataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $fileDataProvider->query->andWhere(['id_directory' => $id_directory]);

        // ★ YENİ: fileExtensions filtresini uygula ★
        if (!empty($fileExtensions) && is_array($fileExtensions)) {
            $orConditions = ['or'];
            foreach ($fileExtensions as $extension) {
                $cleanExtension = '.' . ltrim(trim($extension), '.');
                // ★ DOĞRU: % işaretini başa koy ★
                $orConditions[] = ['like', 'name', '%' . $cleanExtension, false];
            }

            if (count($orConditions) > 1) {
                $fileDataProvider->query->andWhere($orConditions);
            }
        }

        $fileDataProvider->pagination->pageSize = self::DEFAULT_PAGE_SIZE;


        $directoryDataProvider = new ActiveDataProvider([
            'query' => StorageDirectory::find()
                ->andWhere(['id_parent' => $id_directory])
                ->orderBy(['id_directory' => SORT_DESC]),
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

    public function actionUploadFile()
    {
        if (!\Yii::$app->user->can('storageWebDefaultUploadFile')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
        $post = Yii::$app->request->post();
        $type = $post['Storage']['type'] ?? 'file';
        $model = ($type === 'folder') ? new StorageDirectory() : new Storage();
        $id_directory = Yii::$app->request->post('id_directory') ?: null;
        $model->id_directory = $id_directory;

        if ($id_directory !== null) {
            $directoryModel = StorageDirectory::findOne($id_directory);
            if (!\Yii::$app->user->can('storageWebDefaultManageDirectory') && !\Yii::$app->user->can('storageWebDefaultManageDirectoryOwn', ['model' => $directoryModel])) {
                throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
            }
        }

        if (Yii::$app->request->isPost) {
            $model->load($post);
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
                        $baseName = trim($post['Storage']['title']);
                        $name = $baseName;
                        $counter = 1;
                        while (Storage::find()
                            ->where(['title' => $name, 'id_directory' => $id_directory])
                            ->exists()
                        ) {
                            $name = $baseName . ' (' . $counter . ')';
                            $counter++;
                        }
                        $model->title = $name;
                    }
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

    public function actionDownloadFile()
    {
        $id = Yii::$app->request->post('id');
        $file = Storage::findOne($id);
        if (!\Yii::$app->user->can('storageWebDefaultDownloadFile') && !\Yii::$app->user->can('storageWebDefaultDownloadFileOwn', ["model" => $file])) {
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

            $cleanName = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $basename); // ş→s, ı→i falan
            $cleanName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $cleanName);   // boşluk ve sembolleri alt çizgi yap

            $filename = $cleanName . '.' . $ext;

            return Yii::$app->response->sendFile($path, $filename, ['inline' => false]);
        }
        Yii::$app->session->setFlash('error', Module::t('File not found!'));
    }

    public function actionRenameFile($id)
    {
        $model = Storage::findOne($id);
        if (!\Yii::$app->user->can('storageWebDefaultRenameFile') && !\Yii::$app->user->can('storageWebDefaultRenameFileOwn', ["model" => $model])) {
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

    public function actionUpdateFile($id)
    {
        $model = Storage::findOne($id);
        if (!\Yii::$app->user->can('storageWebDefaultUpdateFile') && !\Yii::$app->user->can('storageWebDefaultUpdateFileOwn', ["model" => $model])) {
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
            $oldFileName = $model->name;

            $model->file = UploadedFile::getInstance($model, 'file');

            if ($model->file && in_array($model->file->extension, Storage::$allowExtensions)) {
                $path = realpath(Yii::getAlias('@app') . '/../data');
                $filename = md5(rand()) . "." . $model->file->extension;
                $hash = md5_file($model->file->tempName);

                if ($model->file->saveAs($path . '/' . $filename)) {
                    if (file_exists($oldFilePath)) {
                        @unlink($oldFilePath);
                    }

                    $model->name = $filename;
                    $model->hash_file = $hash;
                    $model->mime_type = Storage::MIME_TYPE[$model->getMIMEType($path . '/' . $filename)];
                    $model->date_update = date('Y-m-d H:i:s');

                    if ($model->save(false))
                        Yii::$app->session->setFlash('success', Module::t('File updated successfully!'));
                    else
                        Yii::$app->session->setFlash('error', Module::t('File update failed!'));
                } else {
                    Yii::$app->session->setFlash('error', Module::t('File could not be saved!'));
                }
            } else {
                Yii::$app->session->setFlash('error', Module::t('Invalid file format!'));
            }
        }

        return $this->renderPartial('_update', ['model' => $model]);
    }

    public function actionShareFile($id)
    {
        $model = Storage::findOne($id);
        if (!\Yii::$app->user->can('storageWebDefaultShareFile') && !\Yii::$app->user->can('storageWebDefaultShareFileOwn', ["model" => $model])) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
        return $this->renderPartial('_share', [
            'model' => $model,
        ]);
    }

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

        if (!\Yii::$app->user->can('storageWebDefaultCopyFile') && !\Yii::$app->user->can('storageWebDefaultCopyFileOwn', ["model" => $sourceModel])) {
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

    public function actionDeleteFile()
    {
        $fileId = Yii::$app->request->post('id');

        if (!$fileId) {
            Yii::$app->session->setFlash('error', Module::t('File ID is required!'));
            return;
        }

        $file = Storage::findOne($fileId);

        if (!\Yii::$app->user->can('storageWebDefaultDeleteFile') && !\Yii::$app->user->can('storageWebDefaultDeleteFileOwn', ["model" => $file])) {
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

    public function actionPickerModal()
    {
        if (!\Yii::$app->user->can('storageWebDefaultPickerModal')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        $id_directory = Yii::$app->request->get('id_directory');
        $fileExtensions = Yii::$app->request->get('fileExtensions', []);
        $isPicker = Yii::$app->request->get('isPicker', true);
        $multiple = Yii::$app->request->get('multiple', false);
        $isJson = Yii::$app->request->get('isJson', true);
        $attributes = Yii::$app->request->get('attributes', ['id_storage']);

        // Normalize fileExtensions - STRING IÇİN HANDLE ET
        if (is_string($fileExtensions) && !empty($fileExtensions)) {
            $fileExtensions = explode(',', $fileExtensions);
        }
        if (!is_array($fileExtensions)) {
            $fileExtensions = [];
        }

        // Boş string'leri temizle
        $fileExtensions = array_filter($fileExtensions, function ($ext) {
            return !empty(trim($ext));
        });

        $query = Storage::find();
        $query->andWhere(['id_directory' => $id_directory]);

        // ★ DÜZELTME: LIKE operatörünü doğru kullan ★
        if (!empty($fileExtensions) && is_array($fileExtensions)) {
            $orConditions = ['or'];
            foreach ($fileExtensions as $extension) {
                $cleanExtension = '.' . ltrim(trim($extension), '.');
                // ★ DOĞRU: Dosya adının SONUNDA extension var mı kontrol et ★
                $orConditions[] = ['like', 'name', '%' . $cleanExtension, false];
            }

            if (count($orConditions) > 1) {
                $query->andWhere($orConditions);
            }
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

        $directoryDataProvider = new ActiveDataProvider([
            'query' => StorageDirectory::find()
                ->andWhere(['id_parent' => $id_directory])
                ->orderBy(['id_directory' => SORT_DESC]),
            'pagination' => [
                'pageSize' => self::DEFAULT_PAGE_SIZE - 1,
            ],
        ]);

        $directories = StorageDirectory::find()
            ->andWhere(['id_parent' => $id_directory])
            ->orderBy(['id_directory' => SORT_DESC])
            ->all();

        // Files için aynı filtreyi uygula
        $filesQuery = Storage::find()->andWhere(['id_directory' => $id_directory]);

        if (!empty($fileExtensions) && is_array($fileExtensions)) {
            $orConditions = ['or'];
            foreach ($fileExtensions as $extension) {
                $cleanExtension = '.' . ltrim(trim($extension), '.');
                // ★ DOĞRU: % işaretini başa koy ★
                $orConditions[] = ['like', 'name', '%' . $cleanExtension, false];
            }
            if (count($orConditions) > 1) {
                $filesQuery->andWhere($orConditions);
            }
        }

        $files = $filesQuery->orderBy(['id_storage' => SORT_DESC])->all();

        $pagination = $dataProvider->getPagination();

        ob_start(); // çıktıyı yakala
        echo $this->renderAjax('@portalium/storage/widgets/views/_picker-modal', [
            'dataProvider' => $dataProvider,
            'directoryDataProvider' => $directoryDataProvider,
            'directories' => $directories,
            'files' => $files,
            'pagination' => $pagination,
            'fileExtensions' => $fileExtensions,
            'isPicker' => $isPicker,
            'multiple' => $multiple,
            'isJson' => $isJson,
            'attributes' => $attributes,
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
            // jQuery ve Bootstrap JS dosyalarını kaldır
            $output = preg_replace(
                '#<script[^>]+src=["\']?/assets/[^"\']+/(jquery\.js|yii\.js|bootstrap\.bundle\.js|tab\.js|jquery\.min\.js)[^"\']*["\'][^>]*>#i',
                '',
                $output
            );
        }

        return $output;
    }

    /**
     * Picker içeriğini yeniler (PJAX için)
     */
    public function actionPickerContent()
    {
        if (!\Yii::$app->user->can('storageWebDefaultPickerContent')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        $id_directory = Yii::$app->request->get('id_directory');
        $fileExtensions = Yii::$app->request->get('fileExtensions', []);
        $isPicker = Yii::$app->request->get('isPicker', true);
        $attributes = Yii::$app->request->get('attributes', ['id_storage']);


        if (is_string($fileExtensions) && !empty($fileExtensions)) {
            $fileExtensions = explode(',', $fileExtensions);
        }
        if (!is_array($fileExtensions)) {
            $fileExtensions = [];
        }

        $query = Storage::find();
        $query->andWhere(['id_directory' => $id_directory]);


        if (!empty($fileExtensions) && is_array($fileExtensions)) {
            $orConditions = ['or'];
            foreach ($fileExtensions as $extension) {
                $cleanExtension = '.' . ltrim(trim($extension), '.');

                $orConditions[] = ['like', 'name', '%' . $cleanExtension, false];
            }
            if (count($orConditions) > 1) {
                $query->andWhere($orConditions);
            }
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => self::DEFAULT_PAGE_SIZE,
            ],
            'sort' => [
                'defaultOrder' => ['id_storage' => SORT_DESC],
            ],
        ]);

        $directoryDataProvider = new ActiveDataProvider([
            'query' => StorageDirectory::find()
                ->andWhere(['id_parent' => $id_directory])
                ->orderBy(['id_directory' => SORT_DESC]),
            'pagination' => [
                'pageSize' => self::DEFAULT_PAGE_SIZE - 1,
            ],
        ]);

        return $this->renderAjax('@portalium/storage/widgets/views/_picker-content', [
            'dataProvider' => $dataProvider,
            'directoryDataProvider' => $directoryDataProvider,
            'fileExtensions' => $fileExtensions,
            'isPicker' => $isPicker,
            'attributes' => $attributes,
        ]);
    }

    public function actionFileList()
    {
        if (!\Yii::$app->user->can('storageWebDefaultFileList')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }


        $fileExtensions = Yii::$app->request->get('fileExtensions', []);


        if (is_string($fileExtensions) && !empty($fileExtensions)) {
            $fileExtensions = explode(',', $fileExtensions);
        }
        if (!is_array($fileExtensions)) {
            $fileExtensions = [];
        }

        $query = Storage::find();


        if (!empty($fileExtensions) && is_array($fileExtensions)) {
            $orConditions = ['or'];
            foreach ($fileExtensions as $extension) {
                $cleanExtension = '.' . ltrim(trim($extension), '.');

                $orConditions[] = ['like', 'name', '%' . $cleanExtension, false];
            }

            if (count($orConditions) > 1) {
                $query->andWhere($orConditions);
            }
        }

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

    public function actionSearch()
    {
        if (!\Yii::$app->user->can('storageWebDefaultSearch')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
        Yii::$app->response->format = \yii\web\Response::FORMAT_HTML;

        $q = Yii::$app->request->get('q', '');
        $id_directory = Yii::$app->request->get('id_directory');
        $isPicker = Yii::$app->request->get('isPicker', false);
        $fileExtensions = Yii::$app->request->get('fileExtensions', []);


        if (is_string($fileExtensions) && !empty($fileExtensions)) {
            $fileExtensions = explode(',', $fileExtensions);
        }
        if (!is_array($fileExtensions)) {
            $fileExtensions = [];
        }

        $fileQuery = Storage::find();
        if (!empty($q)) {
            $fileQuery->andFilterWhere(['like', 'title', $q]);
        }
        if ($id_directory !== null) {
            $fileQuery->andWhere(['id_directory' => $id_directory]);
        }


        if (!empty($fileExtensions) && is_array($fileExtensions)) {
            $orConditions = ['or'];
            foreach ($fileExtensions as $extension) {
                $cleanExtension = '.' . ltrim(trim($extension), '.');

                $orConditions[] = ['like', 'name', '%' . $cleanExtension, false];
            }
            if (count($orConditions) > 1) {
                $fileQuery->andWhere($orConditions);
            }
        }

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

    public function actionNewFolder()
    {
        if (!\Yii::$app->user->can('storageWebDefaultNewFolder')) {
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
                    if (!\Yii::$app->user->can('storageWebDefaultManageDirectory') && !\Yii::$app->user->can('storageWebDefaultManageDirectoryOwn', ['model' => $directoryModel])) {
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

    // +++
    public function actionRenameFolder($id)
    {
        $model = StorageDirectory::findOne(['id_directory' => $id]);

        if (!\Yii::$app->user->can('storageWebDefaultRenameFolder') && !\Yii::$app->user->can('storageWebDefaultRenameFolderOwn', ["model" => $model])) {
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

    public function actionDeleteFolder($id, $id_directory = null)
    {
        $folder = StorageDirectory::findOne(['id_directory' => $id]);

        if (!\Yii::$app->user->can('storageWebDefaultDeleteFolder') && !\Yii::$app->user->can('storageWebDefaultDeleteFolderOwn', ["model" => $folder])) {
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

    protected function deleteFolderRecursive($folder)
    {
        if (!\Yii::$app->user->can('storageWebDefaultdeleteFolderRecursive') && !\Yii::$app->user->can('storageWebDefaultdeleteFolderRecursiveOwn', ["model" => $folder])) {
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

    public function actionGetFile($id, $access_token = null)
    {
        try {

            $file = $this->findModel($id);
        } catch (\Exception $e) {
            //            throw new NotFoundHttpException(Module::t('The requested file does not exist.'));
            Yii::$app->response->statusCode = 404;
            Yii::$app->response->content = Module::t('The requested file does not exist.');
            return Yii::$app->response;
        }

        /*  if (!Yii::$app->user->can('storageWebDefaultGetFile', ['model' => $file])) {
            // throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        } */

        $path = Yii::$app->basePath . '/../' . Yii::$app->setting->getValue('storage::path') . '/' . $file->name;

        if (file_exists($path)) {
            return Yii::$app->response->sendFile($path, $file->title . '.' . pathinfo($path, PATHINFO_EXTENSION));
        } else {
            Yii::$app->response->statusCode = 404;
            Yii::$app->response->content = Module::t('The requested file does not exist.');
            return Yii::$app->response;
        }
    }

    /**
     * Debug action - Test file extension filtering
     * Bu action'ı geliştirme sırasında filtrelemeyi test etmek için kullanabilirsiniz
     */
    public function actionTestFilter()
    {
        if (!YII_DEBUG) {
            throw new \yii\web\NotFoundHttpException();
        }

        $fileExtensions = Yii::$app->request->get('extensions', ['.jpg', '.png']);
        $id_directory = Yii::$app->request->get('id_directory', null);

        echo "<h3>File Extension Filter Test</h3>";
        echo "<p>Testing extensions: " . implode(', ', $fileExtensions) . "</p>";
        echo "<p>Directory ID: " . ($id_directory ?? 'null') . "</p>";


        $query = Storage::find();
        $query->andWhere(['id_directory' => $id_directory]);

        if (!empty($fileExtensions) && is_array($fileExtensions)) {
            $orConditions = ['or'];
            foreach ($fileExtensions as $extension) {
                $cleanExtension = '.' . ltrim($extension, '.');

                $orConditions[] = ['like', 'name', '%' . $cleanExtension, false];
            }
            $query->andWhere($orConditions);
        }

        echo "<h4>Generated SQL:</h4>";
        echo "<pre>" . $query->createCommand()->rawSql . "</pre>";

        $files = $query->all();
        echo "<h4>Found Files (" . count($files) . "):</h4>";
        echo "<ul>";
        foreach ($files as $file) {
            echo "<li>" . $file->name . " (Title: " . $file->title . ")</li>";
        }
        echo "</ul>";

        $allFiles = Storage::find()->andWhere(['id_directory' => $id_directory])->all();
        echo "<h4>All Files in Directory (" . count($allFiles) . "):</h4>";
        echo "<ul>";
        foreach ($allFiles as $file) {
            echo "<li>" . $file->name . " (Title: " . $file->title . ")</li>";
        }
        echo "</ul>";
    }

    protected function findModel($id)
    {
        if (($model = Storage::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException(Module::t('The requested file does not exist.'));
    }
}
