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

    public function testReceiveConfigRepositoryInstance()
    {
        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->disableOriginalConstructor()
                                 ->setMethods(null)
                                 ->getMock();

        $execute = new Execute($configRepository);

        $this->assertAttributeInstanceOf('Marvin\Config\Repository', 'configRepository', $execute);

    }

    public function testMoveConfigFileToApacheConfigDirectory()
    {
        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->disableOriginalConstructor()
                                 ->setMethods(['get'])
                                 ->getMock();

        $configRepository->expects($this->exactly(2))
                         ->method('get')
                         ->withConsecutive(['apache-path'], ['temp-directory'])
                         ->will($this->onConsecutiveCalls('/etc/apache2', '/Marvin/app/tmp'));

        $execute = new Execute($configRepository);

        $this->assertRegExp(
            '/sudo mv -v \/Marvin\/app\/tmp\/marvin.host.conf \/etc\/apache2\/sites-available/',
            $execute->moveConfig('marvin.host.conf')
            );
    }

    public function testShouldEnableApacheServer()
    {
        $execute = new Execute($this->configRepository);
        $this->assertRegExp('/Enabled Success/', $execute->enable('marvin.host.conf'));
        $this->assertRegExp('/sudo a2ensite marvin.host.conf/', $execute->enable('marvin.host.conf'));
    }

    public function testShouldRestartApacheServer()
    {
        $execute = new Execute($this->configRepository);
        $this->assertRegExp('/Restart Success/', $execute->restart());
        $this->assertRegExp('/sudo service apache2 reload/', $execute->restart());
    }
}

