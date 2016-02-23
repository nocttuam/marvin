<?php
namespace Marvin\Hosts;

use Marvin\Filesystem\Filesystem;

class ApacheTest extends \PHPUnit_Framework_TestCase
{
    protected $configRepository;

    protected $execute;

    protected $filesystem;

    protected $template;

    protected function setUp()
    {
        $this->configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                       ->setMethods(null)
                                       ->getMock();

        $this->filesystem = $this->getMockBuilder('Marvin\Filesystem\Filesystem')
                                 ->setMethods(null)
                                 ->getMock();

        $this->template = $this->getMockBuilder('Marvin\Filesystem\Template')
                               ->disableOriginalConstructor()
                               ->setMethods(null)
                               ->getMock();
    }

    public function testSetDependenciesCorrectly()
    {
        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->setMethods(null)
                                 ->getMock();

        $filesystem = $this->getMockBuilder('Marvin\Filesystem\Filesystem')
                           ->setMethods(null)
                           ->getMock();

        $template = $this->getMockBuilder('Marvin\Filesystem\Template')
                         ->disableOriginalConstructor()
                         ->setMethods(null)
                         ->getMock();

        $vhManager = new Apache($configRepository, $filesystem, $template);

        $this->assertAttributeInstanceOf('Marvin\Config\Repository', 'configRepository', $vhManager);
        $this->assertAttributeInstanceOf('Marvin\Filesystem\Filesystem', 'filesystem', $vhManager);
        $this->assertAttributeInstanceOf('Marvin\Filesystem\Template', 'template', $vhManager);
    }

