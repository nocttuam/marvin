<?php
namespace Marvin\Shell\Apache;

/**
 * Mock to PHP function
 *
 * @param $command
 *
 * @return string
 */
function shell_exec($command)
{
    if (preg_match('/(a2ensite)/', $command)) {
        return 'Enabled Success: ' . $command;
    }

    if (preg_match('/(apache2 reload)/', $command)) {
        return 'Restart Success: ' . $command;
    }

    if (preg_match('/(sudo mv)/', $command)) {
        return $command;
    }

    return 'Fail';
}

class ExecuteTest extends \PHPUnit_Framework_TestCase
{
    protected $configRepository;

    public function setUp()
    {
        $this->configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                       ->disableOriginalConstructor()
                                       ->setMethods(null)
                                       ->getMock();
    }

    public function testShouldSetHostInstanceToUse()
    {
        $vhManager = $this->getMockBuilder('Marvin\Contracts\Host')
                          ->disableOriginalConstructor()
                          ->setMethods([])
                          ->getMock();

        $execute = new Execute();

        $execute->setHost($vhManager);

        $this->assertAttributeInstanceOf('Marvin\Contracts\Host', 'host', $execute);

    }

    public function testMoveConfigFileToApacheConfigDirectory()
    {
        $vhManager = $this->getMockBuilder('Marvin\Contracts\Host')
                          ->disableOriginalConstructor()
                          ->setMethods([])
                          ->getMock();

        $vhManager->expects($this->exactly(2))
                  ->method('get')
                  ->withConsecutive(['apache-path'], ['temp-directory'])
                  ->will($this->onConsecutiveCalls('/etc/apache2', '/Marvin/app/tmp'));

        $execute = new Execute();
        $execute->setHost($vhManager);

        $this->assertRegExp(
            '/sudo mv -v \/Marvin\/app\/tmp\/marvin.host.conf \/etc\/apache2\/sites-available/',
            $execute->moveConfig('marvin.host.conf')
        );
    }

    public function testShouldEnableApacheServer()
    {
        $execute = new Execute();
        $this->assertRegExp('/Enabled Success/', $execute->enable('marvin.host.conf'));
        $this->assertRegExp('/sudo a2ensite marvin.host.conf/', $execute->enable('marvin.host.conf'));
    }

    public function testShouldRestartApacheServer()
    {
        $execute = new Execute();
        $this->assertRegExp('/Restart Success/', $execute->restart());
        $this->assertRegExp('/sudo service apache2 reload/', $execute->restart());
    }
}

