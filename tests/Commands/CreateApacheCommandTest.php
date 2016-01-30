<?php
namespace Marvin\Commands;

use Marvin\Commands\CreateApacheCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CreateApacheCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Application
     */
    protected $application;

    protected function setUp()
    {
        $apacheManager = $this->getMockBuilder('Marvin\Hosts\Apache')
                              ->disableOriginalConstructor()
                              ->setMethods([
                                  'createConfigFile',
                                  'enableApacheSite',
                                  'restartServer',
                              ])
                              ->getMock();

        $apacheManager->method('createConfigFile');
        $apacheManager->method('enableApacheSite');
        $apacheManager->method('restartServer');

        $this->application = new Application();
        $this->application->add(new CreateApacheCommand($apacheManager));
    }

    /**
     * @runInSeparateProcess
     */
    public function testPrintMessageIfIpIsInvalid()
    {
        $apacheManager = $this->getMockBuilder('Marvin\Hosts\Apache')
                              ->disableOriginalConstructor()
                              ->setMethods([
                                  'ip',
                              ])
                              ->getMock();

        $apacheManager->expects($this->once())
                      ->method('ip')
                      ->will($this->returnCallback(function ($ip) {
                          if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
                              throw new \InvalidArgumentException('Use a valid IP');
                          }
                      }));

        $application = new Application();
        $application->add(new CreateApacheCommand($apacheManager));

        $command       = $application->find('create:apache');
        $commandTester = new CommandTester($command);

        $ip = '111';

        $commandTester->execute([
            'command'       => $command->getName(),
            'name'          => 'marvin.localhost',
            'document-root' => '/home/trillian/marvin/app',
            '--ip'          => $ip,
        ]);

        $this->assertRegExp('/Use a valid IP/', $commandTester->getDisplay());

    }

    public function testCommandSetOptionsRightly()
    {
        $command       = $this->application->find('create:apache');
        $commandTester = new CommandTester($command);

        $serverName   = 'marvin.localhost';
        $documentRoot = '/home/marvin/site/public';
        $serverAdmin  = 'marvin@mailhost';
        $ip           = '192.168.4.2';
        $port         = '8080';
        $logPath      = '/home/marvin/log';
        $serverAlias  = 'marvin.app';

        $commandTester->execute([
            'command'        => $command->getName(),
            'name'           => $serverName,
            'document-root'  => $documentRoot,
            '--server-admin' => $serverAdmin,
            '--ip'           => $ip,
            '--port'         => $port,
            '--log-path'     => $logPath,
            '--server-alias' => $serverAlias,
        ]);

        $this->assertEquals($serverName, $commandTester->getInput()->getArgument('name'));
        $this->assertEquals($documentRoot, $commandTester->getInput()->getArgument('document-root'));
        $this->assertEquals($serverAdmin, $commandTester->getInput()->getOption('server-admin'));
        $this->assertEquals($ip, $commandTester->getInput()->getOption('ip'));
        $this->assertEquals($port, $commandTester->getInput()->getOption('port'));
        $this->assertEquals($logPath, $commandTester->getInput()->getOption('log-path'));
        $this->assertContains($serverAlias, $commandTester->getInput()->getOption('server-alias'));
    }

    public function testCommandSetOptionsRightlyUsingShortcuts()
    {
        $command       = $this->application->find('create:apache');
        $commandTester = new CommandTester($command);

        $serverName   = 'marvin.localhost';
        $documentRoot = '/home/marvin/site/public';
        $serverAdmin  = 'marvin@mailhost';
        $ip           = '192.168.4.2';
        $port         = '8080';
        $logPath      = '/home/marvin/log';
        $serverAlias  = 'marvin.app';

        $commandTester->execute([
            'command'       => $command->getName(),
            'name'          => $serverName,
            'document-root' => $documentRoot,
            '-a'            => $serverAdmin,
            '-i'            => $ip,
            '-p'            => $port,
            '-l'            => $logPath,
            '-A'            => $serverAlias,
        ]);

        $this->assertEquals($serverAdmin, $commandTester->getInput()->getOption('server-admin'));
        $this->assertEquals($ip, $commandTester->getInput()->getOption('ip'));
        $this->assertEquals($port, $commandTester->getInput()->getOption('port'));
        $this->assertEquals($logPath, $commandTester->getInput()->getOption('log-path'));
        $this->assertContains($serverAlias, $commandTester->getInput()->getOption('server-alias'));
    }

    public function testShouldCreateApacheVirtualHost()
    {
        $apacheManager = $this->getMockBuilder('Marvin\Hosts\Apache')
                              ->disableOriginalConstructor()
                              ->setMethods([
                                  'ip',
                                  'port',
                                  'serverAdmin',
                                  'serverName',
                                  'documentRoot',
                                  'logPath',
                                  'serverAlias',
                                  'createConfigFile',
                                  'enableApacheSite',
                                  'restartServer',
                              ])
                              ->getMock();


        $serverName   = 'marvin.localhost';
        $documentRoot = '/home/marvin/site/public';
        $serverAdmin  = 'marvin@mailhost';
        $ip           = '192.168.4.2';
        $port         = '8080';
        $logPath      = '/home/marvin/log';
        $serverAlias  = 'marvin.app';

        /**
         * Methods to call in /Marvin/Hosts/Apache
         */
        $apacheManager->expects($this->once())
                      ->method('serverName')
                      ->with($this->equalTo($serverName))
                      ->will($this->returnSelf());

        $apacheManager->expects($this->once())
                      ->method('documentRoot')
                      ->with($this->equalTo($documentRoot))
                      ->will($this->returnSelf());

        $apacheManager->expects($this->once())
                      ->method('serverAlias')
                      ->with($this->equalTo(array($serverAlias)))
                      ->will($this->returnSelf());


        $apacheManager->expects($this->once())
                      ->method('ip')
                      ->with($this->equalTo($ip))
                      ->will($this->returnSelf());

        $apacheManager->expects($this->once())
                      ->method('port')
                      ->with($this->equalTo($port))
                      ->will($this->returnSelf());

        $apacheManager->expects($this->once())
                      ->method('serverAdmin')
                      ->with($this->equalTo($serverAdmin))
                      ->will($this->returnSelf());

        $apacheManager->expects($this->once())
                      ->method('logPath')
                      ->with($this->equalTo($logPath))
                      ->will($this->returnSelf());

        $apacheManager->expects($this->once())
                      ->method('createConfigFile');

        $apacheManager->expects($this->once())
                      ->method('enableApacheSite')
                      ->will($this->returnValue('Site Enable Finished'));

        $apacheManager->expects($this->once())
                      ->method('restartServer')
                      ->will($this->returnValue('Apache Restarted'));

        /**
         * Execute commands
         */
        $application = new Application();
        $application->add(new CreateApacheCommand($apacheManager));

        $command       = $application->find('create:apache');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'command'        => $command->getName(),
            'name'           => $serverName,
            'document-root'  => $documentRoot,
            '--server-admin' => $serverAdmin,
            '--server-alias' => $serverAlias,
            '--ip'           => $ip,
            '--port'         => $port,
            '--log-path'     => $logPath,
        ]);

        $this->assertRegExp('/Site Enable Finished/', $commandTester->getDisplay());
        $this->assertRegExp('/Apache Restarted/', $commandTester->getDisplay());
    }

}
