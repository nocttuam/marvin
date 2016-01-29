<?php
namespace Marvin\Hosts;

use Illuminate\Filesystem\Filesystem;

class Apache
{

    private $filesystem;

    protected $configurations = [
        'ip'           => '192.168.42.42',
        'server_admin' => 'webmaster@marvin',
        'log_path'     => '${APACHE_LOG_DIR}',
    ];

    public function __construct(Filesystem $filesystem, $apacheConfigDir)
    {
        $this->filesystem                          = $filesystem;
        $this->configurations['apache_config_dir'] = '/' . trim($apacheConfigDir, '\/');
    }

    public function ip($ip)
    {
        if ( ! $this->ipIsValid($ip)) {
            throw new \InvalidArgumentException('Use a valid IP');
        }
        $this->configurations['ip'] = $ip;

        return $this;
    }

    protected function ipIsValid($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    public function port($port)
    {
        $this->configurations['port'] = $port;

        return $this;
    }

    public function serverName($name)
    {
        $this->configurations['server_name'] = $name;

        return $this;
    }

    public function serverAlias(array $alias)
    {
        $this->configurations['server_alias'] = $alias;

        return $this;
    }

    public function documentRoot($path)
    {
        $this->configurations['server_path'] = $path;

        return $this;
    }

    public function serverAdmin($admin)
    {
        $this->configurations['server_admin'] = $admin;

        return $this;
    }

    public function logPath($path)
    {
        $path                             = trim($path, '\/');
        $this->configurations['log_path'] = '/' . $path;

        return $this;
    }

    public function get($attribute)
    {
        if (isset($this->configurations[$attribute])) {
            return $this->configurations[$attribute];
        }

        return false;
    }

    public function createConfigFile()
    {
        $this->filesystem->put($this->filePath(), $this->content($this->contentParameters()));
    }

    public function enableApacheSite()
    {
        $a2ensite = exec('sudo a2ensite '.$this->configurations['server_name']);
        return $a2ensite;
    }

    public function restartServer()
    {
        $apacheRestart = exec('sudo service apache restart');
        return $apacheRestart;
    }

    protected function filePath()
    {
        $path = $this->configurations['apache_config_dir'] .
                DIRECTORY_SEPARATOR .
                'site-available' .
                DIRECTORY_SEPARATOR .
                $this->configurations['server_name'] .
                '.conf';

        return $path;
    }

    protected function contentParameters()
    {
        $configurations['ip']           = $this->resolveIP();
        $configurations['admin']        = $this->get('server_admin');
        $configurations['serverName']   = $this->get('server_name');
        $configurations['serverAlias']  = $this->makeServerAlias();
        $configurations['documentRoot'] = $this->get('server_path');
        $configurations['logPath']      = $this->get('log_path');

        return $configurations;
    }

    protected function resolveIP()
    {
        if (isset($this->configurations['port']) && ! empty($this->configurations['port'])) {
            return $this->get('ip') . ':' . $this->get('port');
        }

        return $this->get('ip');
    }

    protected function makeServerAlias()
    {
        $alias = $this->get('server_name');
        if ($this->get('server_alias')) {
            $alias = $alias . ' ' . implode(' ', $this->get('server_alias'));
        }

        return $alias;
    }

    protected function content(array $configurations)
    {
        $content = <<<EOD
<VirtualHost {$configurations['ip']}>
    ServerAdmin {$configurations['admin']}
    ServerName {$configurations['serverName']}
    ServerAlias www.{$configurations['serverAlias']}
    DocumentRoot {$configurations['documentRoot']}

    ErrorLog {$configurations['logPath']}/marvin.localhost-error.log
    CustomLog {$configurations['logPath']}/marvin.localhost-access.log combined

    <Directory {$configurations['documentRoot']}>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
EOD;

        return $content;
    }
}