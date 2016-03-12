<?php
namespace Marvin\Hosts;


use Marvin\Contracts\HostManager;
use Marvin\Config\Repository as ConfigRepository;
use Marvin\Filesystem\Filesystem;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class EtcHostsManager
{
    /**
     * Path for the hosts file.
     * In Linux the default is /etc/host
     *
     * @var string
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
    protected $tempDir;

    /**
     * @var \Marvin\Contracts\HostManager;
     */
    protected $host;

    protected $sectionWrap = [
        'init' => '#=========Sirius CybeC - Entry=========',
        'end'  => '#=========Sirius CybeC - Exit=========',
    ];

    /**
     * HostsFileManager constructor.
     *
     * @param Filesystem       $filesystem
     * @param ConfigRepository $configRepository
     */
    public function __construct(Filesystem $filesystem, ConfigRepository $configRepository)
    {
        $this->filesystem       = $filesystem;
        $this->configRepository = $configRepository;
        $this->filePath         = $this->configRepository->get('hostsfile.path');
        $this->tempDir          = $this->configRepository->get('app.temporary-dir');
    }

    /**
     * Read hosts file if it exist in $filePath.
     * If file exist return your content, if not return exception message.
     *
     * @return string
     */
    public function getContent()
    {
        try {
            return $this->filesystem->get($this->filePath);
        } catch (FileNotFoundException $e) {
            return $e->getMessage();
        }
    }

    public function getSection()
    {
        $content = $this->getContent();
        $section = false;
        if (preg_match('/(' . $this->sectionWrap['init'] . ')(.*)(' . $this->sectionWrap['end'] . ')/s', $content,
            $matches)) {

            $section['all']     = $matches[0];
            $section['init']    = $matches[1];
            $section['content'] = $matches[2];
            $section['end']     = $matches[3];
        }

        return $section;
    }

    public function addHost(HostManager $host)
    {
        $this->host = $host;

        $content    = $this->getContent();
        $section    = $this->getSection();
        $hostConfig = $this->getHostConfig();
        $new        = true;
        if ($section) {
            $new = false;
        }
        $section    = $this->buildSection($section, $hostConfig, $new);
        $newContent = $this->buildNewFileContent($content, $section, $new);

        $tempFile = $this->tempDir . DIRECTORY_SEPARATOR . 'hosts';
        $result   = $this->filesystem->put($tempFile, $newContent);

        return $result;
    }

    protected function getHostConfig()
    {
        $ip          = $this->host->get('ip');
        $id          = $this->host->get('id');
        $serverName  = $this->host->get('server-name');
        $serverAlias = $this->host->get('server-alias');
        if (is_array($serverAlias)) {
            $serverAlias = '';
        }

        $config = trim($ip . ' ' . $serverName . ' ' . $serverAlias);
        $config .= ' ### Marvin ID: ' . $id;

        return trim($config);
    }

    protected function buildSection($section, $hostConfig, $new)
    {
        $newSection = $this->sectionWrap['init'];
        $newSection .= $new ? PHP_EOL : $section['content'];
        $newSection .= $hostConfig;
        $newSection .= PHP_EOL;
        $newSection .= $this->sectionWrap['end'];

        return $newSection;
    }

    protected function buildNewFileContent($content, $section, $new)
    {
        if ( ! $new) {
            return preg_replace('/(' . $this->sectionWrap['init'] . ')(.*)(' . $this->sectionWrap['end'] . ')/s',
                $section, $content);
        }

        return $content . PHP_EOL . PHP_EOL . $section;
    }
}