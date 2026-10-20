<?php

namespace portalium\storage\providers;

use Aws\Exception\MultipartUploadException;
use Aws\S3\MultipartUploader;
use Aws\S3\S3Client;
use Yii;
use yii\base\InvalidConfigException;

/**
 * Stores files in an S3-compatible object store.
 *
 * The wire protocol is plain S3, so one class serves every compatible service:
 * Backblaze B2, AWS S3, Cloudflare R2, Google Cloud Storage (in its S3
 * interoperability mode), MinIO, Wasabi. They differ only in endpoint, region
 * and credentials, which all come from settings.
 *
 * The provider name is per-instance rather than a constant so the `disk` column
 * records which service actually holds a file. That matters when the default is
 * switched: an old record keeps reading from the service it was written to.
 *
 * Keys are flat, matching the local layout, optionally behind `storage::s3_prefix`.
 */
class S3Provider implements StorageProviderInterface
{
    /** Backblaze B2. Kept as the historical name for already-stored records. */
    const NAME = 'blaze';

    const NAME_S3 = 's3';
    const NAME_R2 = 'r2';
    const NAME_GCS = 'gcs';

    /** Seconds to wait for the TCP connection before giving up. */
    const CONNECT_TIMEOUT = 5;

    /** Seconds for a whole request. Large uploads need room, so keep it generous. */
    const REQUEST_TIMEOUT = 300;

    /** Retries on failure; the default of 3 makes a bad key hang the UI. */
    const MAX_RETRIES = 1;

    /**
     * Files above this go up in parts. A single PUT of a multi-GB training
     * archive outlives REQUEST_TIMEOUT; each part is its own request.
     */
    const MULTIPART_THRESHOLD = 64 * 1024 * 1024;

    const MULTIPART_PART_SIZE = 32 * 1024 * 1024;

    /**
     * Every S3-compatible provider identifier this class can serve.
     */
    const NAMES = [
        self::NAME,
        self::NAME_S3,
        self::NAME_R2,
        self::NAME_GCS,
    ];

    /**
     * @var string Identifier persisted in storage.disk.
     */
    private $name;

    /**
     * @var S3Client|null
     */
    private $client;

    /**
     * @var string|null
     */
    private $bucket;

    /**
     * @var string Key prefix, normalised to either '' or 'something/'.
     */
    private $prefix;

    /**
     * @var \portalium\storage\models\StorageProvider|null Record holding the credentials.
     */
    private $record;

    /**
     * @param string $name Provider identifier; defaults to Backblaze B2.
     * @param \portalium\storage\models\StorageProvider|null $record
     *        Credentials source. Without it the provider falls back to the
     *        legacy settings, which cannot distinguish between two S3 accounts.
     */
    public function __construct(string $name = self::NAME, $record = null)
    {
        $this->name = $name;
        $this->record = $record;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getProviderId(): ?int
    {
        return $this->record === null ? null : (int) $this->record->id_provider;
    }

    /**
     * Credentials for this provider.
     *
     * Reading them from the record rather than from settings is what lets two
     * S3 accounts coexist: each file points at the provider that holds it, so
     * changing the default never makes older files unreadable.
     */
    private function readConfig(): array
    {
        if ($this->record !== null) {
            return $this->record->getConfigData();
        }

        // Legacy path: settings still hold a single set of credentials.
        $setting = Yii::$app->setting;
        $config = [];

        foreach (['endpoint', 'region', 'bucket', 'key', 'secret', 'prefix'] as $name) {
            $config[$name] = (string) $setting->getValue('storage::s3_' . $name);
        }

        return $config;
    }

    /**
     * @throws InvalidConfigException when the provider configuration is incomplete.
     */
    public function getClient(): S3Client
    {
        if ($this->client !== null) {
            return $this->client;
        }

        if (!class_exists(S3Client::class)) {
            throw new InvalidConfigException(
                'aws/aws-sdk-php is required for remote storage. Run: composer require aws/aws-sdk-php'
            );
        }

        $config = $this->readConfig();
        $endpoint = trim((string) ($config['endpoint'] ?? ''));
        $region = trim((string) ($config['region'] ?? ''));
        $key = trim((string) ($config['key'] ?? ''));
        $secret = trim((string) ($config['secret'] ?? ''));
        $this->bucket = trim((string) ($config['bucket'] ?? ''));

        $missing = [];
        foreach (['endpoint' => $endpoint, 'region' => $region, 'key' => $key, 'secret' => $secret, 'bucket' => $this->bucket] as $name => $value) {
            if ($value === '') {
                $missing[] = $name;
            }
        }

        if ($missing) {
            throw new InvalidConfigException(
                'Storage provider "' . $this->name . '" is not configured. Missing: ' . implode(', ', $missing)
            );
        }

        $prefix = trim((string) ($config['prefix'] ?? ''));
        $prefix = trim($prefix, '/');
        $this->prefix = $prefix === '' ? '' : $prefix . '/';

        $this->client = new S3Client([
            'version' => 'latest',
            'region' => $region,
            'endpoint' => $endpoint,
            // B2 and most S3-compatible services expect bucket-in-path URLs.
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => $key,
                'secret' => $secret,
            ],
            // Without these the SDK retries with exponential backoff and waits
            // indefinitely to connect, so a wrong key leaves the browser hanging
            // for minutes instead of reporting the error.
            'retries' => self::MAX_RETRIES,
            'http' => [
                'connect_timeout' => self::CONNECT_TIMEOUT,
                'timeout' => self::REQUEST_TIMEOUT,
            ],
        ]);

        return $this->client;
    }

