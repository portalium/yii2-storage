<?php

namespace portalium\storage\controllers\console;

use portalium\storage\models\Storage;
use portalium\storage\models\StorageProvider;
use portalium\storage\providers\StorageProviderFactory;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Moves stored files from one provider to another.
 *
 * Copying the bytes is only half the job: a file is found through its
 * `id_provider`, so the row has to move with it or the file becomes
 * unreachable. Each file is therefore copied, verified and only then
 * re-pointed, one at a time, so an interrupted run leaves no broken rows.
 */
class MigrateController extends Controller
{
    /**
     * @var int|null Source provider id.
     */
    public $from;

    /**
     * @var int|null Destination provider id.
     */
    public $to;

    /**
     * @var int Stop after this many files. 0 means no limit.
     */
    public $limit = 0;

    /**
     * @var bool Report what would happen without writing anything.
     */
    public $dryRun = false;

    /**
     * @var bool Remove the source copy once the destination is verified.
     */
    public $deleteSource = false;

    public function options($actionID)
    {
        return array_merge(parent::options($actionID), [
            'from', 'to', 'limit', 'dryRun', 'deleteSource',
        ]);
    }

    public function optionAliases()
    {
        return array_merge(parent::optionAliases(), [
            'f' => 'from',
            't' => 'to',
            'l' => 'limit',
            'd' => 'dryRun',
        ]);
    }

    /**
     * Lists providers with the number of files pointing at each.
     *
     * Usage: php portalium storage/migrate/list
     */
    public function actionList()
    {
        $providers = StorageProvider::find()->orderBy(['id_provider' => SORT_ASC])->all();

        if (empty($providers)) {
            $this->stdout("No providers defined.\n", Console::FG_YELLOW);

            return ExitCode::OK;
        }

        $this->stdout(sprintf("%-5s %-28s %-10s %-10s %s\n", 'ID', 'NAME', 'TYPE', 'SCOPE', 'FILES'), Console::BOLD);

        foreach ($providers as $provider) {
            $this->stdout(sprintf(
                "%-5s %-28s %-10s %-10s %d\n",
                $provider->id_provider,
                mb_substr($provider->name, 0, 27),
                $provider->type,
                $provider->isSystem() ? 'system' : 'ws:' . $provider->id_workspace,
                $provider->getFileCount()
            ));
        }

        $orphans = Storage::find()
            ->where(['type' => Storage::TYPE_FILE, 'id_provider' => null])
            ->count();

        if ($orphans > 0) {
            $this->stdout("\n{$orphans} file(s) have no provider.\n", Console::FG_YELLOW);
        }

        return ExitCode::OK;
    }

    /**
     * Copies every file from one provider to another.
     *
     * Usage:
     *   php portalium storage/migrate --from=3 --to=5 --dryRun=1
     *   php portalium storage/migrate --from=3 --to=5 --limit=10
     *   php portalium storage/migrate --from=3 --to=5
     */
    public function actionIndex()
    {
        if ($this->from === null || $this->to === null) {
            $this->stderr("--from and --to are required. Run storage/migrate/list to see provider ids.\n", Console::FG_RED);

            return ExitCode::USAGE;
        }

        if ((int) $this->from === (int) $this->to) {
            $this->stderr("Source and destination are the same provider.\n", Console::FG_RED);

            return ExitCode::USAGE;
        }

        $source = StorageProvider::findOne((int) $this->from);
        $target = StorageProvider::findOne((int) $this->to);

        if ($source === null || $target === null) {
            $this->stderr("Provider not found.\n", Console::FG_RED);

            return ExitCode::DATAERR;
        }

        if ($target->capability !== StorageProvider::CAPABILITY_STORAGE) {
            $this->stderr("Destination is an import source, not a storage backend.\n", Console::FG_RED);

            return ExitCode::DATAERR;
        }

        $query = Storage::find()
            ->where(['type' => Storage::TYPE_FILE, 'id_provider' => (int) $this->from])
            ->orderBy(['id_storage' => SORT_ASC]);

        if ($this->limit > 0) {
            $query->limit((int) $this->limit);
        }

        $total = (int) $query->count();

        if ($total === 0) {
            $this->stdout("Nothing to move.\n", Console::FG_YELLOW);

            return ExitCode::OK;
        }

        $this->stdout("From : {$source->name} (#{$source->id_provider})\n");
        $this->stdout("To   : {$target->name} (#{$target->id_provider})\n");
        $this->stdout("Files: {$total}\n");

        if ($this->deleteSource) {
            $this->stdout("Source copies will be DELETED after verification.\n", Console::FG_YELLOW);
        }

        if ($this->dryRun) {
            $this->stdout("Dry run: nothing will be written.\n\n", Console::FG_CYAN);
        } else {
            $this->stdout("\n");

            if (!$this->confirm('Proceed?')) {
                return ExitCode::OK;
            }
        }

        $sourceProvider = StorageProviderFactory::fromRecord($source);
        $targetProvider = StorageProviderFactory::fromRecord($target);

        // Prove the destination accepts writes before touching any real file:
        // an unreadable configuration otherwise fails silently, once per file.
        if (!$this->dryRun && !$this->checkDestination($targetProvider)) {
            $this->stderr(
                "Destination is not writable. Check its credentials on the provider screen.\n",
                Console::FG_RED
            );

            return ExitCode::CONFIG;
        }

        $moved = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($query->each(50) as $file) {
            $label = sprintf('#%-7s %-34s', $file->id_storage, mb_substr((string) $file->title, 0, 33));

            if (!$sourceProvider->exists($file->name)) {
                $this->stdout("{$label} MISSING at source, skipped\n", Console::FG_YELLOW);
                $skipped++;
                continue;
            }

            if ($this->dryRun) {
                $this->stdout("{$label} would move\n");
                $moved++;
                continue;
            }

            if ($this->moveOne($file, $sourceProvider, $targetProvider, $target)) {
                $this->stdout("{$label} OK\n", Console::FG_GREEN);
                $moved++;
            } else {
                $this->stdout("{$label} FAILED\n", Console::FG_RED);
                $failed++;
            }
        }

        $this->stdout("\nMoved: {$moved}  Skipped: {$skipped}  Failed: {$failed}\n", Console::BOLD);

        return $failed > 0 ? ExitCode::SOFTWARE : ExitCode::OK;
    }

