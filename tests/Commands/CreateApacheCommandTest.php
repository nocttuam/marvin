<?php

use Marvin\Commands\CreateApacheCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CreateApacheCommandTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Symfony\Component\Console\Application
     */
    protected $application;

    protected function setUp()
    {
        $apacheManager = $this->getMockBuilder('Marvin\Hosts\Apache')
                              ->disableOriginalConstructor()
                              ->setMethods(['create'])
                              ->getMock();

        $apacheManager->method('create');

        $this->application = new Application();
        $this->application->add(new CreateApacheCommand($apacheManager));
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

        $commandTester->execute([
            'command'        => $command->getName(),
            'name'           => $serverName,
            'document-root'  => $documentRoot,
            '--server-admin' => $serverAdmin,
            '--ip'           => $ip,
            '--port'         => $port,
            '--log-path'     => $logPath,
        ]);

        $this->assertEquals($serverName, $commandTester->getInput()->getArgument('name'));
        $this->assertEquals($documentRoot, $commandTester->getInput()->getArgument('document-root'));
        $this->assertEquals($serverAdmin, $commandTester->getInput()->getOption('server-admin'));
        $this->assertEquals($ip, $commandTester->getInput()->getOption('ip'));
        $this->assertEquals($port, $commandTester->getInput()->getOption('port'));
        $this->assertEquals($logPath, $commandTester->getInput()->getOption('log-path'));
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

        $commandTester->execute([
            'command'       => $command->getName(),
            'name'          => $serverName,
            'document-root' => $documentRoot,
            '-a'            => $serverAdmin,
            '-i'            => $ip,
            '-p'            => $port,
            '-l'            => $logPath,
        ]);

        $this->assertEquals($serverAdmin, $commandTester->getInput()->getOption('server-admin'));
        $this->assertEquals($ip, $commandTester->getInput()->getOption('ip'));
        $this->assertEquals($port, $commandTester->getInput()->getOption('port'));
        $this->assertEquals($logPath, $commandTester->getInput()->getOption('log-path'));
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
                                  'create',
                              ])
                              ->getMock();


        $serverName   = 'marvin.localhost';
        $documentRoot = '/home/marvin/site/public';
        $serverAdmin  = 'marvin@mailhost';
        $ip           = '192.168.4.2';
        $port         = '8080';
        $logPath      = '/home/marvin/log';

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
                      ->method('create');

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
            '--ip'           => $ip,
            '--port'         => $port,
            '--log-path'     => $logPath,
        ]);

        $this->assertEquals($documentRoot, $commandTester->getInput()->getArgument('document-root'));
    }

}
