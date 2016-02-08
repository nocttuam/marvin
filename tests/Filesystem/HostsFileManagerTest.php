<?php
namespace Marvin\Filesystem;

use Marvin\Filesystem\HostsFileManager;
use Marvin\Filesystem\Filesystem;

class HostsFileManagerTest extends \PHPUnit_Framework_TestCase
{

    public function testReceiveFilesystemInstance()
    {
        $filesystem = $this->getMockBuilder('Marvin\Filesystem\Filesystem')
                           ->getMock();

        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->setMethods(['get'])
                                 ->getMock();

        $configRepository->expects($this->once())
                         ->method('get')
                         ->with($this->equalTo('host-file-path'), $this->equalTo('/etc/host'))
                         ->will($this->returnValue('/etc/host'));

        $hostsFileManager = new HostsFileManager($filesystem, $configRepository);

        $this->assertAttributeInstanceOf(
            Filesystem::class,
            'filesystem',
            $hostsFileManager
        );

        $this->assertAttributeInstanceOf(
            'Marvin\Config\Repository',
            'configRepository',
            $hostsFileManager
        );

        $tempDir = realpath('.') . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'tmp';
        $this->assertAttributeEquals('/etc/host', 'filePath', $hostsFileManager);
        $this->assertAttributeEquals($tempDir, 'tempDir', $hostsFileManager);
    }

    public function testShouldReadEtcHostsFile()
    {
        $content = '192.168.42.42 marvin.app # ID: 923894213214152';


        $filesystem = $this->getMockBuilder('Marvin\Filesystem\Filesystem')
                           ->setMethods(['get'])
                           ->getMock();

        $filesystem->expects($this->once())
                   ->method('get')
                   ->will($this->returnValue($content));

        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->getMock();


        $fileHost = new HostsFileManager($filesystem, $configRepository);

        $this->assertContains($content, $fileHost->read());
    }

    public function testShouldReturnAlertIfHostsFileNotExist()
    {
        $message = 'File does not exist';


        $filesystem = $this->getMockBuilder('Marvin\Filesystem\Filesystem')
                           ->setMethods(['get'])
                           ->getMock();

        $filesystem->expects($this->once())
                   ->method('get')
                   ->will($this->throwException(new \Illuminate\Contracts\Filesystem\FileNotFoundException($message)));

        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->getMock();


        $fileHost = new HostsFileManager($filesystem, $configRepository);

        $this->assertEquals($message, $fileHost->read());
    }

    public function testWriteConfigurationsOfTheHostsFile()
    {
        $DS = DIRECTORY_SEPARATOR;
        $parameters = [
            'id'             => '6c6b2c7c78c38576c7775e4ce82a9770',
            'ip'             => '192.168.42.42',
            'server-name'    => 'marvin.app',
            'server-alias'   => 'www.marvin.app marvin.dev marvin.local',
            'original-file'  => '/etc/host',
            'temporary-file' => realpath('.') . $DS . 'app' . $DS . 'tmp'. $DS . 'hosts',
        ];

        $hostConfigs = '192.168.42.42 marvin.app www.marvin.app marvin.dev marvin.local # ID: 6c6b2c7c78c38576c7775e4ce82a9770 // Created by Marvin';

        // Marvin\Config\Repository Mock
        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->setMethods(['get'])
                                 ->getMock();

        $configRepository->expects($this->once())
                         ->method('get')
                         ->will($this->returnValue($parameters['original-file']));

        // Marvin\Filesystem\Filesystem Mock
        $filesystem = $this->getMockBuilder('Marvin\Filesystem\Filesystem')
                           ->setMethods(['get', 'put'])
                           ->getMock();

        $filesystem->expects($this->once())
                   ->method('get')
                   ->with($this->equalTo($parameters['original-file']))
                   ->will($this->returnValue($this->hostFileInitContent()));

        $filesystem->expects($this->once())
                   ->method('put')
                   ->with(
                       $this->equalTo($parameters['temporary-file']),
                       $this->hostFileInitContent() . PHP_EOL . $hostConfigs
                   )
                   ->will($this->returnValue(true));

        // Marvin\Hosts\Apache Mock
        $apacheManager = $this->getMockBuilder('Marvin\Hosts\Apache')
                              ->disableOriginalConstructor()
                              ->setMethods(['get'])
                              ->getMock();

        $apacheManager->expects($this->exactly(4))
                      ->method('get')
                      ->will($this->returnCallback(function ($key) use ($parameters) {
                          $result = '';
                          switch ($key) {
                              case 'id':
                                  $result = $parameters['id'];
                                  break;
                              case 'ip':
                                  $result = $parameters['ip'];
                                  break;
                              case 'server-name':
                                  $result = $parameters['server-name'];
                                  break;
                              case 'server-alias':
                                  $result = $parameters['server-alias'];
                                  break;
                          }

                          return $result;
                      }));


        $hostsManager = new HostsFileManager($filesystem, $configRepository);
        $hostsManager->includeHost($apacheManager);

    }

    protected function hostFileInitContent()
    {
        $content = <<<EOD
127.0.0.1	localhost
127.0.1.1	Desktop
127.0.2.1	mywebdev.local mywebdev webdev local

#
192.168.10.10	app.test
192.168.10.10	app.dev

#
192.168.50.4  my.app

#
192.168.42.42 marvin.app # ID: 923894213214152 // Created by Marvin
192.168.42.42 marvin.app # ID: 3214152392389421 // Created by Marvin

# The following lines are desirable for IPv6 capable hosts
    ::1     localhost ip6-localhost ip6-loopback
ff02::1 ip6-allnodes
ff02::2 ip6-allrouters
EOD;

        return $content;
    }
}
