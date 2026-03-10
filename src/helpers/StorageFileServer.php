<?php

namespace portalium\storage\helpers;

use Yii;
use portalium\storage\Module;
use portalium\storage\models\Storage;
use portalium\storage\models\StorageShare;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * Handles file & folder serving (download / stream) for both web and API controllers.
 *
 * Usage:
 *   return StorageFileServer::serve($model, $options);
 *
 * Options (all optional):
 *   - thumb        bool   Serve thumbnail instead of original (files only)
 *   - appToken     string App API key for app-level auth bypass
 *   - permPrefix   string RBAC prefix, e.g. 'storageWebDefault' or 'storageApiDefault'
 */
class StorageFileServer
{
    /**
     * Serve a Storage record (file or directory) as an HTTP response.
     *
     * @param Storage $model
     * @param array   $options
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     */
    public static function serve(Storage $model, array $options = [])
    {
        return $model->isDirectory()
            ? self::serveFolder($model, $options)
            : self::serveFile($model, $options);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Directory → ZIP
    // ─────────────────────────────────────────────────────────────────────────

    private static function serveFolder(Storage $folder, array $options)
    {
        $permPrefix = $options['permPrefix'] ?? 'storageWebDefault';
        $appToken   = $options['appToken'] ?? null;

        $canAccess = Yii::$app->user->can($permPrefix . 'DownloadFile')
            || Yii::$app->workspace->can('storage', $permPrefix . 'DownloadFile', ['model' => $folder])
            || $folder->id_user === Yii::$app->user->id
            || StorageShare::hasAccess(Yii::$app->user->id, null, $folder, StorageShare::PERMISSION_VIEW)
            || self::hasValidAppToken($appToken);

        if (!$canAccess) {
            throw new ForbiddenHttpException(Module::t('You do not have permission to access this folder.'));
        }

        $storagePath = self::getStoragePath();

        try {
            $tmpZip = StorageZipHelper::buildZip($folder, $storagePath);
        } catch (\RuntimeException $e) {
            throw new ServerErrorHttpException($e->getMessage());
        }

        $zipName = preg_replace('/[^\w\-.]/', '_', $folder->name) . '.zip';

        Yii::$app->response->on(\yii\base\Event::class, function () use ($tmpZip) {
            @unlink($tmpZip);
        });

        return Yii::$app->response->sendFile($tmpZip, $zipName, [
            'mimeType' => 'application/zip',
            'inline'   => false,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // File → stream
    // ─────────────────────────────────────────────────────────────────────────

    private static function serveFile(Storage $file, array $options)
    {
        $thumb      = $options['thumb'] ?? false;
        $permPrefix = $options['permPrefix'] ?? 'storageWebDefault';
        $appToken   = $options['appToken'] ?? null;

        // Public files are accessible without any permission check
        if ($file->access !== Storage::ACCESS_PUBLIC) {
            $canAccess = Yii::$app->user->can($permPrefix . 'GetFile', ['model' => $file])
                || Yii::$app->user->can($permPrefix . 'GetFileOwn', ['model' => $file])
                || Yii::$app->workspace->can('storage', $permPrefix . 'GetFile', ['model' => $file])
                || StorageShare::hasAccess(Yii::$app->user->id, $file, null, StorageShare::PERMISSION_VIEW)
                || self::hasValidAppToken($appToken);

            if (!$canAccess) {
                throw new ForbiddenHttpException(Module::t('You do not have permission to access this file.'));
            }
        }

        $storagePath = self::getStoragePath();

        if ($thumb) {
            $thumbPath = $storagePath . '/thumb_' . $file->name;
            $servePath = file_exists($thumbPath) ? $thumbPath : ($storagePath . '/' . $file->name);
            $serveTitle = 'thumb_' . $file->title;
        } else {
            $servePath  = $storagePath . '/' . $file->name;
            $serveTitle = $file->title;
        }

        if (!file_exists($servePath)) {
            throw new NotFoundHttpException(Module::t('The requested file does not exist.'));
        }

        $ext      = strtolower(pathinfo($servePath, PATHINFO_EXTENSION));
        $response = Yii::$app->response;

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $servePath);
        finfo_close($finfo);

        if ($mimeType === 'application/pdf') {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'");
            $response->headers->set('Content-Disposition', 'inline; filename="' . $serveTitle . '.pdf"');
            $response->headers->set('Cache-Control', 'public, max-age=3600');
            return $response->sendFile($servePath, $serveTitle . '.pdf');
        }

        if (strpos($mimeType, 'image/') === 0) {
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
            $response->headers->set('Cross-Origin-Resource-Policy', 'cross-origin');
        }

        return $response->sendFile($servePath, $serveTitle . '.' . $ext);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Internals
    // ─────────────────────────────────────────────────────────────────────────

    private static function getStoragePath(): string
    {
        return Yii::$app->basePath . '/../' . Yii::$app->setting->getValue('storage::path');
    }

    private static function hasValidAppToken(?string $token): bool
    {
        if ($token === null) {
            return false;
        }
        return \novavision\app\models\App::find()->where(['api_key' => $token])->exists();
    }
}