    public function testShouldSetParametersCorrectly()
    {
        $ip = '192.168.42.42';

        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->disableOriginalConstructor()
                                 ->setMethods(['set'])
                                 ->getMock();

        $configRepository->expects($this->once())
                         ->method('set')
                         ->with(
                             $this->equalTo('ip'),
                             $this->equalTo($ip)
                         );

        $vhManager = new Apache($configRepository, $this->filesystem, $this->template);
        $this->assertInstanceOf('Marvin\Hosts\Apache', $vhManager->set('ip', $ip));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage This is not a valid IP:
     */
    public function testThrowExceptionIfIpIsInvalid()
    {
        $vhManager = new Apache($this->configRepository, $this->filesystem, $this->template);

        $vhManager->set('ip', '42.42.42');
    }

    public function testBuildIdToHost()
    {
        $serverName = 'mavin.host';
        $expected   = md5($serverName);

        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->disableOriginalConstructor()
                                 ->setMethods(['set'])
                                 ->getMock();

        $configRepository->expects($this->exactly(2))
                         ->method('set')
                         ->withConsecutive(
                             [$this->equalTo('id'), $this->equalTo($expected)],
                             [$this->equalTo('server-name'), $this->equalTo($serverName)]
                         );

        $vhManager = new Apache($configRepository, $this->filesystem, $this->template);

        $this->assertInstanceOf('Marvin\Hosts\Apache', $vhManager->set('server-name', $serverName));
    }


    public function testTransformAliasArrayInString()
    {
        $alias = [
            'marvin.dev',
            'marvin.host',
            'marvin.local'
        ];

        $expected = 'marvin.dev marvin.host marvin.local';

        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->disableOriginalConstructor()
                                 ->setMethods(['set'])
                                 ->getMock();

        $configRepository->expects($this->once())
                         ->method('set')
                         ->with($this->equalTo('server-alias'), $this->equalTo($expected));

        $vhManager = new Apache($configRepository, $this->filesystem, $this->template);

        $this->assertInstanceOf('Marvin\Hosts\Apache', $vhManager->set('server-alias', $alias));
    }


    public function testBuildCorrectFileNameAddingExtensionIfNotExist()
    {
        $file = [
            'with-extension'    => 'marvin.local.conf',
            'without-extension' => 'marvin.local',
        ];

        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->disableOriginalConstructor()
                                 ->setMethods(['set'])
                                 ->getMock();

        $configRepository->expects($this->exactly(2))
                         ->method('set')
                         ->withConsecutive(
                             [$this->equalTo('file-name'), $this->equalTo($file['with-extension'])],
                             [$this->equalTo('file-name'), $this->equalTo($file['with-extension'])]
                         );

        $vhManager = new Apache($configRepository, $this->filesystem, $this->template);

        $this->assertInstanceOf('Marvin\Hosts\Apache', $vhManager->set('file-name', $file['without-extension']));
        $this->assertInstanceOf('Marvin\Hosts\Apache', $vhManager->set('file-name', $file['with-extension']));

    }

    public function testShouldReturnValueIfKeyExistIfNotReturnAllConfigurations()
    {
        $configurations = [
            'ip'           => '192.168.42.42',
            'server-admin' => 'webmaster@marvin',
            'log-path'     => '${APACHE_LOG_DIR}',
        ];

        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->disableOriginalConstructor()
                                 ->setMethods(['get', 'has', 'all'])
                                 ->getMock();

        $configRepository->expects($this->any())
                         ->method('has')
                         ->will($this->returnCallback(function ($key) use ($configurations) {
                             return key_exists($key, $configurations);
                         }
                         ));

        $configRepository->expects($this->any())
                         ->method('get')
                         ->will($this->returnCallback(
                             function ($key) use ($configurations) {
                                 switch ($key) {
                                     case 'ip':
                                         return $configurations['ip'];
                                     case 'server-admin':
                                         return $configurations['server-admin'];
                                     case 'log-path':
                                         return $configurations['log-path'];
                                 }
                             }
                         ));

        $configRepository->expects($this->once())
                         ->method('all')
                         ->will($this->returnValue($configurations));

        $vhManager = new Apache($configRepository, $this->filesystem, $this->template);

        $this->assertEquals($configurations['ip'], $vhManager->get('ip'));
        $this->assertEquals($configurations['server-admin'], $vhManager->get('server-admin'));
        $this->assertEquals($configurations['log-path'], $vhManager->get('log-path'));
        $this->assertEquals($configurations, $vhManager->get());
    }


    public function testCreateTemporaryConfigurationFileForTheHost()
    {
        $content        = <<<CONF
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
        $fileName       = 'marvin.host';
        $configurations = [
            'file-name' => 'marvin.host'
        ];
        $DS             = DIRECTORY_SEPARATOR;
        $file           = realpath('.') . $DS . 'app' . $DS . 'tmp' . $DS . $configurations['file-name'] . '.conf';

        // Config\Repository Mock
        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->disableOriginalConstructor()
                                 ->setMethods([])
                                 ->getMock();

        $configRepository->expects($this->any())
                         ->method('set')
                         ->will($this->returnCallback(function ($key, $value) use (&$configurations) {
                             $configurations[$key] = $value;
                         }));

        $configRepository->expects($this->any())
                         ->method('get')
                         ->will($this->returnCallback(function ($key = null) use (&$configurations) {
                             if (key_exists($key, $configurations)) {
                                 return $configurations[$key];
                             }

                             return $configurations;
                         }));

        $configRepository->expects($this->any())
                         ->method('all')
                         ->will($this->returnValue($configurations));

        $configRepository->expects($this->any())
                         ->method('has')
                         ->will($this->returnValue(true));

        // Filesystem Mock
        $filesystem = $this->getMockBuilder('Marvin\Filesystem\Filesystem')
                           ->setMethods(['put'])
                           ->getMock();

        $filesystem->expects($this->any())
                   ->method('put')
                   ->with($this->equalTo($file), $this->equalTo($content))
                   ->will($this->returnValue(true));


        // Filesystem\Template Mock
        $template = $this->getMockBuilder('Marvin\Filesystem\Template')
                         ->disableOriginalConstructor()
                         ->setMethods([])
                         ->getMock();

        // Virtual Host Manager Instance
        $vhManager = new Apache($configRepository, $filesystem, $template);

        // Template method
        $template->expects($this->once())
                 ->method('compile')
                 ->with($this->identicalTo($vhManager), $this->equalTo($configurations))
                 ->will($this->returnValue($content));


        $vhManager->set('id', '192.168.4.2')
                  ->set('port', 8080)
                  ->set('server-admin', 'marvin@localhost')
                  ->set('server-name', 'marvin.dev')
                  ->set('server-alias', ['marvin.local.dev', 'marvin.develop.host'])
                  ->set('log-path', '/home/marvin/logs/')
                  ->set('directory-root', '/home/marvin/site/public')
                  ->set('file-name', $configurations['file-name']);


        $this->assertTrue($vhManager->create());
    }

    public function testSetExecuteClassInstance()
    {
        $execute = $this->getMockBuilder('Marvin\Contracts\Execute')
                        ->setMethods(['moveConfig', 'enable', 'restart'])
                        ->getMock();

        $vhManager = new Apache($this->configRepository, $this->filesystem, $this->template);

        $vhManager->setExecute($execute);

        $this->assertAttributeInstanceOf('Marvin\Contracts\Execute', 'execute', $vhManager);
    }

    public function testRunCommandsToEnableSiteAndRestartApache()
    {
        $file = 'marvin.host.conf';

        // Config Repository

        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->setMethods(['get', 'has'])
                                 ->getMock();

        $configRepository->expects($this->any())
                         ->method('get')
                         ->will($this->returnValue($file));

        $configRepository->expects($this->any())
                         ->method('has')
                         ->will($this->returnValue(true));

        // Execute
        $execute = $this->getMockBuilder('Marvin\Contracts\Execute')
                        ->setMethods(['moveConfig', 'enable', 'restart'])
                        ->getMock();

        $execute->expects($this->exactly(2))
                ->method('moveConfig')
                ->with($this->equalTo($file))
                ->will($this->returnValue('File moved success'));

        $execute->expects($this->exactly(2))
                ->method('enable')
                ->with($this->equalTo($file))
                ->will($this->returnValue('Site enabled success'));

        $execute->expects($this->exactly(2))
                ->method('restart')
                ->will($this->returnValue('Apache restarted success'));


        $vhManager = new Apache($configRepository, $this->filesystem, $this->template);

        $this->assertTrue($vhManager->runEnableCommands($execute));
        $this->assertAttributeInstanceOf('Marvin\Contracts\Execute', 'execute', $vhManager);
        $this->assertEquals('File moved success', $vhManager->moveConfigFile());
        $this->assertEquals('Site enabled success', $vhManager->enable());
        $this->assertEquals('Apache restarted success', $vhManager->restart());
    }
}
