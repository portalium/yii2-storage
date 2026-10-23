<?php

namespace portalium\storage\providers;

use Yii;

/**
 * Stores files on the local filesystem, under the `storage::path` setting.
 *
 * This is the historical behaviour of the module: every file lives flat inside
 * that directory, named by the Storage record's `name` column.
 */
class LocalProvider implements StorageProviderInterface
{
    const NAME = 'local';

    /**
     * @var string|null Resolved base path, cached per request.
     */
    private $basePath;

    /**
     * @var int|null Record id, when this instance came from a provider row.
     */
    private $providerId;

    public function __construct(?int $providerId = null)
    {
        $this->providerId = $providerId;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getProviderId(): ?int
    {
        return $this->providerId;
    }

    /**
     * Absolute path of the storage directory, without a trailing slash.
     */
    public function getBasePath(): string
    {
        if ($this->basePath === null) {
            $configured = (string) Yii::$app->setting->getValue('storage::path');
            $this->basePath = rtrim(Yii::getAlias('@app') . '/../' . $configured, '/');
        }

        return $this->basePath;
    }

    /**
     * Absolute path for a storage key.
     *
     * Keys are flat file names; anything that looks like a path traversal is
     * rejected so a crafted `name` cannot escape the storage directory.
     */
    public function getPath(string $key): string
    {
        return $this->getBasePath() . '/' . self::sanitizeKey($key);
    }

    public function write(string $key, string $localPath, bool $deleteSource = false): bool
    {
        $target = $this->getPath($key);
        $dir = dirname($target);

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return false;
        }

        if ($deleteSource && @rename($localPath, $target)) {
            return true;
        }

        if (!@copy($localPath, $target)) {
            return false;
        }

        if ($deleteSource) {
            @unlink($localPath);
        }

        return true;
    }

    public function readStream(string $key)
    {
        $path = $this->getPath($key);

        if (!is_file($path)) {
            return false;
        }

        return @fopen($path, 'rb');
    }

    public function delete(string $key): bool
    {
        $path = $this->getPath($key);

        if (!is_file($path)) {
            return true;
        }

        return @unlink($path);
    }

    public function exists(string $key): bool
    {
        return is_file($this->getPath($key));
    }

    public function size(string $key): int
    {
        $path = $this->getPath($key);

        if (!is_file($path)) {
            return 0;
        }

        return (int) filesize($path);
    }

    public function copy(string $sourceKey, string $destKey): bool
    {
        $source = $this->getPath($sourceKey);

        if (!is_file($source)) {
            return false;
        }

        return @copy($source, $this->getPath($destKey));
    }

    public function localCopy(string $key, ?bool &$isTemporary = null)
    {
        $isTemporary = false;
        $path = $this->getPath($key);

        return is_file($path) ? $path : false;
    }

    /**
     * Strips directory components so a key can only address a file directly
     * inside the storage directory.
     */
    public static function sanitizeKey(string $key): string
    {
        return basename(str_replace('\\', '/', $key));
    }
}
