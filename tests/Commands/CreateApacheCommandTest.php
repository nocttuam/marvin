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
                                  'create',
                              ])
                              ->getMock();

        $apacheManager->method('create');

        $this->application = new Application();
        $this->application->add(new CreateApacheCommand($apacheManager));
    }

    /**
     * @runInSeparateProcess
     */
    public function testPrintMessageIfIpIsInvalid()
    {
        $ip = '111';

        $apacheManager = $this->getMockBuilder('Marvin\Hosts\Apache')
                              ->disableOriginalConstructor()
                              ->setMethods([
                                  'validateParameters',
                              ])
                              ->getMock();

        $apacheManager->expects($this->exactly(3))
                      ->method('validateParameters')
                      ->will($this->returnCallback(function ($key, $ip) {
                          if ('ip' === $key && filter_var($ip, FILTER_VALIDATE_IP) === false) {
                              throw new \InvalidArgumentException('This is a not valid IP');
                          }
                      }));

        $application = new Application();
        $application->add(new CreateApacheCommand($apacheManager));

        $command       = $application->find('create:apache');
        $commandTester = new CommandTester($command);


        $commandTester->execute([
            'command'       => $command->getName(),
            'name'          => 'marvin.localhost',
            'document-root' => '/home/trillian/marvin/app',
            '--ip'          => $ip,
        ]);

        $this->assertRegExp('/This is a not valid IP/', $commandTester->getDisplay());

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
                                 'set',
                                  'create'
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
        $apacheManager->expects($this->any())
                      ->method('set')
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
            '--server-alias' => $serverAlias,
            '--ip'           => $ip,
            '--port'         => $port,
            '--log-path'     => $logPath,
        ]);

        $this->assertRegExp('/Apache Virtual Host Created Success!/', $commandTester->getDisplay());
    }

}
