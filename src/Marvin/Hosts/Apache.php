<?php
namespace Marvin\Hosts;


use Marvin\Filesystem\Filesystem;

class Apache
{
    protected $filesystem;

    protected $configurations;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem     = $filesystem;
        $this->configurations = [
            'ip'           => '192.168.42.42',
            'server-admin' => 'webmaster@marvin',
            'log-path'     => '${APACHE_LOG_DIR}',
        ];
    }

    public function set($key, $value)
    {
        $this->parseParameters($key, $value);
        $this->configurations[$key] = $value;

        return $this;
    }

    protected function parseParameters($key, $value)
    {
        if ('ip' === $key) {
            $this->validateIP($value);
        }
        if ('server-name' === $key) {
            $this->id($value);
        }
    }

    protected function validateIP($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            throw new \InvalidArgumentException('This is not a valid IP: ' . $ip);
        }
    }

    protected function id($name)
    {
        $this->set('id', md5($name));
    }

    public function get($key = null)
    {
        if (is_null($key)) {
            return $this->configurations;
        }
        if (key_exists($key, $this->configurations)) {
            return $this->configurations[$key];
        }
    }

    public function create($fileName)
    {
        $DS   = DIRECTORY_SEPARATOR;
        $file = realpath('.') . $DS . 'app' . $DS . 'tmp' . $DS . $fileName . '.conf';

        return $this->filesystem->put($file, $this->buildContent());
    }

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

    protected function buildAliases()
    {
        $alias = 'www.' . $this->configurations['server-name'];
        if (isset($this->configurations['server-alias']) && ! empty($this->configurations['server-alias'])) {
            $alias .= ' ' . implode(' ', $this->configurations['server-alias']);
        }

        return $alias;
    }

    protected function resolveIp()
    {
        $ip = $this->configurations['ip'];
        if (isset($this->configurations['port']) && ! empty($this->configurations['port'])) {
            $ip .= ':' . $this->configurations['port'];
        }

        return $ip;
    }
}