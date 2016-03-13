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
    public function testSetInitialParametersCorrectly()
    {
        $temporaryDir = 'app/temp';

        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->setMethods(['get'])
                                 ->getMock();

        $configRepository->expects($this->once())
                         ->method('get')
                         ->with($this->equalTo('app.temporary-dir'))
                         ->will($this->returnValue($temporaryDir));

        $execute = new Execute($configRepository);

        $this->assertAttributeInstanceOf(
            'Marvin\Config\Repository',
            'configRepository',
            $execute
        );

        $this->assertAttributeEquals(
            $temporaryDir,
            'temporaryDir',
            $execute
        );
    }

    public function testShouldSetParametersToUseInObject()
    {
        $fileName = 'marvin.dev.conf';

        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->setMethods(null)
                                 ->getMock();

        $vhManager = $this->getMockBuilder('Marvin\Contracts\HostManager')
                          ->disableOriginalConstructor()
                          ->setMethods([])
                          ->getMock();

        $vhManager->expects($this->once())
                  ->method('get')
                  ->with($this->equalTo('file-name'))
                  ->will($this->returnValue($fileName));

        $execute = new Execute($configRepository);

        $execute->setHostManager($vhManager);

        $this->assertAttributeInstanceOf('Marvin\Contracts\HostManager', 'hostManager', $execute);
        $this->assertAttributeEquals($fileName, 'fileName', $execute);

    }

    public function testShouldEnableApacheServer()
    {
        $temporaryDir = 'app/tmp';
        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                ->setMethods(['get'])
                                ->getMock();

        $configRepository->expects($this->once())
                         ->method('get')
                         ->with($this->equalTo('app.temporary-dir'))
                         ->will($this->returnValue($temporaryDir));

        $vhManager = $this->getMockBuilder('Marvin\Contracts\HostManager')
                          ->setMethods([])
                          ->getMock();

        $vhManager->expects($this->once())
                  ->method('get')
                  ->with($this->equalTo('file-name'))
                  ->will($this->returnValue('marvin.dev.conf'));

        $execute = new Execute($configRepository);
        $execute->setHostManager($vhManager);


        $this->assertRegExp('/Enabled Success/', $execute->enable());
        $this->assertRegExp('/sudo a2ensite marvin.dev.conf/', $execute->enable());
    }

    public function testShouldRestartApacheServer()
    {
        $temporaryDir = 'app/tmp';
        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->setMethods(['get'])
                                 ->getMock();

        $configRepository->expects($this->once())
                         ->method('get')
                         ->with($this->equalTo('app.temporary-dir'))
                         ->will($this->returnValue($temporaryDir));

        $vhManager = $this->getMockBuilder('Marvin\Contracts\HostManager')
                          ->setMethods([])
                          ->getMock();

        $vhManager->expects($this->once())
                  ->method('get')
                  ->with($this->equalTo('file-name'))
                  ->will($this->returnValue('marvin.dev.conf'));

        $execute = new Execute($configRepository);
        $execute->setHostManager($vhManager);

        $this->assertRegExp('/Restart Success/', $execute->restart());
        $this->assertRegExp('/sudo service apache2 reload/', $execute->restart());
    }
}

