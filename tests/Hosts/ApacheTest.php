<?php
namespace Marvin\Hosts;

use Marvin\Hosts\Apache;
use Marvin\Filesystem\Filesystem;

function exec($comand)
{
    return $comand;
}

class ApacheTest extends \PHPUnit_Framework_TestCase
{
    protected $filesystem;

    protected $apacheConfigDir;

    protected function setUp()
    {
        $this->filesystem      = \Mockery::mock('Marvin\Filesystem\Filesystem');
        $this->apacheConfigDir = realpath('.') . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'test-apache2';
        $this->createFolderStructure();
    }

    protected function createFolderStructure()
    {
        if ( ! file_exists($this->apacheConfigDir)) {
            mkdir($this->apacheConfigDir . DIRECTORY_SEPARATOR . 'site-available', 0777, true);
            file_put_contents(
                $this->apacheConfigDir . DIRECTORY_SEPARATOR . 'existent.host.conf',
                'Old Configs'
            );
        }
    }

    protected function tearDown()
    {
        $this->destructFolderStructure($this->apacheConfigDir);
    }

    protected function destructFolderStructure($directory)
    {
        if (file_exists($directory)) {
            $files = glob($directory . '/*');
            foreach ($files as $file) {
                is_dir($file) ? $this->destructFolderStructure($file) : unlink($file);
            }
            rmdir($directory);
            return;
        }
    }

    public function testIfFilesystemIsCorrectlyInstance()
    {
        $apache = new Apache($this->filesystem, $this->apacheConfigDir);
        $this->assertAttributeInstanceOf(
            'Marvin\Filesystem\Filesystem',
            'filesystem',
            $apache
        );

        $this->assertAttributeContains($this->apacheConfigDir, 'configurations', $apache);
    }

    public function testShouldGetRequiredAttributes()
    {
        $apache = new Apache($this->filesystem, $this->apacheConfigDir);

        $serverName  = 'marvin.dev';
        $serverAlias = ['marvin.local', 'marvin.host'];
        $serverPath  = '/home/trillian/site/public';

        $apache->serverName($serverName)
               ->serverAlias($serverAlias)
               ->documentRoot($serverPath);

        $this->assertEquals($serverName, $apache->get('server_name'));
        $this->assertEquals($serverAlias, $apache->get('server_alias'));
        $this->assertEquals($serverPath, $apache->get('server_path'));
    }

    public function testSetValidIp()
    {
        $apache = new Apache($this->filesystem, $this->apacheConfigDir);

        $apache->ip('192.168.42.42');
        $this->assertEquals('192.168.42.42', $apache->get('ip'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Use a valid IP
     */
    public function testIfIpIsValid()
    {
        $apache = new Apache($this->filesystem, $this->apacheConfigDir);

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

        $filesystem = new Filesystem;

        $apache = new Apache($filesystem, $this->apacheConfigDir);

        $apache->documentRoot('/home/trillian/site/public')
               ->serverName('marvin.localhost');
        $apache->createConfigFile();
        $this->assertFileExists(realpath('.') . '/tests' . '/test-apache2/site-available/marvin.localhost.conf');
        $this->assertEquals($expects, file_get_contents(realpath('.') . '/tests' . '/test-apache2/site-available/marvin.localhost.conf'));
    }

    public function testCreateConfigurationFileToApacheUsingFullConfigurations()
    {
        $expects = <<<CONF
<VirtualHost 192.168.4.2:8080>
    ServerAdmin marvin@emailhost
    ServerName marvin.dev
    ServerAlias www.marvin.dev marvin.local.dev marvin.develop.host
    DocumentRoot /home/marvin/site/public

    ErrorLog /home/marvin/logs/marvin.localhost-error.log
    CustomLog /home/marvin/logs/marvin.localhost-access.log combined

    <Directory /home/marvin/site/public>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
CONF;

        $filesystem = new Filesystem;

        $apache = new Apache($filesystem, $this->apacheConfigDir);

        $apache->ip('192.168.4.2')
               ->port('8080')
               ->serverAdmin('marvin@emailhost')
               ->serverName('marvin.dev')
               ->serverAlias(['marvin.local.dev', 'marvin.develop.host'])
               ->documentRoot('/home/marvin/site/public')
               ->logPath('/home/marvin/logs/');

        $apache->createConfigFile();
        $this->assertFileExists(realpath('.') . '/tests' . '/test-apache2/site-available/marvin.dev.conf');
        $this->assertEquals($expects, file_get_contents(realpath('.') . '/tests' . '/test-apache2/site-available/marvin.dev.conf'));
    }

    public function testShouldThrowExceptionIfConfigurationsHaveOtherFileOfSameName()
    {
        $filesystem = new Filesystem;

        $apache = new Apache($filesystem, $this->apacheConfigDir);

        $apache->documentRoot('/home/trillian/site/public')
               ->serverName('existent.host');

        $apache->createConfigFile();

    }

    public function testShouldEnableSiteAndRestartServer()
    {
        $apache = new Apache($this->filesystem, $this->apacheConfigDir);
        $apache->serverName('marvin.dev');

        $this->assertEquals('sudo a2ensite '.$apache->get('server_name'), $apache->enableApacheSite());
        $this->assertEquals('sudo service apache restart', $apache->restartServer());
    }
}
