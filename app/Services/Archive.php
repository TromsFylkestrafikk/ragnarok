<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Collection;
use Ragnarok\Sink\Models\RawFile;
use Ragnarok\Sink\Services\LocalFiles;
use ZipArchive;

class Archive
{
    use \Ragnarok\Sink\Traits\LogPrintf;

    /**
     * @var ZipArchive|null
     */
    protected $archive = null;

    /**
     * @var LocalFiles|null
     */
    protected $localFiles = null;

    final public function __construct(protected string $sinkId, protected string $archivePath)
    {
        $this->logPrintfInit('[%s Archive]: ', $sinkId);
        $this->localFiles = new LocalFiles($sinkId);
    }

    /**
     * @param string $sinkId
     * @param Collection<array-key, RawFile> $files
     * @param string $archiveDest
     */
    public static function toZip(string $sinkId, Collection $files, string $archiveDest): Archive
    {
        $archive = new static($sinkId, $archiveDest);
        $archive->addFiles($files);
        $archive->getZipArchive()->close();
        return $archive;
    }

    /**
     * @param Collection<array-key, RawFile> $files
     *
     * @return Archive
     */
    public function addFiles(Collection $files): Archive
    {
        $zip = $this->getZipArchive();
        $disk = $this->localFiles->getDisk();
        $this->debug('Adding %d files', $files->count());
        foreach ($files as $file) {
            $zip->addFile($disk->path($file->name), $file->name);
        }
        return $this;
    }

    /**
     * @return ZipArchive
     */
    public function getZipArchive(): ZipArchive
    {
        if ($this->archive !== null) {
            return $this->archive;
        }
        $this->archive = new ZipArchive();
        $this->archive->open($this->archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        return $this->archive;
    }
}
