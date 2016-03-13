<?php
namespace Marvin\Commands;

use Marvin\Config\Repository;
use Marvin\Filesystem\Filesystem;
use Marvin\Filesystem\Template;
use Marvin\Hosts\ApacheManager;
use Marvin\Hosts\EtcHostsManager;
use Marvin\Shell\Apache\Execute;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CreateApacheCommandTest extends \PHPUnit_Framework_TestCase
{
    protected $container;

    public static function setUpBeforeClass()
    {
        $ds   = DIRECTORY_SEPARATOR;
        $base = dirname(__DIR__);
        $base .= $ds . 'mocksystem' . $ds;

        $path = $base . 'app' . $ds . 'tmp';
        mkdir($path, 0777, true);

        $path = $base . 'root' . $ds . 'etc' . $ds . 'apache2' . $ds . 'sites-available';
        mkdir($path, 0777, true);

        $path = $base . 'root' . $ds . 'etc' . $ds . 'apache2' . $ds . 'sites-enables';
        mkdir($path, 0777, true);

        $path = $base . 'root' . $ds . 'etc' . $ds . 'hosts';

        $hostContent = <<<CONT
127.0.0.1   localhost
127.0.1.1   Desktop
192.168.50.4    www.dev
192.168.50.4    local.app.dev
CONT;

        file_put_contents($path, $hostContent);
    }

    public static function tearDownAfterClass()
    {
        $ds   = DIRECTORY_SEPARATOR;
        $base = dirname(__DIR__);
        $base .= $ds . 'mocksystem' . $ds;

        self::deleteFiles($base);
    }

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
        $container['ConfigRepository'] = $this->getConfigRepository();
        $container['Filesystem']       = new Filesystem();
        $container['Template']         = new Template($container['ConfigRepository'], $container['Filesystem']);
        $container['ApacheManager']    = new ApacheManager($container['ConfigRepository'], $container['Template']);
        $container['EtcHostsManager']  = new EtcHostsManager($container['Filesystem'], $container['ConfigRepository']);
        $container['Execute'] = new Execute($container['ConfigRepository']);

        $this->container = $container;
    }

    protected function getConfigRepository()
    {
        $ds                 = DIRECTORY_SEPARATOR;
        $base               = dirname(dirname(__DIR__)) . $ds . 'app';
        $items['apache']    = include $base . $ds . 'config' . $ds . 'apache.conf.php';
        $items['app']       = include $base . $ds . 'config' . $ds . 'app.conf.php';
        $items['default']   = include $base . $ds . 'config' . $ds . 'default.conf.php';
        $items['hostsfile'] = include $base . $ds . 'config' . $ds . 'hostsfile.conf.php';

        $testDir     = dirname(__DIR__) . $ds . 'mocksystem';
        $testDirRoot = $testDir . $ds . 'root';

        $items['hostsfile']['dir']  = $testDirRoot . $ds . 'etc';
        $items['hostsfile']['path'] = $testDirRoot . $ds . 'etc' . $ds . 'hosts';

        $items['apache']['config-sys-dir'] = $testDirRoot . $ds . 'etc' . $ds . 'apache2';

        $items['app']['temporary-dir'] = $testDir . $ds . 'app' . $ds . 'tmp';

        $configRepository = new Repository($items);

        return $configRepository;
    }

    public function testSetArgumentsAndOptionsCorrectly()
    {
        $application  = new Application();
        $createApache = new CreateApacheCommand($this->container);
        $application->add($createApache);

        $command       = $application->find('create:apache');
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

        $command       = $application->find('create:apache');
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

        $command       = $application->find('create:apache');
        $commandTester = new CommandTester($command);

        $arguments            = [
            'server-name'   => 'marvin.dev',
            'document-root' => '/home/marvin/app',
        ];
        $arguments['command'] = $command->getName();

        $commandTester->execute($arguments);

        $ds           = DIRECTORY_SEPARATOR;
        $base         = dirname(__DIR__) . $ds . 'mocksystem' . $ds . 'app' . $ds . 'tmp';
        $hostFilePath = $base . $ds  . 'hosts';
        $apachePath   = $base . $ds . 'marvin.dev.conf';

        $this->assertFileExists($hostFilePath);
        $this->assertFileExists($apachePath);
    }

}