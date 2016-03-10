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

        $template = $this->getMockBuilder('Marvin\Filesystem\Template')
                         ->disableOriginalConstructor()
                         ->setMethods(null)
                         ->getMock();

        $vhManager = new Apache($configRepository, $template);

        $this->assertAttributeInstanceOf('Marvin\Config\Repository', 'configRepository', $vhManager);
        $this->assertAttributeInstanceOf('Marvin\Filesystem\Template', 'template', $vhManager);
    }

    public function testShouldSetIPAndPortCorrectly()
    {
        $ip   = '192.168.42.42';
        $port = '9090';

        $configurations = [];

        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->disableOriginalConstructor()
                                 ->setMethods(['set'])
                                 ->getMock();

        $configRepository->expects($this->exactly(2))
                         ->method('set')
                         ->withConsecutive(
                             [$this->equalTo('apache.ip'), $this->equalTo($ip)],
                             [$this->equalTo('apache.port'), $this->equalTo($port)]
                         )
                         ->will($this->returnCallback(function ($key, $value) use (&$configurations) {
                             $configurations[$key] = $value;
                         }));

        $vhManager = new Apache($configRepository, $this->template);

        $vhManager->setIP($ip);
        $vhManager->setPort($port);

        $this->assertEquals($ip, $configurations['apache.ip']);
        $this->assertEquals($port, $configurations['apache.port']);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage This is not a valid IP:
     */
    public function testThrowExceptionIfIpIsInvalid()
    {
        $vhManager = new Apache($this->configRepository, $this->template);

        $vhManager->setIP('42.42.42');
    }

    public function testShouldSetServerNameAndBuildID()
    {
        $serverName     = 'mavin.host';
        $expected       = md5($serverName);
        $configurations = [];

        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->disableOriginalConstructor()
                                 ->setMethods(['set'])
                                 ->getMock();

        $configRepository->expects($this->exactly(2))
                         ->method('set')
                         ->withConsecutive(
                             [$this->equalTo('apache.id'), $this->equalTo($expected)],
                             [$this->equalTo('apache.server-name'), $this->equalTo($serverName)]
                         )
                         ->will($this->returnCallback(function ($key, $value) use (&$configurations) {
                             $configurations[$key] = $value;
                         }));

        $vhManager = new Apache($configRepository, $this->template);

        $vhManager->setServerName($serverName);

        $this->assertEquals($serverName, $configurations['apache.server-name']);
        $this->assertEquals($expected, $configurations['apache.id']);
    }


    public function testTransformAliasArrayInString()
    {
        $alias = [
            'marvin.dev',
            'marvin.host',
            'marvin.local'
        ];

        $expected = 'marvin.dev marvin.host marvin.local';

        $configurations = [];

        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->disableOriginalConstructor()
                                 ->setMethods(['set'])
                                 ->getMock();

        $configRepository->expects($this->once())
                         ->method('set')
                         ->with($this->equalTo('apache.server-alias'), $this->equalTo($expected))
                         ->will($this->returnCallback(function ($key, $value) use (&$configurations) {
                             $configurations[$key] = $value;
                         }));

        $vhManager = new Apache($configRepository, $this->template);

        $vhManager->setServerAlias($alias);

        $this->assertEquals($expected, $configurations['apache.server-alias']);
    }

    /**
     * @dataProvider fileNamesProvider
     */
    public function testBuildCorrectFileNameAddingExtensionIfNotExist($name, $expected)
    {

        $configurations = [];

        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->disableOriginalConstructor()
                                 ->setMethods(['set'])
                                 ->getMock();

        $configRepository->expects($this->any())
                         ->method('set')
                         ->with($this->equalTo('apache.file-name'), $this->equalTo($expected))
                         ->will($this->returnCallback(function ($key, $value) use (&$configurations) {
                             $configurations[$key] = $value;
                         }));

        $vhManager = new Apache($configRepository, $this->template);

        $vhManager->setFileName($name);
        $this->assertEquals($expected, $configurations['apache.file-name']);

    }

    public function fileNamesProvider()
    {
        return [
            ['marvin.local', 'marvin.local.conf'],
            ['marvin.conf', 'marvin.conf'],
            ['marvin', 'marvin.conf'],
        ];
    }

    public function testSetPathsForDocumentsAndLogs()
    {
        $documentRoot = 'my/web/application/root';
        $logDir       = 'my/web/application/root/logs';


        $configurations = [];


        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->disableOriginalConstructor()
                                 ->setMethods(['set'])
                                 ->getMock();

        $configRepository->expects($this->any())
                         ->method('set')
                         ->withConsecutive(
                             [$this->equalTo('apache.document-root'), $this->equalTo($documentRoot)],
                             [$this->equalTo('apache.log-dir'), $this->equalTo($logDir)]
                         )
                         ->will($this->returnCallback(function ($key, $value) use (&$configurations
                         ) {
                             $configurations[$key] = $value;
                         }));


        $vhManager = new Apache($configRepository, $this->template);

        $vhManager->setDocumentRoot($documentRoot);
        $vhManager->setLogDir($logDir);

        $this->assertEquals($documentRoot, $configurations['apache.document-root']);
        $this->assertEquals($logDir, $configurations['apache.log-dir']);
    }

    public function testSetServerAdminCorrectly()
    {
        $serverAdmin = 'web@master';


        $configurations = [];


        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->disableOriginalConstructor()
                                 ->setMethods(['set'])
                                 ->getMock();

        $configRepository->expects($this->any())
                         ->method('set')
                         ->with($this->equalTo('apache.server-admin'), $this->equalTo($serverAdmin))
                         ->will($this->returnCallback(function ($key, $value) use (&$configurations
                         ) {
                             $configurations[$key] = $value;
                         }));


        $vhManager = new Apache($configRepository, $this->template);

        $vhManager->setServerAdmin($serverAdmin);

        $this->assertEquals($serverAdmin, $configurations['apache.server-admin']);
    }

    public function testShouldReturnValueIfKeyExistIfNotReturnAllConfigurations()
    {
        $configurations = [
            'apache'  => [
                'ip'           => '192.168.4.2',
                'server-admin' => 'webmaster@marvin',
            ],
            'default' => [
                'ip'   => '192.168.42.42',
                'port' => '8080',
            ],
        ];

        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->disableOriginalConstructor()
                                 ->setMethods(['get', 'has', 'all'])
                                 ->getMock();

        $configRepository->expects($this->any())
                         ->method('has')
                         ->will($this->returnCallback(function ($key) use (&$configurations) {
                             $keys = explode('.', $key);

                             return key_exists($keys[1], $configurations[$keys[0]]);
                         }));


        $configRepository->expects($this->any())
                         ->method('get')
                         ->will($this->returnCallback(function ($key) use (&$configurations) {
                             $keys = explode('.', $key);

                             return $configurations[$keys[0]][$keys[1]];
                         }));

        $configRepository->expects($this->exactly(2))
                         ->method('all')
                         ->will($this->returnValue($configurations));

        $vhManager = new Apache($configRepository, $this->template);

        $this->assertEquals($configurations['apache']['ip'], $vhManager->get('ip'));
        $this->assertEquals($configurations['default']['ip'], $vhManager->get('ip', true));
        $this->assertEquals($configurations['default']['port'], $vhManager->get('port'));
        $this->assertEquals($configurations['default']['port'], $vhManager->get('port', true));
        $this->assertEquals($configurations['apache']['server-admin'], $vhManager->get('server-admin'));
        $this->assertEquals($configurations['apache']['server-admin'], $vhManager->get('server-admin', true));
        $this->assertEquals($configurations, $vhManager->get('false-key'));
        $this->assertEquals($configurations, $vhManager->get());
    }


    public function testCreateTemporaryConfigurationFileForTheHost()
    {
        $configs = [
            'apache'   => [
                'log-dir' => '${APACHE_LOG_DIR}',
            ],
            'defaults' => [
                'ip'   => '192.168.42.42',
                'port' => '8080',
            ],
        ];

        $expectedTags = [
            'ip'            => '192.168.4.2',
            'port'          => '8080',
            'server-name'   => 'marvin.dev',
            'document-root' => '/home/marvin/app/public',
            'log-dir'       => '${APACHE_LOG_DIR}',
            'file-name'     => 'marvin.dev.conf',
            'id'            => md5('marvin.dev'),
        ];

        // Config\Repository Mock
        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->disableOriginalConstructor()
                                 ->setMethods([])
                                 ->getMock();

        $configRepository->expects($this->any())
                         ->method('set')
                         ->will($this->returnCallback(function ($key, $value) use (&$configs) {
                             if (key_exists($key, $configs)) {
                                 $configs[$key] = $value;
                             }
                             $keys                        = explode('.', $key);
                             $configs[$keys[0]][$keys[1]] = $value;
                         }));

        $configRepository->expects($this->any())
                         ->method('get')
                         ->will($this->returnCallback(function ($key) use (&$configs) {
                             if (key_exists($key, $configs)) {
                                 return $configs[$key];
                             }

                             $keys = explode('.', $key);

                             return $configs[$keys[0]][$keys[1]];
                         }));

        $configRepository->expects($this->any())
                         ->method('all')
                         ->will($this->returnValue($configs));

        $configRepository->expects($this->any())
                         ->method('has')
                         ->will($this->returnCallback(function ($key) use (&$configs) {
                             if (key_exists($key, $configs)) {
                                 return true;
                             }

                             $keys = explode('.', $key);

                             return key_exists($keys[1], $configs[$keys[0]]);
                         }));


        // Filesystem\Template Mock
        $template = $this->getMockBuilder('Marvin\Filesystem\Template')
                         ->disableOriginalConstructor()
                         ->setMethods([])
                         ->getMock();

        // Virtual Host Manager Instance
        $vhManager = new Apache($configRepository, $template);

        // Template method
        $template->expects($this->once())
                 ->method('compile')
                 ->with($this->identicalTo($vhManager), $this->equalTo($expectedTags))
                 ->will($this->returnValue(true));


        $vhManager->setIP('192.168.4.2')
                  ->setServerName('marvin.dev')
                  ->setDocumentRoot('/home/marvin/app/public')
                  ->setFileName('marvin.dev');


        $this->assertTrue($vhManager->create());
    }

    public function testReturnExecuteInstanceConfiguredToUse()
    {
        $execute = $this->getMockBuilder('Marvin\Contracts\Execute')
                        ->setMethods([])
                        ->getMock();

        $vhManager = new Apache($this->configRepository, $this->template);

        $execute->expects($this->once())
                ->method('setHost')
                ->with($this->identicalTo($vhManager));

        $this->assertInstanceOf('Marvin\Contracts\Execute', $vhManager->execute($execute));
    }
}
