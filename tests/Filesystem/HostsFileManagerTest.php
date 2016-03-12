<?php
namespace Marvin\Filesystem;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Marvin\Filesystem\HostsFileManager;
use Marvin\Filesystem\Filesystem;

class HostsFileManagerTest extends \PHPUnit_Framework_TestCase
{

    public function testSetInitialParametersCorrectly()
    {
        $filesystem = $this->getMockBuilder('Marvin\Filesystem\Filesystem')
                           ->getMock();

        $hostsFilePath = '/etc/hosts';
        $tempDir       = '/app/tmp';

        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->setMethods(['get'])
                                 ->getMock();

        $configRepository->expects($this->exactly(2))
                         ->method('get')
                         ->withConsecutive(
                             [$this->equalTo('hostsfile.path')],
                             [$this->equalTo('app.temporary-dir')]
                         )
                         ->will($this->returnCallback(function ($key) use ($hostsFilePath, $tempDir) {
                             if ($key === 'hostsfile.path') {
                                 return $hostsFilePath;
                             }

                             return $tempDir;
                         }));

        $hostsFileManager = new HostsFileManager($filesystem, $configRepository);

        $this->assertAttributeInstanceOf(
            'Marvin\Filesystem\Filesystem',
            'filesystem',
            $hostsFileManager
        );
        $this->assertAttributeInstanceOf(
            'Marvin\Config\Repository',
            'configRepository',
            $hostsFileManager
        );

        $this->assertAttributeEquals(
            $hostsFilePath,
            'filePath',
            $hostsFileManager
        );

        $this->assertAttributeEquals(
            $tempDir,
            'tempDir',
            $hostsFileManager
        );
    }

    public function testShouldReturnContentInHostsFile()
    {
        $hostsFilePath = '/etc/hosts';
        $tempDir       = '/app/tmp';
        $content       = 'This is a content';


        $filesystem = $this->getMockBuilder('Marvin\Filesystem\Filesystem')
                           ->setMethods(['get'])
                           ->getMock();

        $filesystem->expects($this->once())
                   ->method('get')
                   ->with($this->equalTo($hostsFilePath))
                   ->will($this->returnValue($content));


        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->setMethods(['get'])
                                 ->getMock();

        $configRepository->expects($this->exactly(2))
                         ->method('get')
                         ->withConsecutive(
                             [$this->equalTo('hostsfile.path')],
                             [$this->equalTo('app.temporary-dir')]
                         )
                         ->will($this->returnCallback(function ($key) use ($hostsFilePath, $tempDir) {
                             if ($key === 'hostsfile.path') {
                                 return $hostsFilePath;
                             }

                             return $tempDir;
                         }));

        $hostsFileManager = new HostsFileManager($filesystem, $configRepository);

        $this->assertEquals($content, $hostsFileManager->getContent());
    }

    public function testShouldReturnSectionCreateByMarvin()
    {
        $content = <<<CONT
127.0.0.1 localhost
127.0.1.1 Desktop
127.0.2.1 mywebdev.local mywebdev webdev local

#=========Sirius CybeC - Entry=========
192.168.42.42 marvin.dev ### Marvin ID: 46846849
#=========Sirius CybeC - Exit=========
CONT;

        $section = <<<SECTION
#=========Sirius CybeC - Entry=========
192.168.42.42 marvin.dev ### Marvin ID: 46846849
#=========Sirius CybeC - Exit=========
SECTION;


        $hostsFilePath = '/etc/hosts';
        $tempDir       = '/app/tmp';

        $filesystem = $this->getMockBuilder('Marvin\Filesystem\Filesystem')
                           ->setMethods(['get'])
                           ->getMock();

        $filesystem->expects($this->once())
                   ->method('get')
                   ->with($hostsFilePath)
                   ->will($this->returnValue($content));


        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->setMethods(['get'])
                                 ->getMock();

        $configRepository->expects($this->exactly(2))
                         ->method('get')
                         ->withConsecutive(
                             [$this->equalTo('hostsfile.path')],
                             [$this->equalTo('app.temporary-dir')]
                         )
                         ->will($this->returnCallback(function ($key) use ($hostsFilePath, $tempDir) {
                             if ($key === 'hostsfile.path') {
                                 return $hostsFilePath;
                             }

                             return $tempDir;
                         }));

        $hostsFileManager = new HostsFileManager($filesystem, $configRepository);
        $return           = $hostsFileManager->getSection();

        $this->assertEquals($section, $return['all']);
    }

