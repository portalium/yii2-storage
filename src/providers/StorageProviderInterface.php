<?php

namespace portalium\storage\providers;

/**
 * Contract for a physical storage backend.
 *
 * A provider only deals with raw bytes addressed by a flat key (the Storage
 * record's `name` column, e.g. "a1b2c3.png"). Directory hierarchy, titles and
 * permissions live in the database, never in the provider.
 *
 * Because of that flat layout, move and rename operations touch the database
 * only and never reach a provider.
 */
interface StorageProviderInterface
{
    /**
     * Store a local (usually temporary) file under $key.
     *
     * @param string $key           Flat storage key, e.g. "a1b2c3.png"
     * @param string $localPath     Existing readable local file
     * @param bool   $deleteSource  Move semantics: remove $localPath on success
     */
    public function write(string $key, string $localPath, bool $deleteSource = false): bool;

    /**
     * Open $key for reading.
     *
     * @return resource|false Stream handle the caller must fclose()
     */
    public function readStream(string $key);

    public function delete(string $key): bool;

    public function exists(string $key): bool;

    /**
     * @return int Size in bytes, or 0 when the key is missing.
     */
    public function size(string $key): int;

    public function copy(string $sourceKey, string $destKey): bool;

    /**
     * Materialise $key as a local file path.
     *
     * Needed by consumers that cannot work on streams: Imagick thumbnailing and
     * ZipArchive::addFile(). For local backends this returns the real path with
     * $isTemporary = false; remote backends download to a temp file and set it
     * to true, meaning the caller must delete the file when done.
     *
     * @param bool $isTemporary Set by reference; true when the caller owns the file
     * @return string|false Local path, or false when the key is missing
     */
    public function localCopy(string $key, ?bool &$isTemporary = null);

    /**
     * Identifier persisted in storage.disk, e.g. "local" or "blaze".
     */
    public function getName(): string;

    /**
     * Provider record id persisted in storage.id_provider, when one backs this
     * instance. Null for providers resolved the legacy way.
     */
    public function getProviderId(): ?int;
}
