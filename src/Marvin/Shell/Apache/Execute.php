<?php
namespace Marvin\Shell\Apache;

use Marvin\Shell\IExecute;
use Marvin\Config\Repository as ConfigRepository;

class Execute implements IExecute
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * Execute constructor.
     *
     * @param ConfigRepository $configRepository
     */
    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     * Move configuration file to Apache configurations directory
     *
     * @param string $file
     *
     * @return string
     */
    public function moveConfig($file)
    {
        $apachePath = $this->configRepository->get('apache-path');
        $temp     = $this->configRepository->get('temp-directory');

        $origin = $temp . DIRECTORY_SEPARATOR . $file;
        $destiny    = $apachePath . DIRECTORY_SEPARATOR . 'sites-available';

        return shell_exec('sudo mv -v ' . $origin . ' ' . $destiny);
    }

    /**
     * Run command a2ensite to enable new host
     *
     * @param string $file
     *
     * @return mixed
     */
    public function enable($file)
    {
        return shell_exec('sudo a2ensite ' . $file);
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