<?php
namespace Marvin\Commands;

use Marvin\Config\Repository;
use Marvin\Filesystem\Filesystem;
use Marvin\Filesystem\Template;
use Marvin\Hosts\ApacheManager;
use Marvin\Hosts\EtcHostsManager;
use Marvin\Shell\Apache\Execute;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CreateApacheCommandTest extends \PHPUnit_Framework_TestCase
{
    protected $container;

    /**
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    protected $root;

    protected static function deleteFiles($target)
    {
        if (is_dir($target)) {
            $files = glob($target . '*', GLOB_MARK);

            foreach ($files as $file) {
                self::deleteFiles($file);
            }

            rmdir($target);
        } elseif (is_file($target)) {
            unlink($target);
        }
    }

    protected function setUp()
    {
        $this->setFilesystemMock();
        $container['ConfigRepository'] = $this->getConfigRepository();
        $container['Filesystem']       = new Filesystem();
        $container['Template']         = new Template($container['ConfigRepository'], $container['Filesystem']);
        $container['ApacheManager']    = new ApacheManager($container['ConfigRepository'], $container['Template']);
        $container['EtcHostsManager']  = new EtcHostsManager($container['Filesystem'], $container['ConfigRepository']);
        $container['Execute'] = new Execute($container['ConfigRepository']);

        $this->container = $container;
    }

    protected function setFilesystemMock()
    {
        $etcContent = <<<CONT
127.0.0.1   localhost
127.0.1.1   Desktop
192.168.50.4    www.dev
192.168.50.4    local.app.dev
CONT;
        $structure = [
            'marvin' => [
                'tmp' => []
            ],
            'etc' => [
                'apache2' => [
                    'sites-available' => [],
                    'sites-enables' => []
                ],
                'hosts' => $etcContent
            ]

        ];

        $this->root = vfsStream::setup('root', null, $structure);
    }


    /**
     * @return Repository
     */
    protected function getConfigRepository()
    {
        $ds                 = DIRECTORY_SEPARATOR;
        $base               = dirname(dirname(__DIR__)) . $ds . 'app';
        $items['apache']    = include $base . $ds . 'config' . $ds . 'apache.conf.php';
        $items['app']       = include $base . $ds . 'config' . $ds . 'app.conf.php';
        $items['default']   = include $base . $ds . 'config' . $ds . 'default.conf.php';
        $items['hostsfile'] = include $base . $ds . 'config' . $ds . 'hostsfile.conf.php';


        $items['hostsfile']['dir']  = $this->root->getChild('etc')->url();
        $items['hostsfile']['path'] = $this->root->getChild('etc/hosts')->url();
        $items['apache']['config-sys-dir'] = $this->root->getChild('etc/apache2')->url();
        $items['app']['temporary-dir'] = $this->root->getChild('marvin/tmp')->url();


        $configRepository = new Repository($items);

        return $configRepository;
    }

    public function testSetArgumentsAndOptionsCorrectly()
    {
        $application  = new Application();
        $createApache = new CreateApacheCommand($this->container);
        $application->add($createApache);

        $command       = $application->find('apache:create');
        $commandTester = new CommandTester($command);

        $arguments = [
            'server-name'   => 'marvin.dev',
            'document-root' => '/home/marvin/app',
        ];

        $options = [
            'ip'           => '192.50.50.40',
            'port'         => '80',
            'server-admin' => 'marvin@webmaster',
            'alias'        => 'marvin.local',
            'log-dir'      => '/home/marvin/app/logs',
        ];

        $parameters                   = $arguments;
        $parameters['command']        = $command->getName();
        $parameters['--ip']           = $options['ip'];
        $parameters['--port']         = $options['port'];
        $parameters['--server-admin'] = $options['server-admin'];
        $parameters['--alias']        = $options['alias'];
        $parameters['--log-dir']      = $options['log-dir'];

        $commandTester->execute($parameters);

        $inpArguments = $commandTester->getInput()->getArguments();
        $inpOptions   = $commandTester->getInput()->getOptions();

        // Assert Arguments
        $this->assertEquals($arguments['server-name'], $inpArguments['server-name']);
        $this->assertEquals($arguments['document-root'], $inpArguments['document-root']);

        // Assert Options
        $this->assertEquals($options['ip'], $inpOptions['ip']);
        $this->assertEquals($options['port'], $inpOptions['port']);
        $this->assertEquals($options['server-admin'], $inpOptions['server-admin']);
        $this->assertEquals($options['alias'], $inpOptions['alias']);
        $this->assertEquals($options['log-dir'], $inpOptions['log-dir']);
    }

    public function testPrintMessageIfIPIsInvalid()
    {
        $application  = new Application();
        $createApache = new CreateApacheCommand($this->container);
        $application->add($createApache);

        $command       = $application->find('apache:create');
        $commandTester = new CommandTester($command);

        $arguments = [
            'server-name'   => 'marvin.dev',
            'document-root' => '/home/marvin/app',
        ];

        $options = [
            'ip' => '4.2.90',
        ];

        $parameters            = $arguments;
        $parameters['command'] = $command->getName();
        $parameters['--ip']    = $options['ip'];

        $commandTester->execute($parameters);

        $this->assertRegExp('/Use a valid IP/', $commandTester->getDisplay());
    }

    public function testShouldCreateConfigurationsFilesAndEnableNewHost()
    {
        $this->container['Execute'] = $this->getMockBuilder('Marvin\Contracts\Execute')
                                           ->setMethods([])
                                           ->getMock();

        $application  = new Application();
        $createApache = new CreateApacheCommand($this->container);
        $application->add($createApache);

        $command       = $application->find('apache:create');
        $commandTester = new CommandTester($command);

        $arguments            = [
            'server-name'   => 'marvin.dev',
            'document-root' => '/home/marvin/app',
        ];
        $arguments['command'] = $command->getName();

        $commandTester->execute($arguments);


        $apacheFile  = $this->root->getChild('marvin/tmp/marvin.dev.conf')->url();
        $hostsFile  = $this->root->getChild('marvin/tmp/hosts')->url();

        $this->assertFileExists($apacheFile);
        $this->assertFileExists($hostsFile);
    }

}