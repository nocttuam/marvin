<?php
namespace Marvin\Filesystem;

use Marvin\Hosts\Apache;
use Marvin\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class HostsFileManager
{
    protected $filePath;

    protected $filesystem;

    protected $configRepository;

    protected $tmpDir;

    public function __construct(Filesystem $filesystem, ConfigRepository $configRepository)
    {
        $this->filesystem = $filesystem;

        $this->configRepository = $configRepository;

        $this->filePath = $configRepository->get('host-file-path', '/etc/host');

        $this->tempDir = realpath('.') . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'tmp';

    }

    public function read()
    {
        try {
            return $this->filesystem->get($this->filePath);
        } catch (FileNotFoundException $e) {
            return $e->getMessage();
        }
    }

    public function includeHost(Apache $apacheManager)
    {
        $originalContent = $this->filesystem->get($this->filePath);
        $content = $apacheManager->get('ip');
        $content .= ' '.$apacheManager->get('server-name');
        $content .= ' '.$apacheManager->get('server-alias');
        $content .= ' # ID: ';
        $content .= $apacheManager->get('id');
        $content .= ' // Created by Marvin';

        $result = $originalContent . PHP_EOL .$content;
        $file = $this->tempDir. DIRECTORY_SEPARATOR . 'hosts';
        $this->filesystem->put($file, $result);
    }

}