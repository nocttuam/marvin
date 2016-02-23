<?php
namespace Marvin\Shell\Apache;

use Marvin\Contracts\Host;
use Marvin\Contracts\Execute as ExecuteInterface;
use Marvin\Config\Repository as ConfigRepository;

class Execute implements ExecuteInterface
{
    /**
     * @var ConfigRepository
     */
    protected $host;

    /**
     * Set the Virtual Host Manager
     *
     * @param Host $host
     */
    public function setHost(Host $host)
    {
        $this->host = $host;
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
        $apachePath = $this->host->get('apache-path');
        $temp       = $this->host->get('temp-directory');

        $origin  = $temp . DIRECTORY_SEPARATOR . $file;
        $destiny = $apachePath . DIRECTORY_SEPARATOR . 'sites-available';

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