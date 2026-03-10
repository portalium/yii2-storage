<?php

namespace portalium\storage\helpers;

use portalium\storage\models\Storage;
use ZipArchive;

/**
 * Helper for zipping a storage folder (recursively) into a temporary file.
 */
class StorageZipHelper
{
    /**
     * Build a ZIP archive for the given folder and return the path to the temp file.
     *
     * Caller is responsible for deleting the temp file after sending.
     *
     * @param Storage $folder        Root folder (Storage with type=directory) to zip
     * @param string  $storagePath   Absolute path to the storage base directory
     * @return string  Path to the created temp ZIP file
     * @throws \RuntimeException  When ZipArchive is unavailable or creation fails
     */
    public static function buildZip(Storage $folder, $storagePath)
    {
        if (!class_exists('ZipArchive')) {
            throw new \RuntimeException('ZipArchive extension is not available on this server.');
        }

        $zip     = new ZipArchive();
        $tmpFile = tempnam(sys_get_temp_dir(), 'storage_folder_') . '.zip';

        if ($zip->open($tmpFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not create ZIP archive.');
        }

        self::addFolderToZip($zip, $folder, $storagePath, '');

        $zip->close();

        return $tmpFile;
    }

    /**
     * Recursively add a folder's contents to the open ZipArchive.
     *
     * @param \ZipArchive $zip         Open archive
     * @param Storage     $folder      Current folder being processed (type=directory)
     * @param string      $storagePath Absolute storage base path
     * @param string      $zipPrefix   Path inside the ZIP for this folder's contents
     */
    private static function addFolderToZip(ZipArchive $zip, Storage $folder, $storagePath, $zipPrefix)
    {
        $folderZipPath = $zipPrefix . $folder->name . '/';

        // Add the directory entry itself so empty folders appear in the ZIP
        $zip->addEmptyDir($folderZipPath);

        // Add files in this directory
        $files = Storage::find()
            ->where(['id_directory' => $folder->id_storage, 'type' => Storage::TYPE_FILE])
            ->all();
        foreach ($files as $file) {
            $filePath = $storagePath . '/' . $file->name;
            if (file_exists($filePath)) {
                $ext        = pathinfo($file->name, PATHINFO_EXTENSION);
                $entryName  = $folderZipPath . $file->title . ($ext ? '.' . $ext : '');
                // Avoid duplicate entry names within the same folder
                $entryName  = self::uniqueEntryName($zip, $entryName);
                $zip->addFile($filePath, $entryName);
            }
        }

        // Recurse into sub-folders
        $subFolders = Storage::find()
            ->where(['id_directory' => $folder->id_storage, 'type' => Storage::TYPE_DIRECTORY])
            ->all();
        foreach ($subFolders as $subFolder) {
            self::addFolderToZip($zip, $subFolder, $storagePath, $folderZipPath);
        }
    }

    /**
     * Ensure an entry name is unique inside the ZIP by appending a counter if needed.
     */
    private static function uniqueEntryName(ZipArchive $zip, $entryName)
    {
        if ($zip->locateName($entryName) === false) {
            return $entryName;
        }

        $ext      = pathinfo($entryName, PATHINFO_EXTENSION);
        $base     = $ext ? substr($entryName, 0, -strlen('.' . $ext)) : $entryName;
        $counter  = 1;

        do {
            $candidate = $base . ' (' . $counter++ . ')' . ($ext ? '.' . $ext : '');
        } while ($zip->locateName($candidate) !== false);

        return $candidate;
    }
}
