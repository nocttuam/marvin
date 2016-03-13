<?php
namespace Marvin\Filesystem;

class TemplateTest extends \PHPUnit_Framework_TestCase
{

    protected $host;

    protected $filesystem;

    public function testSetInitialParametersCorrectly()
    {
        $filesystem = $this->getMockBuilder('Marvin\Filesystem\Filesystem')
                           ->setMethods([])
                           ->getMock();

        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->setMethods([])
                                 ->getMock();

        $template = new Template($configRepository, $filesystem);

        $this->assertAttributeInstanceOf(
            'Marvin\Filesystem\Filesystem',
            'filesystem',
            $template
        );

        $this->assertAttributeInstanceOf(
            'Marvin\Config\Repository',
            'configRepository',
            $template
        );

    }

    public function testCompileNewConfigurationsFileAndReturnResult()
    {
        $serverName     = 'marvin.dev';
        $id             = md5($serverName);

        $configurations = [
            'app'      => [
                'templates-dir'  => '/app/templates',
                'temporary-dir' => 'app/temp',
            ],
            'apache'   => [
                'host'          => 'apache',
                'id'            => $id,
                'ip'            => '192.168.4.2',
                'server-name'   => $serverName,
                'server-alias'  => 'www.marvin.dev marvin.local.dev marvin.develop.host',
                'document-root' => '/home/marvin/site/public',
                'file-name'     => 'marvin.dev.conf',
            ],
            'defaults' => [
                'port' => '8080',
            ],
        ];

        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->setMethods(['get'])
                                 ->getMock();

        $configRepository->expects($this->any())
                         ->method('get')
                         ->will($this->returnCallback(function ($key) use ($configurations) {
                             if (key_exists($key, $configurations)) {
                                 return $configurations[$key];
                             }
                             $keys = explode('.', $key);
                             if (key_exists($keys[0], $configurations)) {
                                 return $configurations[$keys[0]][$keys[1]];
                             }

                             return $configurations;
                         }));


        // VHostManager setups

        $vhManager = $this->getMockBuilder('Marvin\Contracts\HostManager')
                          ->setMethods([])
                          ->getMock();

        $vhManager->expects($this->any())
                  ->method('get')
                  ->will($this->returnCallback(function ($key) use ($configurations) {
                      if (key_exists($key, $configurations['apache'])) {
                          return $configurations['apache'][$key];
                      }

                      if (key_exists($key, $configurations['defaults'])) {
                          return $configurations['defaults'][$key];
                      }

                      return $configurations;
                  }));

        // Filesystem\Template setups

        $templateContent = <<<TEMPLATE
<VirtualHost {{ip}}:{{port}}>
{!!    ServerAdmin {{server-admin}}!!}
    ServerName {{server-name}}
    ServerAlias {{server-alias}}
    DocumentRoot {{document-root}}
</VirtualHost>
TEMPLATE;

        $compiledContent = <<<HOSTCONF
<VirtualHost 192.168.4.2:8080>

    ServerName marvin.dev
    ServerAlias www.marvin.dev marvin.local.dev marvin.develop.host
    DocumentRoot /home/marvin/site/public
</VirtualHost>

# Created by Marvin // ID: {$id}

HOSTCONF;


        $templateApacheFile = $configurations['app']['templates-dir'] . DIRECTORY_SEPARATOR . 'apache.stub';
        $fileName           = $configurations['apache']['file-name'];
        $compiledFileDest   = $configurations['app']['temporary-dir'] . DIRECTORY_SEPARATOR . $fileName;

        $filesystem = $this->getMockBuilder('Marvin\Filesystem\Filesystem')
                           ->setMethods(['get', 'files', 'put'])
                           ->getMock();

        $filesystem->expects($this->once())
                   ->method('files')
                   ->with($this->equalTo($configurations['app']['templates-dir']))
                   ->will($this->returnValue([$templateApacheFile, 'ngnix.stub']));

        $filesystem->expects($this->once())
                   ->method('get')
                   ->with($this->equalTo($templateApacheFile))
                   ->will($this->returnValue($templateContent));

        $filesystem->expects($this->once())
                   ->method('put')
                   ->with($this->equalTo($compiledFileDest), $this->equalTo($compiledContent))
                   ->will($this->returnValue(true));

        $template = new Template($configRepository, $filesystem);


        $this->assertTrue($template->compile($vhManager));

    }


    /**
     * @expectedException \Exception
     * @expectedExceptionMessage is not a valid tag
     */
    public function testThrowExceptionIfRequiredTagExistInTemplateButNotInTagsList()
    {
        $configurations = [
            'app'      => [
                'templates-dir'  => '/app/templates',
                'temporary-dir' => 'app/temp',
            ],
            'apache'   => [
                'host'          => 'apache',
                'ip'            => '192.168.4.2',
                'server-name'   => 'marvin.dev',
                'server-alias'  => 'www.marvin.dev marvin.local.dev marvin.develop.host',
                'document-root' => '/home/marvin/site/public',
                'file-name'     => 'marvin.dev.conf',
            ],
            'defaults' => [
                'port' => '8080',
            ],
        ];

        $configRepository = $this->getMockBuilder('Marvin\Config\Repository')
                                 ->setMethods(['get'])
                                 ->getMock();

        $configRepository->expects($this->any())
                         ->method('get')
                         ->with($this->equalTo('app.templates-dir'))
                         ->will($this->returnValue($configurations['app']['templates-dir']));


        // VHostManager setups

        $vhManager = $this->getMockBuilder('Marvin\Contracts\HostManager')
                          ->setMethods([])
                          ->getMock();

        $vhManager->expects($this->any())
                  ->method('get')
                  ->will($this->returnCallback(function ($key) use ($configurations) {
                      if (key_exists($key, $configurations['apache'])) {
                          return $configurations['apache'][$key];
                      }

                      if (key_exists($key, $configurations['defaults'])) {
                          return $configurations['defaults'][$key];
                      }

                      return $configurations;
                  }));


        // Filesystem\Template setups

        $templateApacheFile = $configurations['app']['templates-dir'] . DIRECTORY_SEPARATOR . 'apache.stub';
        $templateContent    = 'They are a simple {{tag}} and a {{fake-tag}}';


        $filesystem = $this->getMockBuilder('Marvin\Filesystem\Filesystem')
                           ->setMethods(['get', 'files'])
                           ->getMock();

        $filesystem->expects($this->once())
                   ->method('files')
                   ->with($this->equalTo($configurations['app']['templates-dir']))
                   ->will($this->returnValue([$templateApacheFile, 'ngnix.stub']));

        $filesystem->expects($this->once())
                   ->method('get')
                   ->with($this->equalTo($templateApacheFile))
                   ->will($this->returnValue($templateContent));


        $template = new Template($configRepository, $filesystem);


        $this->assertTrue($template->compile($vhManager));
    }

}
