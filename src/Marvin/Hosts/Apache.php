<?php
namespace Marvin\Hosts;

use Marvin\Contracts\Host;
use Marvin\Contracts\Execute;
use Marvin\Filesystem\Filesystem;
use Marvin\Filesystem\Template;
use Marvin\Config\Repository as ConfigRepository;

class Apache implements Host
{
    protected $configRepository;

    /**
     * Shell Manager.
     * To run commands necessaries to configuration.
     *
     * @var Execute
     */
    protected $execute;

    /**
     * Filesystem Manager
     *
     * @var Filesystem
     */
    protected $filesystem;

    protected $template;


    /**
     * Apache constructor.
     *
     * @param ConfigRepository $configRepository
     * @param Filesystem       $filesystem
     * @param Template         $template
     */
    public function __construct(ConfigRepository $configRepository, Filesystem $filesystem, Template $template)
    {
        $this->configRepository = $configRepository;
        $this->filesystem       = $filesystem;
        $this->template         = $template;
    }

    /**
     * Set a given configuration name and value
     *
     * @param string $key
     * @param string $value
     *
     * @return $this
     */
    public function set($key, $value)
    {
        $value = $this->parseParameters($key, $value);
        $this->configRepository->set($key, $value);

        return $this;
    }

    /**
     * Process configurations items.
     * Validate IP, build id, turn alias list in a string
     *
     * @param string $key
     * @param string $value
     *
     * @return string
     */
    protected function parseParameters($key, $value)
    {
        switch ($key) {
            case 'ip':
                $this->validateIP($value);
                break;
            case 'server-name':
                $this->id($value);
                break;
            case 'server-alias':
                return implode(' ', $value);
            case 'file-name':
                return $this->resolveFileName($value);
        }

        return $value;
    }

    /**
     * If IP is invalid throw exception
     *
     * @param string $ip
     *
     * @throws \InvalidArgumentException
     */
    protected function validateIP($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            throw new \InvalidArgumentException('This is not a valid IP: ' . $ip);
        }
    }

    /**
     * Generate a virtual host id
     *
     * @param string $name
     */
    protected function id($name)
    {
        $this->set('id', md5($name));
    }

    protected function resolveFileName($name)
    {
        if (preg_match('/(.conf)$/', $name)) {
            return $name;
        }

        return $name . '.conf';
    }

    /**
     * Get the specified configuration value of the virtual host object
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key = null)
    {
        if ($this->configRepository->has($key)) {
            return $this->configRepository->get($key);
        }

        return $this->configRepository->all();
    }

    /**
     * Create a temporary file containing virtual host configurations
     *
     * @TODO: Add get path to temporary folder in configurations file
     *
     * @return int
     */
    public function create()
    {
        $DS      = DIRECTORY_SEPARATOR;
        $file    = realpath('.') . $DS . 'app' . $DS . 'tmp' . $DS . $this->get('file-name');
        $content = $this->template->compile($this, $this->configRepository->all());

        return $this->filesystem->put($file, $content);
    }

    public function setExecute(Execute $execute)
    {
        $this->execute = $execute;

        return $this;
    }

    public function runEnableCommands(Execute $execute)
    {
        $this->setExecute($execute);
        $this->moveConfigFile();
        $this->enable();
        $this->restart();

        return true;
    }

    public function moveConfigFile()
    {
        return $this->execute->moveConfig($this->get('file-name'));
    }

    public function enable()
    {
        return $this->execute->enable($this->get('file-name'));
    }

    public function restart()
    {
        return $this->execute->restart();
    }
}