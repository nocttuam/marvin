<?php

use Marvin\Hosts\Apache;

class ApacheTest extends PHPUnit_Framework_TestCase
{
    protected $filesystem;

    protected $apacheConfigPath;

    protected function setUp()
    {
        $this->filesystem       = Mockery::mock('Illuminate\Filesystem\Filesystem');
        $this->apacheConfigPath = __DIR__ . DIRECTORY_SEPARATOR . 'apache2';
        $this->createFolderStructure();
    }

    protected function tearDown()
    {
        $this->destructFolderStructure();
    }

    protected function createFolderStructure()
    {
        if ( ! file_exists($this->apacheConfigPath)) {
            mkdir($this->apacheConfigPath, 0777, true);
            file_put_contents(
                $this->apacheConfigPath . DIRECTORY_SEPARATOR . 'existent.host.conf',
                'Old Configs'
            );
        }
    }

    protected function destructFolderStructure()
    {
        if (file_exists($this->apacheConfigPath)) {
            foreach (array_diff(scandir($this->apacheConfigPath), ['.', '..']) as $item) {
                $file = $this->apacheConfigPath . DIRECTORY_SEPARATOR . $item;
                if (is_dir($file)) {
                    rmdir($file);
                }
                unlink($file);
            }
            rmdir($this->apacheConfigPath);
        }
    }

    public function testIfFilesystemIsCorretlyIstance()
    {
        $apache = new Apache($this->filesystem, $this->apacheConfigPath);
        $this->assertAttributeInstanceOf(
            'Illuminate\Filesystem\Filesystem',
            'filesystem',
            $apache
        );

        $this->assertAttributeContains($this->apacheConfigPath, 'configurations', $apache);
    }

    public function testShouldGetRequiredAttributes()
    {
        $apache = new Apache($this->filesystem, $this->apacheConfigPath);

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
        $apache = new Apache($this->filesystem, $this->apacheConfigPath);

        $apache->ip('192.168.42.42');
        $this->assertEquals('192.168.42.42', $apache->get('ip'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Use a valid IP
     */
    public function testIfIpIsValid()
    {
        $apache = new Apache($this->filesystem, $this->apacheConfigPath);

        $apache->ip('42.42.42');
    }

    public function testCreateConfigurationFileToApacheUsingBasicConfigurations()
    {
        $expects = <<<CONF
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

        $filesystem = new Illuminate\Filesystem\Filesystem;

        $apache = new Apache($filesystem, $this->apacheConfigPath);

        $apache->serverPath('/home/trillian/site/public')
               ->serverName('marvin.localhost');
        $apache->create();
        $this->assertFileExists(__DIR__ . '/apache2/marvin.localhost.conf');
        $this->assertEquals($expects, file_get_contents(__DIR__ . '/apache2/marvin.localhost.conf'));
    }

    public function testCreateConfigurationFileToApacheUsingFullConfigurations()
    {
        $expects = <<<CONF
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

        $filesystem = new Illuminate\Filesystem\Filesystem;

        $apache = new Apache($filesystem, $this->apacheConfigPath);

        $apache->ip('192.168.4.2')
               ->port('8080')
               ->serverAdmin('marvin@emailhost')
               ->serverName('marvin.dev')
               ->serverAlias(['marvin.local.dev', 'marvin.develop.host'])
               ->serverPath('/home/marvin/site/public')
               ->logPath('/home/logs/');

        $apache->create();
        $this->assertFileExists(__DIR__ . '/apache2/marvin.dev.conf');
        $this->assertEquals($expects, file_get_contents(__DIR__ . '/apache2/marvin.dev.conf'));
    }

    public function testShouldThrowExceptionIfConfigurationsHaveOtherFileOfSameName()
    {
        $filesystem = new Illuminate\Filesystem\Filesystem;

        $apache = new Apache($filesystem, $this->apacheConfigPath);

        $apache->serverPath('/home/trillian/site/public')
               ->serverName('existent.host');

        $apache->create();

    }
}
