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
    const DEFAULT_PAGE_SIZE = 12;
    
    public function actionIndex()
    {
        $model = new Storage();
        $searchModel = new StorageSearch();
        $id_directory = Yii::$app->request->get('id_directory');
        $dataProvider = $searchModel->search($this->request->queryParams);
        
        // File data provider setup
        $fileDataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $fileDataProvider->query->andWhere(['id_directory' => $id_directory]);
        $fileDataProvider->pagination->pageSize = self::DEFAULT_PAGE_SIZE;
        
        // Directory data provider setup
        $directoryDataProvider = new ActiveDataProvider([
            'query' => StorageDirectory::find()
                ->andWhere(['id_parent' => $id_directory])
                ->orderBy(['id_directory' => SORT_DESC]),
            'pagination' => [
                'pageSize' => self::DEFAULT_PAGE_SIZE-1,
            ],
        ]);
        
        $isPicker = Yii::$app->request->get('isPicker', false);
        
        if (Yii::$app->request->isPjax) {
            return $this->renderAjax('_item-list', [
                'directoryDataProvider' => $directoryDataProvider,
                'fileDataProvider' => $fileDataProvider,
                'isPicker' => $isPicker,
            ]);
        }
        
        return $this->render('index', [
            'model' => $model,
            'dataProvider' => $dataProvider,
            'fileDataProvider' => $fileDataProvider,
            'directoryDataProvider' => $directoryDataProvider,
            'isPicker' => $isPicker
        ]);
    }

    public function actionUploadFile()
    {
        $post = Yii::$app->request->post();
        $type = $post['Storage']['type'] ?? 'file';
        $model = ($type === 'folder') ? new StorageDirectory() : new Storage();
        $id_directory = Yii::$app->request->post('id_directory') ?: null;
        $model->id_directory = $id_directory;
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

        return $this->renderPartial('_upload-file', [
            'model' => $model,
        ]);
    }

    public function actionDownloadFile()
    {
        $id = Yii::$app->request->post('id');
        $file = Storage::findOne($id);

        if ($file) {
            $path = Yii::getAlias('@app') . '/../' . Yii::$app->setting->getValue('storage::path') . '/' . $file->name;

            if (!file_exists($path)) {
                Storage::deleteAll(['id_storage' => $file->id_storage]);
                Yii::$app->session->setFlash('error', Module::t('File not found!'));
            }
            return Yii::$app->response->sendFile($path, $file->title . '.' . pathinfo($path, PATHINFO_EXTENSION));
        }
        Yii::$app->session->setFlash('error', Module::t('File not found!'));
    }


    public function actionRenameFile($id)
    {
        $model = Storage::findOne($id);
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
        return $this->renderPartial('_share', [
            'model' => $model,
        ]);
    }

    public function actionCopyFile()
    {
        $id = Yii::$app->request->post('id');
        $sourceModel = Storage::findOne($id);
        if (!$sourceModel) {
            Yii::$app->session->setFlash('error', Module::t('File not found!'));
            return '';
        }
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $storagePath = Yii::getAlias('@app') . '/../' . Yii::$app->setting->getValue('storage::path');
        $filePath = $storagePath . '/' . $sourceModel->name;

        if (!$sourceModel)
            Yii::$app->session->setFlash('error', Module::t('File not found!'));

        if (!file_exists($filePath)) {
            Storage::deleteAll(['id_storage' => $sourceModel->id_storage]);
            Yii::$app->session->setFlash('error', Module::t('File not found!'));
            return '';
        }
        $newModel = $sourceModel->copyFile();

        if ($newModel)
            Yii::$app->session->setFlash('success', Module::t('File copied successfully!'));
        else
            Yii::$app->session->setFlash('error', Module::t('File could not be copied!'));
    }

    public function actionDeleteFile()
    {
        $fileId = Yii::$app->request->post('id');

        if (Yii::$app->request->isPost && Yii::$app->request->validateCsrfToken()) {
            $file = Storage::findOne($fileId);

            if ($file) {
                $path = Yii::getAlias('@app') . '/../' . Yii::$app->setting->getValue('storage::path') . '/' . $file->name;

                if (!file_exists($path)) {
                    Storage::deleteAll(['id_storage' => $file->id_storage]);
                    Yii::$app->session->setFlash('error', Module::t('File not found!'));
                }
                if ($file->deleteFile())
                    Yii::$app->session->setFlash('success', Module::t('File deleted successfully!'));
                else
                    Yii::$app->session->setFlash('error', Module::t('File not found!'));
            } else
                Yii::$app->session->setFlash('error', Module::t('File not found!'));
        }
    }

    public function actionPickerModal()
    {
        $query = Storage::find();
        $searchModel = new StorageSearch();
        $id_directory = Yii::$app->request->get('id_directory');
        $dataProvider = $searchModel->search($this->request->queryParams);
        $fileDataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $fileDataProvider->query->andWhere(['id_directory' => $id_directory]);

        $extensions = Yii::$app->request->get('fileExtensions', []);

        if (!empty($extensions) && is_array($extensions)) {
            $orConditions = ['or'];  

            foreach ($extensions as $extension) {
                $orConditions[] = ['like', 'name', '.' . ltrim($extension, '.')];
            }
            
            $query->andWhere($orConditions);
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
                'pageSize' => self::DEFAULT_PAGE_SIZE-1,
            ],
        ]);

        $directories = StorageDirectory::find()
            ->andWhere(['id_parent' => $id_directory])
            ->orderBy(['id_directory' => SORT_DESC])
            ->all();

        $files = Storage::find()
            ->andWhere(['id_directory' => $id_directory])
            ->orderBy(['id_storage' => SORT_DESC])
            ->all();
            
        $pagination = $dataProvider->getPagination();

        return $this->renderAjax('@portalium/storage/widgets/views/_picker-modal', [
            'dataProvider' => $dataProvider,  
            'directoryDataProvider' => $directoryDataProvider,
            'directories' => $directories,
            'files'  => $files,
            'pagination'  => $pagination,
        ]);
    }

    public function actionFileList()
    {
        $query = Storage::find();
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
        Yii::$app->response->format = \yii\web\Response::FORMAT_HTML;

        $q = Yii::$app->request->get('q', '');
        $id_directory = Yii::$app->request->get('id_directory');
        $isPicker = Yii::$app->request->get('isPicker', false);

        // File query
        $fileQuery = Storage::find();
        if (!empty($q)) {
            $fileQuery->andFilterWhere(['like', 'title', $q]);
        }
        if ($id_directory !== null) {
            $fileQuery->andWhere(['id_directory' => $id_directory]);
        }

        $fileDataProvider = new \yii\data\ActiveDataProvider([
            'query' => $fileQuery,
            'pagination' => ['pageSize' => self::DEFAULT_PAGE_SIZE],
            'sort' => ['defaultOrder' => ['id_storage' => SORT_DESC]],
        ]);

        // Directory query
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
            'pagination' => ['pageSize' => self::DEFAULT_PAGE_SIZE-1],
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
        $model = new StorageDirectory();

        if (Yii::$app->request->isPost) {
            if ($model->load(Yii::$app->request->post())) {
                $id_directory = Yii::$app->request->post('id_directory');
                if ($id_directory === 'null' || $id_directory == 0)
                    $model->id_parent = null;
                else
                    $model->id_parent = $id_directory;
                $baseName = trim($model->name) !== '' ? $model->name : Module::t('New Folder');
                $name = $baseName;
                $counter = 1;

                while (StorageDirectory::find()
                    ->where(['id_parent' => $model->id_parent, 'name' => $name])
                    ->exists()) {
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
    
    public function actionRenameFolder($id, $id_directory)
    {
        $model = StorageDirectory::findOne(['id_directory' => $id]);
        if (!$model) {
            Yii::$app->session->setFlash('error', Module::t('Folder not found!'));
            return '';
        }
        if (Yii::$app->request->post()) {
            Yii::warning("veriler: ", json_encode(Yii::$app->request->post()));
            $oldName = $model->name;
            if ($model->load(Yii::$app->request->post()) && $model->validate()) {
                if ($oldName !== $model->name) {
                    $baseName = $model->name;
                    $name = $baseName;
                    $counter = 1;
                    while (StorageDirectory::find()
                        ->where(['name' => $name, 'id_parent' => $model->id_parent])
                        ->andWhere(['<>', 'id_directory', $id])
                        ->exists()) {
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
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $folder = StorageDirectory::findOne(['id_directory' => $id]);

        if (!$folder) {
            Yii::$app->session->setFlash('error', Module::t('Folder not found!'));
            return ['success' => false, 'message' => Module::t('Folder not found!')];
        }

        $this->deleteFolderRecursive($folder);

        Yii::$app->session->setFlash('success', Module::t('Folder and its contents deleted successfully!'));
    }

    protected function deleteFolderRecursive($folder)
    {
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
}