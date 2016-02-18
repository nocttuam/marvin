<?php
namespace Marvin\Filesystem;


class TemplateTest extends \PHPUnit_Framework_TestCase
{

    protected $host;

    protected $filesystem;

    protected function setUp()
    {
        $this->host = $this->getMockBuilder('Marvin\Contracts\Host')
                           ->setMethods([])
                           ->getMock();

        $this->filesystem = $this->getMockBuilder('Marvin\Filesystem\Filesystem')
                                 ->getMock();
    }


    public function testSetDependenciesCorrectly()
    {
        $host = $this->getMockBuilder('Marvin\Contracts\Host')
                     ->setMethods([])
                     ->getMock();

        $filesystem = $this->getMockBuilder('Marvin\Filesystem\Filesystem')
                           ->getMock();

        $template = new Template($host, $filesystem);

        $this->assertAttributeInstanceOf(
            'Marvin\Contracts\Host',
            'host',
            $template
        );

        $this->assertAttributeInstanceOf(
            'Marvin\Filesystem\Filesystem',
            'filesystem',
            $template
        );
    }

    public function testShouldSetFilePathAndContentIfFileIsValidTemplate()
    {
        $file = '/path/to/template/file.stub';

        $content = 'This is a content';

        $filesystem = $this->getMockBuilder('Marvin\Filesystem\Filesystem')
                           ->setMethods(['exists', 'get'])
                           ->getMock();

        $filesystem->expects($this->once())
                   ->method('exists')
                   ->will($this->returnValue(true));

        $filesystem->expects($this->once())
            ->method('get')
            ->with($this->equalTo($file))
            ->will($this->returnValue($content));

        $template = new Template($this->host, $filesystem);

        $template->file($file);

        $this->assertAttributeEquals($file, 'file', $template);
        $this->assertAttributeEquals($content, 'content', $template);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Template file not exist
     */
    public function testThrowExceptionIfFileNotExist()
    {
        $file = 'path/to/nonexistent/file';

        $filesystem = $this->getMockBuilder('Marvin\Filesystem\Filesystem')
                           ->setMethods(['exists'])
                           ->getMock();

        $filesystem->expects($this->once())
                   ->method('exists')
                   ->will($this->returnValue(false));

        $template = new Template($this->host, $filesystem);

        $template->file($file);
    }

    public function testGetFileContent()
    {
        $file = 'path/to/nonexistent/file';

        $content = <<<CONF
<VirtualHost {{ip}}>
    ServerAdmin {{server-admin}}
    ServerName {{server-name}}
    ServerAlias {{alias}
    DocumentRoot {{document-root}}

    ErrorLog {{log-path}}/{{server-name}}-error.log
    CustomLog {log-path}}/{{server-name}}-access.log combined

    <Directory {{document-root}}>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
CONF;


        $filesystem = $this->getMockBuilder('Marvin\Filesystem\Filesystem')
                           ->setMethods(['exists', 'get'])
                           ->getMock();

        $filesystem->expects($this->once())
                   ->method('exists')
                   ->will($this->returnValue(true));

        $filesystem->expects($this->once())
                   ->method('get')
                   ->with($this->equalTo($file))
                   ->will($this->returnValue($content));

        $template = new Template($this->host, $filesystem);

        $this->assertEquals($content, $template->content($file));
    }

    public function testReplaceTagsInTemplateAndReturnConfigurations()
    {
        $file = 'path/to/template/file.stub';

        $content = <<<CONF
<VirtualHost {{ip}}>
    ServerAdmin {{server-admin}}
    ServerName {{server-name}}
    ServerAlias {{server-alias}}
    DocumentRoot {{document-root}}

    ErrorLog {{log-path}}/{{server-name}}-error.log
    CustomLog {{log-path}}/{{server-name}}-access.log combined

    <Directory {{document-root}}>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
CONF;

        $newContent = <<<CONF
<VirtualHost 192.168.4.2:8080>
    ServerAdmin marvin@emailhost
    ServerName marvin.dev
    ServerAlias www.marvin.dev marvin.local.dev marvin.develop.host
    DocumentRoot /home/marvin/site/public

    ErrorLog /home/marvin/logs/marvin.dev-error.log
    CustomLog /home/marvin/logs/marvin.dev-access.log combined

    <Directory /home/marvin/site/public>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
CONF;

        $tags = [
            'ip'            => '192.168.4.2:8080',
            'server-name'   => 'marvin.dev',
            'server-admin'  => 'marvin@emailhost',
            'server-alias'  => 'www.marvin.dev marvin.local.dev marvin.develop.host',
            'document-root' => '/home/marvin/site/public',
            'log-path'      => '/home/marvin/logs',
        ];

        $host = $this->getMockBuilder('Marvin\Contracts\Host')
                     ->disableOriginalConstructor()
                     ->setMethods(['get', 'set', 'create'])
                     ->getMock();

        $host->expects($this->any())
             ->method('get')
             ->will($this->returnCallback(function ($key) use ($tags) {
                 if (key_exists($key, $tags)) {
                     return $tags[$key];
                 } else {
                     return $tags;
                 }
             }));

        $filesystem = $this->getMockBuilder('Marvin\Filesystem\Filesystem')
                           ->setMethods(['get', 'exists'])
                           ->getMock();

        $filesystem->expects($this->once())
                   ->method('get')
                   ->will($this->returnValue($content))
                   ->with($this->equalTo($file));

        $filesystem->expects($this->once())
                   ->method('exists')
                   ->will($this->returnValue(true));

        $template = new Template($host, $filesystem);
        $template->file($file);

        $this->assertEquals($newContent, $template->render($tags));
    }
}