    public function getBucket(): string
    {
        if ($this->bucket === null) {
            $this->getClient();
        }

        return (string) $this->bucket;
    }

    /**
     * Full object key for a storage key, including the configured prefix.
     */
    public function getObjectKey(string $key): string
    {
        if ($this->prefix === null) {
            $this->getClient();
        }

        return $this->prefix . LocalProvider::sanitizeKey($key);
    }

    /**
     * Not every S3-compatible service accepts multipart the way AWS does, so a
     * failure here falls back to a single PUT instead of failing the write.
     * The uploader closes the stream it is given, hence a handle of its own.
     */
    private function writeInParts(string $key, string $localPath): bool
    {
        $handle = @fopen($localPath, 'rb');
        if ($handle === false) {
            return false;
        }

        try {
            // No ACL, same as putObject below: GCS rejects one on buckets with
            // uniform access.
            (new MultipartUploader($this->getClient(), $handle, [
                'bucket' => $this->getBucket(),
                'key' => $this->getObjectKey($key),
                'part_size' => self::MULTIPART_PART_SIZE,
            ]))->upload();

            return true;
        } catch (\Throwable $e) {
            Yii::warning('Multipart upload failed for ' . $key . ', retrying as one request: ' . $e->getMessage(), __METHOD__);
            // Parts already uploaded are billed until the upload is aborted.
            if ($e instanceof MultipartUploadException && !empty($e->getState()->getId()['UploadId'])) {
                try {
                    $this->getClient()->abortMultipartUpload($e->getState()->getId());
                } catch (\Throwable $ignored) {
                    Yii::warning('Could not abort the multipart upload of ' . $key, __METHOD__);
                }
            }

            return false;
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }
    }

    public function write(string $key, string $localPath, bool $deleteSource = false): bool
    {
        if (!is_file($localPath)) {
            return false;
        }

        if (filesize($localPath) > self::MULTIPART_THRESHOLD && $this->writeInParts($key, $localPath)) {
            return $this->written($key, $localPath, $deleteSource);
        }

        $handle = @fopen($localPath, 'rb');

        if ($handle === false) {
            return false;
        }

        try {
            $this->getClient()->putObject([
                'Bucket' => $this->getBucket(),
                'Key' => $this->getObjectKey($key),
                'Body' => $handle,
            ]);
        } catch (\Throwable $e) {
            Yii::error('S3 write failed for ' . $key . ': ' . $e->getMessage(), __METHOD__);

            return false;
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }

        return $this->written($key, $localPath, $deleteSource);
    }

