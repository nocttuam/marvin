<?php
namespace Marvin\Filesystem;

use Marvin\Hosts\Apache;
use Marvin\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class HostsFileManager
{
    /**
     * Path for the hosts file.
     * In Linux the default is /etc/host
     *
     * @var array|null
     */
    protected $filePath;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * Directory used to store temporary files
     *
     * @var string
     */
    protected $tmpDir;

    /**
     * HostsFileManager constructor.
     *
     * @param Filesystem       $filesystem
     * @param ConfigRepository $configRepository
     */
    public function __construct(Filesystem $filesystem, ConfigRepository $configRepository)
    {
        $this->filesystem = $filesystem;

        $this->configRepository = $configRepository;

        $this->filePath = $configRepository->get('host-file-path', '/etc/host');

        $this->tempDir = realpath('.') . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'tmp';

    }

    /**
     * Read hosts file if it exist in $filePath.
     * If file exist return your content, if not return exception message.
     *
     * @return string
     */
    public function read()
    {
        try {
            return $this->filesystem->get($this->filePath);
        } catch (FileNotFoundException $e) {
            return $e->getMessage();
        }
    }

    /**
     * Include new host configurations in hots file(/etc/hosts)
     *
     * @param Apache $apacheManager
     *
     * @throws FileNotFoundException
     */
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