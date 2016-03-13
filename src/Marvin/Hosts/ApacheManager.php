<?php
namespace Marvin\Hosts;

use Marvin\Contracts\HostManager;
use Marvin\Contracts\Execute;
use Marvin\Filesystem\Template;
use Marvin\Config\Repository as ConfigRepository;

class ApacheManager implements HostManager
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @var Template
     */
    protected $template;

    /**
     * @var Execute
     */
    protected $execute;


    protected $host = 'apache';


    /**
     * Apache constructor.
     *
     * @param ConfigRepository $configRepository
     * @param Template         $template
     */
    public function __construct(ConfigRepository $configRepository, Template $template)
    {
        $this->configRepository = $configRepository;
        $this->template         = $template;
        $this->configRepository->set('apache.host', $this->host);
    }


    /**
     * Set IP used to access virtual host
     * If IP is invalid throw exception
     *
     * @param $ip
     *
     * @throws \InvalidArgumentException
     *
     * @return $this
     */
    public function setIP($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            throw new \InvalidArgumentException('This is not a valid IP: ' . $ip);
        }
        $this->configRepository->set('apache.ip', $ip);

        return $this;
    }

    /**
     * Set Port number used to access virtual host
     *
     * @param $port
     *
     * @return $this
     */
    public function setPort($port)
    {
        $this->configRepository->set('apache.port', $port);

        return $this;
    }


    /**
     * Name used to identify the virtual host
     *
     * @param $serverName
     *
     * @return $this
     */
    public function setServerName($serverName)
    {
        $this->setID($serverName);
        $this->configRepository->set('apache.server-name', $serverName);

        return $this;
    }

    /**
     * Generate a virtual host id
     *
     * @param string $name
     */
    protected function setID($name)
    {
        $this->configRepository->set('apache.id', md5($name));
    }

    /**
     * Alternate names for a host
     *
     * @param array $serverAlias
     *
     * @return $this
     */
    public function setServerAlias(array $serverAlias)
    {
        $this->configRepository->set('apache.server-alias', implode(' ', $serverAlias));

        return $this;
    }

    /**
     * Email include in error messages
     *
     * @param $serverAdmin
     *
     * @return $this
     */
    public function setServerAdmin($serverAdmin)
    {
        $this->configRepository->set('apache.server-admin', $serverAdmin);

        return $this;
    }

    /**
     * Directory containing main documents tree visible in web
     *
     * @param $path
     *
     * @return $this
     */
    public function setDocumentRoot($path)
    {
        $this->configRepository->set('apache.document-root', $path);

        return $this;
    }

    /**
     * Location where will log errors
     *
     * @param $path
     *
     * @return $this
     */
    public function setLogDir($path)
    {
        $this->configRepository->set('apache.log-dir', $path);

        return $this;
    }

    /**
     * Set name used to the configuration file
     *
     * @param $name
     */
    public function setFileName($name)
    {
        if ( ! preg_match('/(.conf)$/', $name)) {
            $name .= '.conf';
        }
        $this->configRepository->set('apache.file-name', $name);
    }

    /**
     * Get the specified configuration value of the virtual host object
     *
     * @param string $key
     *
     * @param bool   $default
     *
     * @return mixed
     */
    public function get($key = null, $default = false)
    {
        if (($default || ! $this->configRepository->has('apache.' . $key)) &&
            $this->configRepository->has('default.' . $key)
        ) {
            return $this->configRepository->get('default.' . $key);
        }

        if ($this->configRepository->has('apache.' . $key)) {
            return $this->configRepository->get('apache.' . $key);
        }

        return $this->configRepository->all();
    }

    /**
     * Create a temporary file containing virtual host configurations
     *
     * @return bool
     */
    public function create()
    {
        $apache   = $this->configRepository->get('apache');
        $defaults = $this->configRepository->get('defaults');
        $tags     = array_merge($defaults, $apache);

        return $this->template->compile($this);
    }

    /**
     * Execute commands of the system to create and enable virtual host
     *
     * @param Execute $execute
     *
     * @return Execute
     */
    public function execute(Execute $execute)
    {
        $execute->setHostManager($this);
        return $execute;
    }
}