    private function written(string $key, string $localPath, bool $deleteSource): bool
    {
        if ($deleteSource) {
            @unlink($localPath);
        }

        unset($this->existsCache[$key], $this->sizeCache[$key]);

        return true;
    }

    public function readStream(string $key)
    {
        try {
            $result = $this->getClient()->getObject([
                'Bucket' => $this->getBucket(),
                'Key' => $this->getObjectKey($key),
            ]);
        } catch (\Throwable $e) {
            Yii::error('S3 read failed for ' . $key . ': ' . $e->getMessage(), __METHOD__);

            return false;
        }

        $body = $result['Body'] ?? null;

        if ($body === null) {
            return false;
        }

        if ($body instanceof \Psr\Http\Message\StreamInterface) {
            $detached = $body->detach();

            return $detached ?: false;
        }

        return false;
    }

    public function delete(string $key): bool
    {
        try {
            $this->getClient()->deleteObject([
                'Bucket' => $this->getBucket(),
                'Key' => $this->getObjectKey($key),
            ]);
        } catch (\Throwable $e) {
            Yii::error('S3 delete failed for ' . $key . ': ' . $e->getMessage(), __METHOD__);

            return false;
        }

        unset($this->existsCache[$key], $this->sizeCache[$key]);

        return true;
    }

    /**
     * @var array<string,bool> exists() results for this request.
     */
    private $existsCache = [];

    /**
     * @var array<string,int> size() results for this request.
     */
    private $sizeCache = [];

    /**
     * Whether the object is present.
     *
     * Results are remembered for the life of the request: rendering a file list
     * asks about the same keys repeatedly, and each miss would otherwise be a
     * round trip to the bucket.
     */
    public function exists(string $key): bool
    {
        if (array_key_exists($key, $this->existsCache)) {
            return $this->existsCache[$key];
        }

        try {
            $result = $this->getClient()->doesObjectExist($this->getBucket(), $this->getObjectKey($key));
        } catch (\Throwable $e) {
            $result = false;
        }

        return $this->existsCache[$key] = $result;
    }

    public function size(string $key): int
    {
        if (array_key_exists($key, $this->sizeCache)) {
            return $this->sizeCache[$key];
        }

        try {
            $result = $this->getClient()->headObject([
                'Bucket' => $this->getBucket(),
                'Key' => $this->getObjectKey($key),
            ]);
        } catch (\Throwable $e) {
            return $this->sizeCache[$key] = 0;
        }

        // headObject already proves the object is there.
        $this->existsCache[$key] = true;

        return $this->sizeCache[$key] = (int) ($result['ContentLength'] ?? 0);
    }

    public function copy(string $sourceKey, string $destKey): bool
    {
        try {
            $this->getClient()->copyObject([
                'Bucket' => $this->getBucket(),
                'Key' => $this->getObjectKey($destKey),
                'CopySource' => $this->getBucket() . '/' . $this->getObjectKey($sourceKey),
            ]);
        } catch (\Throwable $e) {
            Yii::error('S3 copy failed for ' . $sourceKey . ': ' . $e->getMessage(), __METHOD__);

            return false;
        }

        unset($this->existsCache[$destKey], $this->sizeCache[$destKey]);

        return true;
    }

    public function localCopy(string $key, ?bool &$isTemporary = null)
    {
        $isTemporary = true;
        $stream = $this->readStream($key);

        if ($stream === false) {
            return false;
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'storage_');

        if ($tmpPath === false) {
            fclose($stream);

            return false;
        }

        // Preserve the extension so Imagick and ZipArchive behave as they do locally.
        $extension = pathinfo($key, PATHINFO_EXTENSION);

        if ($extension !== '') {
            $withExtension = $tmpPath . '.' . $extension;

            if (@rename($tmpPath, $withExtension)) {
                $tmpPath = $withExtension;
            }
        }

        $target = @fopen($tmpPath, 'wb');

        if ($target === false) {
            fclose($stream);
            @unlink($tmpPath);

            return false;
        }

        $copied = stream_copy_to_stream($stream, $target);

        fclose($stream);
        fclose($target);

        if ($copied === false) {
            @unlink($tmpPath);

            return false;
        }

        return $tmpPath;
    }
}
