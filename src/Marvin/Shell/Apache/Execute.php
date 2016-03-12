<?php
namespace Marvin\Shell\Apache;

use Marvin\Contracts\HostManager;
use Marvin\Contracts\Execute as ExecuteInterface;
use Marvin\Config\Repository as ConfigRepository;

class Execute implements ExecuteInterface
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @var ConfigRepository
     */
    protected $hostManager;

    /**
     * Path to temporary directory use in aplication
     *
     * @var string
     */
    protected $temporaryDir;

    /**
     * Name of the configurations file generated
     *
     * @var string
     */
    protected $fileName;

    /**
     * Execute constructor.
     *
     * @param ConfigRepository $configRepository
     */
    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
        $this->temporaryDir     = $configRepository->get('app.temporary-dir');
    }

    /**
     * Set the Virtual Host Manager
     *
     * @param HostManager $hostManager
     */
    public function setHostManager(HostManager $hostManager)
    {
        $this->hostManager = $hostManager;
        $this->fileName    = $this->hostManager->get('file-name');

    }

    /**
     * Run command a2ensite to enable new host
     *
     * @return mixed
     */
    public function enable()
    {
        return shell_exec('sudo a2ensite ' . $this->fileName);
    }

    /**
     * Restart Apache service
     *
     * @return mixed
     */
    public function restart()
    {
        return shell_exec('sudo service apache2 reload');
    }

}