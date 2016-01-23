<?php

use Marvin\Hosts\Apache;

class ApacheTest extends PHPUnit_Framework_TestCase
{
    public function testShouldGetRequiredAttributes()
    {
        $apache = new Apache();

        $serverName  = 'marvin.dev';
        $serverAlias = ['marvin.local', 'marvin.host'];
        $serverPath  = '/home/trillian/site/public';

        $apache->serverName($serverName)
               ->serverAlias($serverAlias)
               ->serverPath($serverPath);

        $this->assertEquals($serverName, $apache->get('server_name'));
        $this->assertEquals($serverAlias, $apache->get('server_alias'));
        $this->assertEquals($serverPath, $apache->get('server_path'));
    }

    public function testSetValidIp()
    {
        $apache = new Apache();

        $apache->ip('192.168.42.42');
        $this->assertEquals('192.168.42.42', $apache->get('ip'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Use a valid IP
     */
    public function testIfIpIsValid()
    {
        $apache = new Apache();

        $apache->ip('42.42.42');
    }

    public function testShouldReturnValidApacheConfigurations()
    {
        $apache = new Apache();

        $apache->serverPath('/home/trillian/site/public')
               ->serverName('marvin.localhost');

        $config = <<<CONF
<VirtualHost 192.168.42.42>
    ServerAdmin webmaster@marvin
    ServerName marvin.localhost
    ServerAlias www.marvin.localhost
    DocumentRoot /home/trillian/site/public

    ErrorLog \${APACHE_LOG_DIR}/marvin.localhost-error.log
    CustomLog \${APACHE_LOG_DIR}/marvin.localhost-access.log combined

    <Directory /home/trillian/site/public>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
CONF;

        $this->assertEquals($config, $apache->configurations());
    }

    public function testShouldReturnValidFullApacheConfigurations()
    {
        $apache = new Apache();

        $apache->ip('192.168.4.2')
               ->port('8080')
               ->serverAdmin('marvin@emailhost')
               ->serverName('marvin.dev')
               ->serverAlias(['marvin.local.dev', 'marvin.develop.host'])
               ->serverPath('/home/marvin/site/public')
               ->logPath('/home/logs/');

        $config = <<<CONF
<VirtualHost 192.168.4.2:8080>
    ServerAdmin marvin@emailhost
    ServerName marvin.dev
    ServerAlias www.marvin.dev marvin.local.dev marvin.develop.host
    DocumentRoot /home/marvin/site/public

    ErrorLog /home/logs/marvin.localhost-error.log
    CustomLog /home/logs/marvin.localhost-access.log combined

    <Directory /home/marvin/site/public>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
CONF;

        $this->assertEquals($config, $apache->configurations());
    }
}
