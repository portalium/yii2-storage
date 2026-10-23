<?php

namespace portalium\storage\controllers\console;

use portalium\storage\models\Storage;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Produces the thumbnails that file listings expect to find ready.
 *
 * Serving a thumbnail never generates one: on a remote provider that meant
 * a download, a resize and an upload while the browser waited, which is what
 * made the file picker take tens of seconds to paint. Generation lives here
 * instead, so the cost is paid once, out of band.
 */
class ThumbnailController extends Controller
{
    /**
     * @var int Stop after this many files. 0 means no limit.
     */
    public $limit = 0;

    /**
     * @var bool Report what would happen without writing anything.
     */
    public $dryRun = false;

    public function options($actionID)
    {
        return array_merge(parent::options($actionID), [
            'limit', 'dryRun',
        ]);
    }

    public function optionAliases()
    {
        return array_merge(parent::optionAliases(), [
            'l' => 'limit',
            'd' => 'dryRun',
        ]);
    }

    /**
     * Reports how many records still lack a thumbnail.
     *
     * Usage: php portalium storage/thumbnail/status
     */
    public function actionStatus()
    {
        $pending = $this->findPending()->count();

        $this->stdout("Files awaiting a thumbnail: ", Console::BOLD);
        $this->stdout($pending . "\n");

        return ExitCode::OK;
    }

    /**
     * Generates every missing thumbnail.
     *
     * Usage:
     *   php portalium storage/thumbnail/generate --dryRun=1
     *   php portalium storage/thumbnail/generate --limit=50
     *   php portalium storage/thumbnail/generate
     */
    public function actionGenerate()
    {
        if (!extension_loaded('imagick')) {
            $this->stderr("The imagick extension is not loaded; nothing can be generated.\n", Console::FG_RED);

            return ExitCode::CONFIG;
        }

        $query = $this->findPending();

        if ($this->limit > 0) {
            $query->limit((int) $this->limit);
        }

        $records = $query->all();

        if (empty($records)) {
            $this->stdout("Nothing to do.\n", Console::FG_GREEN);

            return ExitCode::OK;
        }

        $this->stdout(count($records) . " file(s) to process.\n\n");

        $done = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($records as $record) {
            // The pending query filters on the stored mime type, but a record
            // can still be unthumbnailable for reasons only the model knows.
            if (!$record->isThumbnailable()) {
                $skipped++;
                continue;
            }

            $label = sprintf('#%-7s %s', $record->id_storage, mb_substr((string) $record->title, 0, 48));

            if ($this->dryRun) {
                $this->stdout("would generate  " . $label . "\n");
                $done++;
                continue;
            }

            if ($record->ensureThumbnail()) {
                $this->stdout("ok              " . $label . "\n", Console::FG_GREEN);
                $done++;
            } else {
                $this->stdout("failed          " . $label . "\n", Console::FG_YELLOW);
                $failed++;
            }
        }

        $this->stdout("\n");
        $this->stdout("generated: {$done}  skipped: {$skipped}  failed: {$failed}\n", Console::BOLD);

        return ExitCode::OK;
    }

    /**
     * Files that could carry a thumbnail but have none yet.
     *
     * @return \yii\db\ActiveQuery
     */
    private function findPending()
    {
        $mimeTypes = [];

        foreach (Storage::THUMBNAIL_ALLOWED_MIMES as $mimeType) {
            $mimeTypes[] = $mimeType;

            // Records store the mime type as the numeric code of the lookup
            // table, so both spellings have to be matched.
            if (isset(Storage::MIME_TYPE[$mimeType])) {
                $mimeTypes[] = Storage::MIME_TYPE[$mimeType];
            }
        }

        return Storage::find()
            ->where(['type' => Storage::TYPE_FILE])
            ->andWhere(['or', ['thumbnail' => null], ['thumbnail' => '']])
            ->andWhere(['mime_type' => $mimeTypes])
            ->orderBy(['id_storage' => SORT_DESC]);
    }
}