    public function testShouldReturnFalseIfSectionCreatedByMarvinDoesNotExist()
    {
        $filesystem = $this->getMockBuilder('Marvin\Filesystem\Filesystem')
                           ->getMock();

        $hostsFilePath = '/etc/hosts';
        $tempDir       = '/app/tmp';

        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->setMethods(['get'])
                                 ->getMock();

        $configRepository->expects($this->exactly(2))
                         ->method('get')
                         ->withConsecutive(
                             [$this->equalTo('hostsfile.path')],
                             [$this->equalTo('app.temporary-dir')]
                         )
                         ->will($this->returnCallback(function ($key) use ($hostsFilePath, $tempDir) {
                             if ($key === 'hostsfile.path') {
                                 return $hostsFilePath;
                             }

                             return $tempDir;
                         }));

        $hostsFileManager = new HostsFileManager($filesystem, $configRepository);

        $this->assertFalse($hostsFileManager->getSection());
    }

    public function testAddHostConfigAndMarvinSectionInHostsFile()
    {
        // Initial content
        $contentFile = <<<HOSTS
127.0.0.1 localhost
127.0.1.1 Desktop
127.0.2.1 mywebdev.local mywebdev webdev local

# Test App
192.168.10.10 test.app

# Site Host
192.168.50.4  local.site.dev

# MV
#192.168.13.101 mv
HOSTS;

        // Content changed by the Marvin
        $finalContentFile = <<<HOSTS
127.0.0.1 localhost
127.0.1.1 Desktop
127.0.2.1 mywebdev.local mywebdev webdev local

# Test App
192.168.10.10 test.app

# Site Host
192.168.50.4  local.site.dev

# MV
#192.168.13.101 mv

#=========Sirius CybeC - Entry=========
192.168.42.42 marvin.dev marvin.local wwww.marvin.dev ### Marvin ID: 4986786462
#=========Sirius CybeC - Exit=========
HOSTS;

        // Configurations
        $configs = [
            'app.temporary-dir' => '/app/tmp',
            'hostsfile.path'    => '/etc/hosts',
        ];

        // Setup Filesystem
        $filesystem = $this->getMockBuilder('Marvin\Filesystem\Filesystem')
                           ->setMethods(['get', 'put'])
                           ->getMock();

        $filesystem->expects($this->any())
                   ->method('get')
                   ->with($configs['hostsfile.path'])
                   ->will($this->returnValue($contentFile));

        $tempFile = $configs['app.temporary-dir'] . DIRECTORY_SEPARATOR . 'hosts';

        $filesystem->expects($this->once())
                   ->method('put')
                   ->with($this->equalTo($tempFile), $this->equalTo($finalContentFile))
                   ->will($this->returnValue(true));

        // Setup Config\Repository
        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->setMethods(['get'])
                                 ->getMock();

        $configRepository->expects($this->exactly(2))
                         ->method('get')
                         ->withConsecutive(
                             [$this->equalTo('hostsfile.path')],
                             [$this->equalTo('app.temporary-dir')]
                         )
                         ->will($this->returnCallback(function ($key) use ($configs) {
                             return $configs[$key];
                         }));


        // Setup HostManager
        $hostConfigs = [
            'ip'           => '192.168.42.42',
            'id'           => '4986786462',
            'server-name'  => 'marvin.dev',
            'server-alias' => 'marvin.local wwww.marvin.dev',
        ];

        $vhManager = $this->getMockBuilder('Marvin\Contracts\HostManager')
                          ->setMethods([])
                          ->getMock();

        $vhManager->expects($this->any())
                  ->method('get')
                  ->will($this->returnCallback(function ($key) use ($hostConfigs) {
                      if (key_exists($key, $hostConfigs)) {
                          return $hostConfigs[$key];
                      }
                      return $hostConfigs;
                  }));

        $hostsFileManager = new HostsFileManager($filesystem, $configRepository);

        $result = $hostsFileManager->addHost($vhManager);

        $this->assertTrue($result);
    }