    /**
     * Round-trips a small object so a broken destination stops the run early.
     */
    private function checkDestination($targetProvider): bool
    {
        $key = 'nv-migrate-check-' . bin2hex(random_bytes(6)) . '.txt';
        $tmp = tempnam(sys_get_temp_dir(), 'nvmig_');
        file_put_contents($tmp, 'check');

        try {
            if (!$targetProvider->write($key, $tmp)) {
                return false;
            }

            $ok = $targetProvider->exists($key);
            $targetProvider->delete($key);

            return $ok;
        } catch (\Throwable $e) {
            Yii::error('Destination check failed: ' . $e->getMessage(), __METHOD__);

            return false;
        } finally {
            if (is_file($tmp)) {
                @unlink($tmp);
            }
        }
    }

    /**
     * Copy one file, confirm it landed, then re-point the row.
     *
     * The row is updated last: if anything fails before that, the file still
     * resolves to the source and nothing is lost.
     */
    private function moveOne(Storage $file, $sourceProvider, $targetProvider, StorageProvider $target): bool
    {
        $isTemporary = false;
        $localPath = $sourceProvider->localCopy($file->name, $isTemporary);

        if ($localPath === false) {
            return false;
        }

        try {
            if (!$targetProvider->write($file->name, $localPath)) {
                return false;
            }

            // A provider whose credentials cannot be read reports 0 for every
            // size, so the file must be confirmed present before the sizes are
            // worth comparing.
            if (!$targetProvider->exists($file->name)) {
                Yii::error("Destination has no copy of storage {$file->id_storage} after write", __METHOD__);

                return false;
            }

            $sourceSize = $sourceProvider->size($file->name);
            $targetSize = $targetProvider->size($file->name);

            if ($sourceSize > 0 && $sourceSize !== $targetSize) {
                Yii::error(
                    "Size mismatch for storage {$file->id_storage}: {$sourceSize} vs {$targetSize}",
                    __METHOD__
                );
                $targetProvider->delete($file->name);

                return false;
            }

            // Thumbnails travel with the file; a missing one is regenerated on
            // demand, so it must not fail the move.
            if (!empty($file->thumbnail) && $sourceProvider->exists($file->thumbnail)) {
                $thumbTemp = false;
                $thumbPath = $sourceProvider->localCopy($file->thumbnail, $thumbTemp);

                if ($thumbPath !== false) {
                    $targetProvider->write($file->thumbnail, $thumbPath);

                    if ($thumbTemp && is_file($thumbPath)) {
                        @unlink($thumbPath);
                    }
                }
            }

            $file->id_provider = $target->id_provider;
            $file->disk = $targetProvider->getName();

            if (!$file->save(false, ['id_provider', 'disk'])) {
                $targetProvider->delete($file->name);

                return false;
            }

            if ($this->deleteSource) {
                $sourceProvider->delete($file->name);

                if (!empty($file->thumbnail)) {
                    $sourceProvider->delete($file->thumbnail);
                }
            }

            return true;
        } catch (\Throwable $e) {
            Yii::error("Move failed for storage {$file->id_storage}: " . $e->getMessage(), __METHOD__);

            return false;
        } finally {
            if ($isTemporary && is_file($localPath)) {
                @unlink($localPath);
            }
        }
    }
}
