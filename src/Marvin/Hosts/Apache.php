<?php
namespace Marvin\Hosts;


class Apache
{

    protected $configurations = [
        'ip'           => '192.168.42.42',
        'server_admin' => 'webmaster@marvin',
        'log_path'     => '${APACHE_LOG_DIR}',
    ];

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

    public function serverPath($path)
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

    public function configurations()
    {
        $ip          = $this->makeIpAndPort();
        $admin       = $this->get('server_admin');
        $serverName  = $this->get('server_name');
        $serverAlias = $this->makeServerAlias();
        $serverPath  = $this->get('server_path');
        $logPath     = $this->get('log_path');

        return $this->hostConfiguration($ip, $admin, $serverName, $serverAlias, $serverPath, $logPath);
    }

    protected function makeIpAndPort()
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

    protected function hostConfiguration($ip, $admin, $serverName, $serverAlias, $serverPath, $logPath)
    {
        $config = <<<EOD
<VirtualHost $ip>
    ServerAdmin $admin
    ServerName $serverName
    ServerAlias www.$serverAlias
    DocumentRoot $serverPath

    ErrorLog $logPath/marvin.localhost-error.log
    CustomLog $logPath/marvin.localhost-access.log combined

    <Directory $serverPath>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
EOD;

        return $config;
    }
}