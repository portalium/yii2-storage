<?php

namespace portalium\storage\controllers\web;

use portalium\storage\models\StorageDirectory;
use portalium\storage\Module;
use portalium\web\Controller;
use portalium\storage\models\Storage;
use portalium\storage\models\StorageSearch;
use portalium\storage\helpers\StoragePermissionHelper;
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
        return $behaviors;
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

        $fileExtensions = Yii::$app->request->get('fileExtensions', []);

        if (is_string($fileExtensions) && !empty($fileExtensions)) {
            $fileExtensions = explode(',', $fileExtensions);
        }
        if (!is_array($fileExtensions)) {
            $fileExtensions = [];
        }

        $fileExtensions = array_filter($fileExtensions, function ($ext) {
            return !empty(trim($ext));
        });


        $id_user = Yii::$app->user->id;
        $fileDataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $fileDataProvider->query->andWhere(['id_directory' => $id_directory]);
        
        // Get user's workspace IDs for shared files
        $userWorkspaceIds = \portalium\workspace\models\WorkspaceUser::find()
            ->select('id_workspace')
            ->where(['id_user' => $id_user])
            ->column();
        
        if (!$isPicker || !\Yii::$app->user->can('storageWebDefaultManage')) {
            // Include own files + files shared with user + files shared with user's workspaces + files in shared directories + files in full storage shares
            $fileDataProvider->query->andWhere([
                'or',
                // Own files
                ['{{%storage_storage}}.id_user' => $id_user],
                // Files shared directly with user
                [
                    'and',
                    ['in', '{{%storage_storage}}.id_storage', 
                        \portalium\storage\models\StorageShare::find()
                            ->select('id_storage')
                            ->where(['is_active' => 1])
                            ->andWhere(['shared_with_type' => \portalium\storage\models\StorageShare::TYPE_USER])
                            ->andWhere(['id_shared_with' => $id_user])
                            ->andWhere(['or',
                                ['expires_at' => null],
                                ['>', 'expires_at', date('Y-m-d H:i:s')]
                            ])
                    ]
                ],
                // Files shared with user's workspaces
                [
                    'and',
                    ['in', '{{%storage_storage}}.id_storage', 
                        \portalium\storage\models\StorageShare::find()
                            ->select('id_storage')
                            ->where(['is_active' => 1])
                            ->andWhere(['shared_with_type' => \portalium\storage\models\StorageShare::TYPE_WORKSPACE])
                            ->andWhere(['in', 'id_shared_with', $userWorkspaceIds])
                            ->andWhere(['or',
                                ['expires_at' => null],
                                ['>', 'expires_at', date('Y-m-d H:i:s')]
                            ])
                    ]
                ],
                // Files in directories shared directly with user
                [
                    'and',
                    ['in', '{{%storage_storage}}.id_directory', 
                        \portalium\storage\models\StorageShare::find()
                            ->select('id_directory')
                            ->where(['is_active' => 1])
                            ->andWhere(['shared_with_type' => \portalium\storage\models\StorageShare::TYPE_USER])
                            ->andWhere(['id_shared_with' => $id_user])
                            ->andWhere(['or',
                                ['expires_at' => null],
                                ['>', 'expires_at', date('Y-m-d H:i:s')]
                            ])
                    ]
                ],
                // Files in directories shared with user's workspaces
                [
                    'and',
                    ['in', '{{%storage_storage}}.id_directory', 
                        \portalium\storage\models\StorageShare::find()
                            ->select('id_directory')
                            ->where(['is_active' => 1])
                            ->andWhere(['shared_with_type' => \portalium\storage\models\StorageShare::TYPE_WORKSPACE])
                            ->andWhere(['in', 'id_shared_with', $userWorkspaceIds])
                            ->andWhere(['or',
                                ['expires_at' => null],
                                ['>', 'expires_at', date('Y-m-d H:i:s')]
                            ])
                    ]
                ],
                // Files from users who shared their full storage with user
                [
                    'and',
                    ['in', '{{%storage_storage}}.id_user',
                        \portalium\storage\models\StorageShare::find()
                            ->select('id_user_owner')
                            ->where(['is_active' => 1])
                            ->andWhere(['shared_with_type' => \portalium\storage\models\StorageShare::TYPE_USER])
                            ->andWhere(['id_shared_with' => $id_user])
                            ->andWhere(['id_storage' => null])
                            ->andWhere(['id_directory' => null])
                            ->andWhere(['or',
                                ['expires_at' => null],
                                ['>', 'expires_at', date('Y-m-d H:i:s')]
                            ])
                    ]
                ],
                // Files from full storage shares with user's workspaces
                [
                    'and',
                    ['in', '{{%storage_storage}}.id_user',
                        \portalium\storage\models\StorageShare::find()
                            ->select('id_user_owner')
                            ->where(['is_active' => 1])
                            ->andWhere(['shared_with_type' => \portalium\storage\models\StorageShare::TYPE_WORKSPACE])
                            ->andWhere(['in', 'id_shared_with', $userWorkspaceIds])
                            ->andWhere(['id_storage' => null])
                            ->andWhere(['id_directory' => null])
                            ->andWhere(['or',
                                ['expires_at' => null],
                                ['>', 'expires_at', date('Y-m-d H:i:s')]
                            ])
                    ]
                ],
            ]);
        }

        if (!empty($fileExtensions) && is_array($fileExtensions)) {
            $orConditions = ['or'];
            foreach ($fileExtensions as $extension) {
                $cleanExtension = '.' . ltrim(trim($extension), '.');
                $orConditions[] = ['like', 'name', '%' . $cleanExtension, false];
            }

            if (count($orConditions) > 1) {
                $fileDataProvider->query->andWhere($orConditions);
            }
        }

        $fileDataProvider->pagination->pageSize = self::DEFAULT_PAGE_SIZE;

        $directoryQuery = StorageDirectory::find()
            ->andWhere(['id_parent' => $id_directory])
            ->orderBy(['id_directory' => SORT_DESC]);
        
        // Include own directories + directories shared with user + directories from full storage shares
        if (!$isPicker || !\Yii::$app->user->can('storageWebDefaultManageDirectory')) {
            $directoryQuery->andWhere([
                'or',
                // Own directories
                ['{{%storage_storage_directory}}.id_user' => $id_user],
                // Directories shared directly with user
                [
                    'and',
                    ['in', '{{%storage_storage_directory}}.id_directory', 
                        \portalium\storage\models\StorageShare::find()
                            ->select('id_directory')
                            ->where(['is_active' => 1])
                            ->andWhere(['shared_with_type' => \portalium\storage\models\StorageShare::TYPE_USER])
                            ->andWhere(['id_shared_with' => $id_user])
                            ->andWhere(['or',
                                ['expires_at' => null],
                                ['>', 'expires_at', date('Y-m-d H:i:s')]
                            ])
                    ]
                ],
                // Directories shared with user's workspaces
                [
                    'and',
                    ['in', '{{%storage_storage_directory}}.id_directory', 
                        \portalium\storage\models\StorageShare::find()
                            ->select('id_directory')
                            ->where(['is_active' => 1])
                            ->andWhere(['shared_with_type' => \portalium\storage\models\StorageShare::TYPE_WORKSPACE])
                            ->andWhere(['in', 'id_shared_with', $userWorkspaceIds])
                            ->andWhere(['or',
                                ['expires_at' => null],
                                ['>', 'expires_at', date('Y-m-d H:i:s')]
                            ])
                    ]
                ],
                // Directories from users who shared their full storage with user
                [
                    'and',
                    ['in', '{{%storage_storage_directory}}.id_user',
                        \portalium\storage\models\StorageShare::find()
                            ->select('id_user_owner')
                            ->where(['is_active' => 1])
                            ->andWhere(['shared_with_type' => \portalium\storage\models\StorageShare::TYPE_USER])
                            ->andWhere(['id_shared_with' => $id_user])
                            ->andWhere(['id_storage' => null])
                            ->andWhere(['id_directory' => null])
                            ->andWhere(['or',
                                ['expires_at' => null],
                                ['>', 'expires_at', date('Y-m-d H:i:s')]
                            ])
                    ]
                ],
                // Directories from full storage shares with user's workspaces
                [
                    'and',
                    ['in', '{{%storage_storage_directory}}.id_user',
                        \portalium\storage\models\StorageShare::find()
                            ->select('id_user_owner')
                            ->where(['is_active' => 1])
                            ->andWhere(['shared_with_type' => \portalium\storage\models\StorageShare::TYPE_WORKSPACE])
                            ->andWhere(['in', 'id_shared_with', $userWorkspaceIds])
                            ->andWhere(['id_storage' => null])
                            ->andWhere(['id_directory' => null])
                            ->andWhere(['or',
                                ['expires_at' => null],
                                ['>', 'expires_at', date('Y-m-d H:i:s')]
                            ])
                    ]
                ],
            ]);
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
        if (!\Yii::$app->user->can('storageWebDefaultManage')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
        $model = new Storage();
        $searchModel = new StorageSearch();
        $id_directory = Yii::$app->request->get('id_directory');
        $isPicker = Yii::$app->request->get('isPicker', false);

        $fileExtensions = Yii::$app->request->get('fileExtensions', []);

        // Normalize fileExtensions
        if (is_string($fileExtensions) && !empty($fileExtensions)) {
            $fileExtensions = explode(',', $fileExtensions);
        }
        if (!is_array($fileExtensions)) {
            $fileExtensions = [];
        }

        // Remove empty strings
        $fileExtensions = array_filter($fileExtensions, function ($ext) {
            return !empty(trim($ext));
        });

        $fileDataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $fileDataProvider->query->andWhere(['id_directory' => $id_directory]);

        if (!empty($fileExtensions) && is_array($fileExtensions)) {
            $orConditions = ['or'];
            foreach ($fileExtensions as $extension) {
                $cleanExtension = '.' . ltrim(trim($extension), '.');
                $orConditions[] = ['like', 'name', '%' . $cleanExtension, false];
            }

            if (count($orConditions) > 1) {
                $fileDataProvider->query->andWhere($orConditions);
            }
        }

        $fileDataProvider->pagination->pageSize = self::DEFAULT_PAGE_SIZE;


        $directoryQuery = StorageDirectory::find()
            ->andWhere(['id_parent' => $id_directory])
            ->orderBy(['id_directory' => SORT_DESC]);
        
        // Manage sayfasında tüm klasörleri göster (admin erişimi)
        
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
            if (!\Yii::$app->user->can('storageWebDefaultManageDirectory') && 
                !\Yii::$app->user->can('storageWebDefaultManageDirectoryOwn', ['model' => $directoryModel]) && 
                !\Yii::$app->workspace->can('storage', 'storageWebDefaultManageDirectory', ['model' => $directoryModel])) {
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

                    $currentUserId = Yii::$app->user->id;

                    if (!Storage::find()->where(['title' => $filename . $extension, 'id_directory' => $id_directory, 'id_user' => $currentUserId])->exists()) {
                        $model->title = $filename . $extension;
                    } else {
                        $counter = 1;
                        $newTitle = "{$filename} ({$counter}){$extension}";
                        while (Storage::find()->where(['title' => $newTitle, 'id_directory' => $id_directory, 'id_user' => $currentUserId])->exists()) {
                            $counter++;
                            $newTitle = "{$filename} ({$counter}){$extension}";
                        }
                        $model->title = $newTitle;
                    }
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
        
        // Check global permissions
        $hasGlobalPermission = \Yii::$app->user->can('storageWebDefaultDownloadFile') 
            || \Yii::$app->user->can('storageWebDefaultDownloadFileOwn', ["model" => $file]) 
            || \Yii::$app->workspace->can('storage', 'storageWebDefaultIndex', ['model' => $file]);
        
        // Check share permissions - VIEW permission is enough for download
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
            $cleanName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $cleanName); // change space and symbols to underline

            $filename = $cleanName . '.' . $ext;

            return Yii::$app->response->sendFile($path, $filename, ['inline' => false]);
        }
        Yii::$app->session->setFlash('error', Module::t('File not found!'));
    }

    public function actionRenameFile($id)
    {
        $model = Storage::findOne($id);
        
        // Check global permissions
        $hasGlobalPermission = \Yii::$app->user->can('storageWebDefaultRenameFile') 
            || \Yii::$app->user->can('storageWebDefaultRenameFileOwn', ["model" => $model]) 
            || \Yii::$app->workspace->can('storage', 'storageWebDefaultRenameFile', ['model' => $model]);
        
        // Check share permissions - need EDIT or MANAGE permission
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

    public function actionUpdateFile($id)
    {
        $model = Storage::findOne($id);
        
        // Check global permissions
        $hasGlobalPermission = \Yii::$app->user->can('storageWebDefaultUpdateFile') 
            || \Yii::$app->user->can('storageWebDefaultUpdateFileOwn', ["model" => $model]) 
            || \Yii::$app->workspace->can('storage', 'storageWebDefaultUpdateFile', ['model' => $model]);
        
        // Check share permissions - need EDIT or MANAGE permission
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

                    if (in_array($model->file->extension, ['jpg','jpeg','png'])) {
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

    public function actionUpdateAccess()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $id = Yii::$app->request->post('id');
        $access = Yii::$app->request->post('access');

        $model = \portalium\storage\models\Storage::findOne($id);
        if (!$model) {
            return ['success' => false, 'message' => 'Dosya bulunamadı'];
        }

        if (!\Yii::$app->user->can('storageWebDefaultShareFile') &&
            !\Yii::$app->user->can('storageWebDefaultShareFileOwn', ["model" => $model]) &&
            !\Yii::$app->workspace->can('storage', 'storageWebDefaultShareFile', ['model' => $model])) {
            return ['success' => false, 'message' => 'Yetkiniz yok'];
        }

        $model->access = ($access === 'public') ? $model::ACCESS_PUBLIC : $model::ACCESS_PRIVATE;

        if ($model->save(false)) {
            return ['success' => true, 'message' => 'Erişim seviyesi güncellendi'];
        } else {
            return ['success' => false, 'message' => 'Kaydedilirken hata oluştu'];
        }
    }

    public function actionShareFile($id)
    {
        $model = Storage::findOne($id);
        if (!$model) {
            throw new \yii\web\NotFoundHttpException(Module::t('File not found!'));
        }
        
        // Check global permissions
        $hasGlobalPermission = \Yii::$app->user->can('storageWebDefaultShareFile') 
            || \Yii::$app->user->can('storageWebDefaultShareFileOwn', ["model" => $model]) 
            || \Yii::$app->workspace->can('storage', 'storageWebDefaultShareFileOwn', ['model' => $model]);
        
        // Check if user has MANAGE permission through share
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
        
        // Check global permissions
        $hasGlobalPermission = \Yii::$app->user->can('storageWebDefaultShareDirectory') 
            || \Yii::$app->user->can('storageWebDefaultShareDirectoryOwn', ["model" => $directory]) 
            || \Yii::$app->workspace->can('storage', 'storageWebDefaultShareDirectory', ['model' => $directory]);
        
        // Check if user has MANAGE permission through share
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
        
        // Only admin can share other users' full storage
        if ($userId != Yii::$app->user->id && !\Yii::$app->user->can('storageWebDefaultShareFullStorage')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
        
        return $this->renderPartial('_share', [
            'userId' => $userId,
            'shareType' => 'storage',
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

        // Global + Own + Workspace permission check
        $hasGlobalPermission =
            \Yii::$app->user->can('storageWebDefaultCopyFile') ||
            \Yii::$app->user->can('storageWebDefaultCopyFileOwn', ['model' => $sourceModel]) ||
            \Yii::$app->workspace->can('storage', 'storageWebDefaultCopyFile', ['model' => $sourceModel]);

        // Check share permissions - VIEW permission is enough for copy
        $hasSharePermission = \portalium\storage\models\StorageShare::hasAccess(
            \Yii::$app->user->id,
            $sourceModel,
            null,
            \portalium\storage\models\StorageShare::PERMISSION_VIEW
        );

        if (!$hasGlobalPermission && !$hasSharePermission) {
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

        // Normalize fileExtensions - Handle for String
        if (is_string($fileExtensions) && !empty($fileExtensions)) {
            $fileExtensions = explode(',', $fileExtensions);
        }
        if (!is_array($fileExtensions)) {
            $fileExtensions = [];
        }
        
        if (is_string($allowedExtensions) && !empty($allowedExtensions)) {
            $allowedExtensions = json_decode($allowedExtensions, true) ?: [];
        }
        if (!is_array($allowedExtensions)) {
            $allowedExtensions = [];
        }

        $fileExtensions = array_filter($fileExtensions, function ($ext) {
            return !empty(trim($ext));
        });

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

        if ((!\Yii::$app->user->can('storageWebDefaultIndexOwn') && !\Yii::$app->workspace->can('storage', 'storageWebDefaultIndex'))
            || $isPicker
        ) {
            $query->andWhere([
                'or',
                ['id_user' => Yii::$app->user->id],
                ['id_workspace' => Yii::$app->workspace->id]
            ]);
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
        if ((!\Yii::$app->user->can('storageWebDefaultManageDirectory') && !\Yii::$app->workspace->can('storage', 'storageWebDefaultManageDirectory')) || $isPicker) {
            $directoryQuery->andWhere(['id_user' => Yii::$app->user->id]);
            /* $directoryQuery->andWhere([
                'or',
                ['id_user' => Yii::$app->user->id],
                ['id_workspace' => Yii::$app->workspace->id]
            ]); */
        }
        $directoryDataProvider = new ActiveDataProvider([
            'query' => $directoryQuery,
            'pagination' => [
                'pageSize' => self::DEFAULT_PAGE_SIZE - 1,
            ],
        ]);

        $directories = StorageDirectory::find()
            ->andWhere(['id_parent' => $id_directory])
            ->orderBy(['id_directory' => SORT_DESC])
            ->all();

        // Apply the same filter for Files
        $filesQuery = Storage::find()->andWhere(['id_directory' => $id_directory]);

        if (!empty($fileExtensions) && is_array($fileExtensions)) {
            $orConditions = ['or'];
            foreach ($fileExtensions as $extension) {
                $cleanExtension = '.' . ltrim(trim($extension), '.');
                $orConditions[] = ['like', 'name', '%' . $cleanExtension, false];
            }
            if (count($orConditions) > 1) {
                $filesQuery->andWhere($orConditions);
            }
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
            // Remove Bootstrap and JS files
            $output = preg_replace(
                '#<script[^>]+src=["\']?/assets/[^"\']+/(jquery\.js|yii\.js|bootstrap\.bundle\.js|tab\.js|jquery\.min\.js)[^"\']*["\'][^>]*>#i',
                '',
                $output
            );
        }

        return $output;
    }

    public function actionFileList()
    {
        if (!\Yii::$app->user->can('storageWebDefaultFileList') && !\Yii::$app->workspace->can('storage', 'storageWebDefaultFileList')) {
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
        if (!\Yii::$app->user->can('storageWebDefaultSearch') && !\Yii::$app->workspace->can('storage', 'storageWebDefaultSearch')) {
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

        $id_user = Yii::$app->user->id;
        $fileQuery = Storage::find();
        
        if (!empty($q)) {
            $fileQuery->andFilterWhere(['like', 'title', $q]);
        }
        if ($id_directory !== null) {
            $fileQuery->andWhere(['id_directory' => $id_directory]);
        }

        // Get user's workspace IDs for shared files
        $userWorkspaceIds = \portalium\workspace\models\WorkspaceUser::find()
            ->select('id_workspace')
            ->where(['id_user' => $id_user])
            ->column();

        // Include own files + shared files + files in shared directories + files in full storage shares
        if (!\Yii::$app->user->can('storageWebDefaultIndex') && 
            !\Yii::$app->workspace->can('storage', 'storageWebDefaultIndex')) {
            $fileQuery->andWhere([
                'or',
                // Own files
                ['{{%storage_storage}}.id_user' => $id_user],
                // Files shared directly with user
                [
                    'and',
                    ['in', '{{%storage_storage}}.id_storage', 
                        \portalium\storage\models\StorageShare::find()
                            ->select('id_storage')
                            ->where(['is_active' => 1])
                            ->andWhere(['shared_with_type' => \portalium\storage\models\StorageShare::TYPE_USER])
                            ->andWhere(['id_shared_with' => $id_user])
                            ->andWhere(['or',
                                ['expires_at' => null],
                                ['>', 'expires_at', date('Y-m-d H:i:s')]
                            ])
                    ]
                ],
                // Files shared with user's workspaces
                [
                    'and',
                    ['in', '{{%storage_storage}}.id_storage', 
                        \portalium\storage\models\StorageShare::find()
                            ->select('id_storage')
                            ->where(['is_active' => 1])
                            ->andWhere(['shared_with_type' => \portalium\storage\models\StorageShare::TYPE_WORKSPACE])
                            ->andWhere(['in', 'id_shared_with', $userWorkspaceIds])
                            ->andWhere(['or',
                                ['expires_at' => null],
                                ['>', 'expires_at', date('Y-m-d H:i:s')]
                            ])
                    ]
                ],
                // Files in directories shared directly with user
                [
                    'and',
                    ['in', '{{%storage_storage}}.id_directory', 
                        \portalium\storage\models\StorageShare::find()
                            ->select('id_directory')
                            ->where(['is_active' => 1])
                            ->andWhere(['shared_with_type' => \portalium\storage\models\StorageShare::TYPE_USER])
                            ->andWhere(['id_shared_with' => $id_user])
                            ->andWhere(['or',
                                ['expires_at' => null],
                                ['>', 'expires_at', date('Y-m-d H:i:s')]
                            ])
                    ]
                ],
                // Files in directories shared with user's workspaces
                [
                    'and',
                    ['in', '{{%storage_storage}}.id_directory', 
                        \portalium\storage\models\StorageShare::find()
                            ->select('id_directory')
                            ->where(['is_active' => 1])
                            ->andWhere(['shared_with_type' => \portalium\storage\models\StorageShare::TYPE_WORKSPACE])
                            ->andWhere(['in', 'id_shared_with', $userWorkspaceIds])
                            ->andWhere(['or',
                                ['expires_at' => null],
                                ['>', 'expires_at', date('Y-m-d H:i:s')]
                            ])
                    ]
                ],
                // Files from users who shared their full storage with user
                [
                    'and',
                    ['in', '{{%storage_storage}}.id_user',
                        \portalium\storage\models\StorageShare::find()
                            ->select('id_user_owner')
                            ->where(['is_active' => 1])
                            ->andWhere(['shared_with_type' => \portalium\storage\models\StorageShare::TYPE_USER])
                            ->andWhere(['id_shared_with' => $id_user])
                            ->andWhere(['id_storage' => null])
                            ->andWhere(['id_directory' => null])
                            ->andWhere(['or',
                                ['expires_at' => null],
                                ['>', 'expires_at', date('Y-m-d H:i:s')]
                            ])
                    ]
                ],
                // Files from full storage shares with user's workspaces
                [
                    'and',
                    ['in', '{{%storage_storage}}.id_user',
                        \portalium\storage\models\StorageShare::find()
                            ->select('id_user_owner')
                            ->where(['is_active' => 1])
                            ->andWhere(['shared_with_type' => \portalium\storage\models\StorageShare::TYPE_WORKSPACE])
                            ->andWhere(['in', 'id_shared_with', $userWorkspaceIds])
                            ->andWhere(['id_storage' => null])
                            ->andWhere(['id_directory' => null])
                            ->andWhere(['or',
                                ['expires_at' => null],
                                ['>', 'expires_at', date('Y-m-d H:i:s')]
                            ])
                    ]
                ],
            ]);
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

        // Include own directories + shared directories + directories from full storage shares
        if (!\Yii::$app->user->can('storageWebDefaultIndex') && 
            !\Yii::$app->workspace->can('storage', 'storageWebDefaultIndex')) {
            $directoryQuery->andWhere([
                'or',
                // Own directories
                ['{{%storage_storage_directory}}.id_user' => $id_user],
                // Directories shared directly with user
                [
                    'and',
                    ['in', '{{%storage_storage_directory}}.id_directory', 
                        \portalium\storage\models\StorageShare::find()
                            ->select('id_directory')
                            ->where(['is_active' => 1])
                            ->andWhere(['shared_with_type' => \portalium\storage\models\StorageShare::TYPE_USER])
                            ->andWhere(['id_shared_with' => $id_user])
                            ->andWhere(['or',
                                ['expires_at' => null],
                                ['>', 'expires_at', date('Y-m-d H:i:s')]
                            ])
                    ]
                ],
                // Directories shared with user's workspaces
                [
                    'and',
                    ['in', '{{%storage_storage_directory}}.id_directory', 
                        \portalium\storage\models\StorageShare::find()
                            ->select('id_directory')
                            ->where(['is_active' => 1])
                            ->andWhere(['shared_with_type' => \portalium\storage\models\StorageShare::TYPE_WORKSPACE])
                            ->andWhere(['in', 'id_shared_with', $userWorkspaceIds])
                            ->andWhere(['or',
                                ['expires_at' => null],
                                ['>', 'expires_at', date('Y-m-d H:i:s')]
                            ])
                    ]
                ],
                // Directories from users who shared their full storage with user
                [
                    'and',
                    ['in', '{{%storage_storage_directory}}.id_user',
                        \portalium\storage\models\StorageShare::find()
                            ->select('id_user_owner')
                            ->where(['is_active' => 1])
                            ->andWhere(['shared_with_type' => \portalium\storage\models\StorageShare::TYPE_USER])
                            ->andWhere(['id_shared_with' => $id_user])
                            ->andWhere(['id_storage' => null])
                            ->andWhere(['id_directory' => null])
                            ->andWhere(['or',
                                ['expires_at' => null],
                                ['>', 'expires_at', date('Y-m-d H:i:s')]
                            ])
                    ]
                ],
                // Directories from full storage shares with user's workspaces
                [
                    'and',
                    ['in', '{{%storage_storage_directory}}.id_user',
                        \portalium\storage\models\StorageShare::find()
                            ->select('id_user_owner')
                            ->where(['is_active' => 1])
                            ->andWhere(['shared_with_type' => \portalium\storage\models\StorageShare::TYPE_WORKSPACE])
                            ->andWhere(['in', 'id_shared_with', $userWorkspaceIds])
                            ->andWhere(['id_storage' => null])
                            ->andWhere(['id_directory' => null])
                            ->andWhere(['or',
                                ['expires_at' => null],
                                ['>', 'expires_at', date('Y-m-d H:i:s')]
                            ])
                    ]
                ],
            ]);
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
        if (!\Yii::$app->user->can('storageWebDefaultNewFolder') && 
            !\Yii::$app->user->can('storageWebDefaultNewFolderOwn') && 
            !\Yii::$app->workspace->can('storage', 'storageWebDefaultNewFolder')) {
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
                    if (!\Yii::$app->user->can('storageWebDefaultManageDirectory') && 
                        !\Yii::$app->user->can('storageWebDefaultManageDirectoryOwn', ['model' => $directoryModel]) && 
                        !\Yii::$app->workspace->can('storage', 'storageWebDefaultManageDirectory', ['model' => $directoryModel])) {
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

    public function actionRenameFolder($id)
    {
        $model = StorageDirectory::findOne(['id_directory' => $id]);
        
        // Check global permissions
        $hasGlobalPermission = \Yii::$app->user->can('storageWebDefaultRenameFolder') 
            || \Yii::$app->user->can('storageWebDefaultRenameFolderOwn', ["model" => $model]) 
            || \Yii::$app->workspace->can('storage', 'storageWebDefaultRenameFolder', ['model' => $model]);
        
        // Check share permissions - need EDIT or MANAGE permission
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

    public function actionDeleteFolder($id, $id_directory = null)
    {
        $folder = StorageDirectory::findOne(['id_directory' => $id]);
        
        // Check global permissions
        $hasGlobalPermission = \Yii::$app->user->can('storageWebDefaultDeleteFolder') 
            || \Yii::$app->user->can('storageWebDefaultDeleteFolderOwn', ["model" => $folder]) 
            || \Yii::$app->workspace->can('storage', 'storageWebDefaultDeleteFolder', ['model' => $folder]);
        
        // Check share permissions - need MANAGE permission for deletion
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

    public function actionGetFileAttributes($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $file = \portalium\storage\models\Storage::findOne($id);
        if (!$file) {
            return ['error' => 'File not found'];
        }
        
        // Check global permissions
        $hasGlobalPermission = \Yii::$app->user->can('storageWebDefaultIndex') 
            || \Yii::$app->workspace->can('storage', 'storageWebDefaultIndex', ['model' => $file]);
        
        // Check share permissions - VIEW permission is enough
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

    public function actionGetFile($id, $access_token = null)
    {
        try {
            $file = $this->findModel($id);
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 404;
            Yii::$app->response->content = Module::t('The requested file does not exist.');
            return Yii::$app->response;
        }

        if ($file->access == Storage::ACCESS_PRIVATE && !Yii::$app->user->can('storageWebDefaultGetFile', ['model' => $file]) && !Yii::$app->workspace->can('storage', 'storageWebDefaultGetFile', ['model' => $file])) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }

        $path = Yii::$app->basePath . '/../' . Yii::$app->setting->getValue('storage::path') . '/' . $file->name;

        if (file_exists($path)) {
            $fileExtension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $path);
            finfo_close($finfo);

            $response = Yii::$app->response;

            if ($mimeType === 'application/pdf') {
                $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
                $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'");
                $response->headers->set('Content-Disposition', 'inline; filename="' . $file->title . '.pdf"');
                $response->headers->set('Cache-Control', 'public, max-age=3600');

                return $response->sendFile($path, $file->title . '.pdf');
            }

            return $response->sendFile($path, $file->title . '.' . $fileExtension);
        } else {
            Yii::$app->response->statusCode = 404;
            Yii::$app->response->content = Module::t('The requested file does not exist.');
            return Yii::$app->response;
        }
    }

    public function actionGenerateMissingThumbnails()
    {
        $updated = Storage::generateMissingThumbnails();
        return;
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

        // Permission checks using helper
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

        // Generate token for link type
        if ($shared_with_type === \portalium\storage\models\StorageShare::TYPE_LINK) {
            $share->generateShareToken();
        }

        if ($share->save()) {
            return ['success' => true, 'message' => Module::t('Share created successfully!'), 'share' => $share];
        } else {
            return ['success' => false, 'message' => Module::t('Failed to create share!'), 'errors' => $share->errors];
        }
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

        // Permission checks using helper
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

        // Render shares list HTML
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

        // Permission check using helper
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

        // Permission check using helper
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

        // Get all active shares for this user
        $shares = \portalium\storage\models\StorageShare::find()
            ->where(['is_active' => 1])
            ->andWhere(['OR',
                ['expires_at' => null],
                ['>', 'expires_at', date('Y-m-d H:i:s')]
            ])
            ->andWhere(['OR',
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

        // Get all shares created by this user
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

