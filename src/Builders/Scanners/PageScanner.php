<?php namespace ShvetsGroup\JetPages\Builders\Scanners;

use Symfony\Component\Finder\SplFileInfo;

class PageScanner
{
    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;
    protected $type = 'page';
    protected $regex = '#\.(txt|html|md)$#';

    public function __construct()
    {
        $this->files = app('Illuminate\Filesystem\Filesystem');
    }

    /**
     * @param $directory
     * @return array
     */
    public function scan($directory)
    {
        $files = $this->findFiles($directory);
        return $this->processFiles($files);
    }

    /**
     * @param $directory
     * @return array
     * @throws PageScanningException
     */
    public function findFiles($directory)
    {
        if (!is_dir($directory)) {
            throw new PageScanningException();
        }
        $files = $this->files->allFiles($directory);
        return array_filter($files, function ($filename) {
            return preg_match($this->regex, $filename);
        });
    }

    /**
     * @param array $files
     * @return array
     * @throws PageProcessingException
     */
    public function processFiles(array $files)
    {
        $map = [];
        foreach ($files as $file) {
            $result = $this->processFile($file);
            if (!isset($result['slug'])) {
                throw new PageProcessingException("No slug in file $file.");
            }
            $slug = (string)$result['slug'];
            $map[$slug] = $result;
        }
        return $map;
    }

    /**
     * @param SplFileInfo $file
     * @return array
     */
    public function processFile(SplFileInfo $file)
    {
        $slug = $file->getRelativePathname();
        $slug = preg_replace($this->regex, '', $slug);
        return [
            'slug' => $slug,
            'type' => $this->type,
            'path' => $file->getRealpath(),
            'src' => $file->getContents(),
        ];
    }
}