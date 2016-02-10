<?php
namespace Marvin\Hosts;

use Marvin\Filesystem\Filesystem;

class Apache
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * Configurations list
     *
     * @var array
     */
    protected $configurations;

    /**
     * Apache constructor.
     *
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem     = $filesystem;
        $this->configurations = [
            'ip'           => '192.168.42.42',
            'server-admin' => 'webmaster@marvin',
            'log-path'     => '${APACHE_LOG_DIR}',
        ];
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
        $this->configurations[$key] = $value;

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
        }
        return $value;
    }

    /**
     * If IP is invalid throw exception
     *
     * @param string $ip
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

    /**
     * Get the specified configuration value of the virtual host object
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        if (key_exists($key, $this->configurations)) {
            return $this->configurations[$key];
        }

        return $this->configurations;
    }

    /**
     * Create a temporary file containing virtual host configurations
     *
     * @param string $fileName
     *
     * @return int
     */
    public function create($fileName)
    {
        $DS   = DIRECTORY_SEPARATOR;
        $file = realpath('.') . $DS . 'app' . $DS . 'tmp' . $DS . $fileName . '.conf';

        return $this->filesystem->put($file, $this->buildContent());
    }

    /**
     * Host configuration template
     *
     * @return string
     */
    protected function buildContent()
    {
        $ip      = $this->resolveIp();
        $alias   = $this->buildAliases();
        $content = <<<CONF
<VirtualHost {$ip}>
    ServerAdmin {$this->configurations['server-admin']}
    ServerName {$this->configurations['server-name']}
    ServerAlias {$alias}
    DocumentRoot {$this->configurations['document-root']}

    ErrorLog {$this->configurations['log-path']}/{$this->configurations['server-name']}-error.log
    CustomLog {$this->configurations['log-path']}/{$this->configurations['server-name']}-access.log combined

    <Directory {$this->configurations['document-root']}>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
CONF;

        return $content;
    }

    /**
     * Build serverAlias used in host configurations
     *
     * @return string
     */
    protected function buildAliases()
    {
        $alias = 'www.' . $this->configurations['server-name'];
        if (isset($this->configurations['server-alias']) && ! empty($this->configurations['server-alias'])) {
            $alias .= ' ' . $this->configurations['server-alias'];
        }

        return $alias;
    }

    /**
     * Join IP and Port to use in host configurations.
     * Format used in host file is IP:port.
     *
     * @return string
     */
    protected function resolveIp()
    {
        $ip = $this->configurations['ip'];
        if (isset($this->configurations['port']) && ! empty($this->configurations['port'])) {
            $ip .= ':' . $this->configurations['port'];
        }

        return $ip;
    }
}