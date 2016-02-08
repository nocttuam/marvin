<?php
namespace Hosts;

use Marvin\Filesystem\Filesystem;
use Marvin\Hosts\Apache;

class ApacheTest extends \PHPUnit_Framework_TestCase
{
    protected $filesystem;

    protected function setUp()
    {
        $this->filesystem = $this->getMockBuilder('\Marvin\Filesystem\Filesystem')
                                 ->getMock();
    }

    public function testSetCorrectlyInitialProperties()
    {
        $apache = new Apache($this->filesystem);

        $this->assertAttributeInstanceOf('\Marvin\Filesystem\Filesystem', 'filesystem', $apache);
    }

    public function testShouldSetAndGetParameters()
    {
        $apache = new Apache($this->filesystem);

        $apache->set('ip', '192.168.4.2');
        $apache->set('port', '8080');
        $apache->set('server-admin', 'marvin@emailhost');
        $apache->set('server-name', 'marvin.dev');
        $apache->set('server-alias', ['marvin.local.dev', 'marvin.develop.host']);
        $apache->set('document-root', '/home/marvin/site/public');
        $apache->set('log-path', '/home/marvin/logs/');

        $expected = [
            'ip'            => '192.168.4.2',
            'port'          => '8080',
            'server-admin'  => 'marvin@emailhost',
            'server-name'   => 'marvin.dev',
            'server-alias'  => ['marvin.local.dev', 'marvin.develop.host'],
            'document-root' => '/home/marvin/site/public',
            'log-path'      => '/home/marvin/logs/',
        ];

        $this->assertCount(8, $apache->get()); // include in count id parameter
        $this->assertArraySubset($expected, $apache->get());
        $this->assertArrayHasKey('server-name', $apache->get());
        $this->assertEquals($expected['ip'], $apache->get('ip'));
        $this->assertEquals($expected['port'], $apache->get('port'));
        $this->assertEquals($expected['server-admin'], $apache->get('server-admin'));
        $this->assertEquals($expected['server-name'], $apache->get('server-name'));
        $this->assertEquals($expected['server-alias'], $apache->get('server-alias'));
        $this->assertEquals($expected['document-root'], $apache->get('document-root'));
        $this->assertEquals($expected['log-path'], $apache->get('log-path'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage This is not a valid IP:
     */
    public function testThrowExceptionIfIpIsInvalid()
    {
        $apache = new Apache($this->filesystem);

        $apache->set('ip', '42.42.42');
    }

    public function testBuildIdToHost()
    {
        $serverName = md5('mavin.host');
        $expected   = md5($serverName);
        $apache     = new Apache($this->filesystem);

        $apache->set('server-name', $serverName);

        $this->assertEquals($serverName, $apache->get('server-name'));
        $this->assertEquals($expected, $apache->get('id'));
    }

    public function testReturnCOnfigurationsForTheHost()
    {
        $content  = <<<CONF
<VirtualHost 192.168.4.2:8080>
    ServerAdmin marvin@emailhost
    ServerName marvin.dev
    ServerAlias www.marvin.dev marvin.local.dev marvin.develop.host
    DocumentRoot /home/marvin/site/public

    ErrorLog /home/marvin/logs/marvin.dev-error.log
    CustomLog /home/marvin/logs/marvin.dev-access.log combined

    <Directory /home/marvin/site/public>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
CONF;
        $fileName = 'marvin.host';
        $DS       = DIRECTORY_SEPARATOR;
        $file     = realpath('.') . $DS . 'app' . $DS . 'tmp' . $DS . $fileName . '.conf';

        $filesystem = $this->getMockBuilder('\Marvin\Filesystem\Filesystem')
                           ->setMethods(['put'])
                           ->getMock();

        $filesystem->expects($this->once())
                   ->method('put')
                   ->with($file, $content)
                   ->will($this->returnValue(true));

        $apache = new Apache($filesystem);

        $apache->set('ip', '192.168.4.2')
               ->set('port', '8080')
               ->set('server-admin', 'marvin@emailhost')
               ->set('server-name', 'marvin.dev')
               ->set('server-alias', ['marvin.local.dev', 'marvin.develop.host'])
               ->set('document-root', '/home/marvin/site/public')
               ->set('log-path', '/home/marvin/logs');

        $this->assertTrue($apache->create($fileName));
    }

    public function testReturnBasicConfigurationsForTheHost()
    {
        $content  = <<<CONF
<VirtualHost 192.168.42.42>
    ServerAdmin webmaster@marvin
    ServerName marvin.dev
    ServerAlias www.marvin.dev
    DocumentRoot /home/marvin/site/public

    ErrorLog \${APACHE_LOG_DIR}/marvin.dev-error.log
    CustomLog \${APACHE_LOG_DIR}/marvin.dev-access.log combined

    <Directory /home/marvin/site/public>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
CONF;
        $fileName = 'marvin.host';
        $DS       = DIRECTORY_SEPARATOR;
        $file     = realpath('.') . $DS . 'app' . $DS . 'tmp' . $DS . $fileName . '.conf';

        $filesystem = $this->getMockBuilder('\Marvin\Filesystem\Filesystem')
                           ->setMethods(['put'])
                           ->getMock();

        $filesystem->expects($this->once())
                   ->method('put')
                   ->with($file, $content)
                   ->will($this->returnValue(true));

        $apache = new Apache($filesystem);

        $apache->set('server-name', 'marvin.dev')
               ->set('document-root', '/home/marvin/site/public');

        $this->assertTrue($apache->create($fileName));
    }
}