    public function testAddHostConfigInExistingSectionInTheHostsFile()
    {
        // Initial content
        $contentFile = <<<HOSTS
127.0.0.1 localhost
127.0.1.1 Desktop
127.0.2.1 mywebdev.local mywebdev webdev local

# Test App
192.168.10.10 test.app

# Site Host
192.168.50.4  local.site.dev

# MV
#192.168.13.101 mv

#=========Sirius CybeC - Entry=========
192.168.42.42 marvin.dev marvin.local wwww.marvin.dev ### Marvin ID: 4986786462
#=========Sirius CybeC - Exit=========
HOSTS;

        // Content changed by the Marvin
        $finalContentFile = <<<HOSTS
127.0.0.1 localhost
127.0.1.1 Desktop
127.0.2.1 mywebdev.local mywebdev webdev local

# Test App
192.168.10.10 test.app

# Site Host
192.168.50.4  local.site.dev

# MV
#192.168.13.101 mv

#=========Sirius CybeC - Entry=========
192.168.42.42 marvin.dev marvin.local wwww.marvin.dev ### Marvin ID: 4986786462
192.168.50.92 my.local.dev ### Marvin ID: 6894949879
#=========Sirius CybeC - Exit=========
HOSTS;

        // Configurations
        $configs = [
            'app.temporary-dir' => '/app/tmp',
            'hostsfile.path'    => '/etc/hosts',
        ];

        // Setup Filesystem
        $filesystem = $this->getMockBuilder('Marvin\Filesystem\Filesystem')
                           ->setMethods(['get', 'put'])
                           ->getMock();

        $filesystem->expects($this->any())
                   ->method('get')
                   ->with($configs['hostsfile.path'])
                   ->will($this->returnValue($contentFile));

        $tempFile = $configs['app.temporary-dir'] . DIRECTORY_SEPARATOR . 'hosts';
        $filesystem->expects($this->once())
                   ->method('put')
                   ->with($this->equalTo($tempFile), $this->equalTo($finalContentFile))
                   ->will($this->returnValue(true));

        // Setup Config\Repository
        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->setMethods(['get'])
                                 ->getMock();

        $configRepository->expects($this->exactly(2))
                         ->method('get')
                         ->withConsecutive(
                             [$this->equalTo('hostsfile.path')],
                             [$this->equalTo('app.temporary-dir')]
                         )
                         ->will($this->returnCallback(function ($key) use ($configs) {
                             return $configs[$key];
                         }));

        // Setup HostManager
        $hostConfigs = [
            'ip'          => '192.168.50.92',
            'id'          => '6894949879',
            'server-name' => 'my.local.dev',
        ];

        $vhManager = $this->getMockBuilder('Marvin\Contracts\HostManager')
                          ->setMethods([])
                          ->getMock();

        $vhManager->expects($this->any())
                  ->method('get')
                  ->will($this->returnCallback(function ($key) use ($hostConfigs) {
                      if (key_exists($key, $hostConfigs)) {
                          return $hostConfigs[$key];
                      }
                      return $hostConfigs;
                  }));

        $hostsFileManager = new HostsFileManager($filesystem, $configRepository);

        $result = $hostsFileManager->addHost($vhManager);

        $this->assertTrue($result);
    }
}
