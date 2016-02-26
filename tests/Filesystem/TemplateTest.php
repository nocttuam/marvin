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

        $template = new Template($filesystem);

        $this->assertAttributeInstanceOf(
            'Marvin\Filesystem\Filesystem',
            'filesystem',
            $template
        );


    }

//    public function testSetHostUsingTemplate()
//    {
//        $file = '/path/to/template.stub';
//
//        $host = $this->getMockBuilder('Marvin\Contracts\Host')
//                    ->setMethods([])
//                    ->getMock();
//
//        $host->expects($this->once())
//             ->method('get')
//             ->will($this->returnValue($file));
//
//        $filesystem = $this->getMockBuilder('Marvin\Filesystem\Filesystem')
//                           ->setMethods(['exists', 'get'])
//                           ->getMock();
//
//        $filesystem->expects($this->once())
//                   ->method('exists')
//                   ->will($this->returnValue(true));
//
//        $filesystem->expects($this->once())
//                   ->method('get')
//                   ->will($this->returnValue('Content'));
//
//        $this->assertAttributeInstanceOf(
//            'Marvin\Contracts\Host',
//            'host',
//            $template
//        );
//
//        $template = new Template($filesystem);
//
//
//        $this->assertAttributeEquals(
//            $file,
//            'file',
//            $template
//        );
//    }

    public function testShouldSetFilePathAndContentIfFileIsValidTemplate()
    {
        $file = '/path/to/template/file.stub';

        $content = 'This is a content';


        $filesystem = $this->getMockBuilder('Marvin\Filesystem\Filesystem')
                           ->setMethods(['exists', 'get'])
                           ->getMock();

        $filesystem->expects($this->any())
                   ->method('exists')
                   ->will($this->returnValue(true));

        $filesystem->expects($this->any())
                   ->method('get')
                   ->will($this->returnValue($content));

        $template = new Template($filesystem);

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

        $template = new Template($filesystem);

        $template->file($file);
    }

    public function testGetFileContent()
    {
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

        $file = 'path/to/nonexistent/file';

        $filesystem = $this->getMockBuilder('Marvin\Filesystem\Filesystem')
                           ->setMethods(['exists', 'get'])
                           ->getMock();

        $filesystem->expects($this->any())
                   ->method('exists')
                   ->will($this->returnValue(true));

        $filesystem->expects($this->any())
                   ->method('get')
                   ->with($this->equalTo($file))
                   ->will($this->returnValue($content));

        $template = new Template($filesystem);

        $this->assertEquals($content, $template->content($file));
    }

    public function testReplaceTagsInContent()
    {
        $templateContent = <<<TEMPLATE
<VirtualHost {{ip}}>
    ServerAdmin {{server-admin}}
    ServerName {{server-name}}
    ServerAlias {{server-alias}}
    DocumentRoot {{document-root}}
</VirtualHost>
TEMPLATE;

        $tags = [
            'ip'            => '192.168.4.2',
            'server-name'   => 'marvin.dev',
            'server-admin'  => 'marvin@emailhost',
            'server-alias'  => 'www.marvin.dev marvin.local.dev marvin.develop.host',
            'document-root' => '/home/marvin/site/public',
            'template-path' => 'path/to/template/file.stub',
        ];

        $expected = <<<EXPEC
<VirtualHost 192.168.4.2>
    ServerAdmin marvin@emailhost
    ServerName marvin.dev
    ServerAlias www.marvin.dev marvin.local.dev marvin.develop.host
    DocumentRoot /home/marvin/site/public
</VirtualHost>
EXPEC;

        $host = $this->getMockBuilder('Marvin\Contracts\Host')
                     ->disableOriginalConstructor()
                     ->setMethods([])
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
                   ->will($this->returnValue($templateContent))
                   ->with($this->equalTo($tags['template-path']));

        $filesystem->expects($this->once())
                   ->method('exists')
                   ->will($this->returnValue(true));

        $template = new Template($filesystem);

        $this->assertEquals($expected, $template->compile($host, $tags));
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage is not a valid tag
     */
    public function testThrowExceptionIfRequiredTagExistInTemplateButNotInTagsList()
    {
        $template = 'It is a simple {{tag}} and a {{fake-tag}}';

        $tags = ['tag' => "I'm really tag"];

        $host = $this->getMockBuilder('Marvin\Contracts\Host')
                     ->disableOriginalConstructor()
                     ->setMethods([])
                     ->getMock();

        $host->expects($this->any())
             ->method('get')
             ->will($this->returnValue('path/to/template/file.stub'));

        $filesystem = $this->getMockBuilder('Marvin\Filesystem\Filesystem')
                           ->setMethods(['get', 'exists'])
                           ->getMock();

        $filesystem->expects($this->once())
                   ->method('get')
                   ->will($this->returnValue($template))
                   ->with($this->equalTo('path/to/template/file.stub'));

        $filesystem->expects($this->once())
                   ->method('exists')
                   ->will($this->returnValue(true));

        $template = new Template($filesystem);

        $template->compile($host, $tags);
    }

    public function testReplaceOptionalContentAndDeleteIfTagsNotExist()
    {
        $templateContent = <<<TEMPLATE
<VirtualHost {{ip}}{!!:{{port}}!!}>
{!!    ServerAdmin {{server-admin}}!!}
    ServerName {{server-name}}
{!!    ServerAlias {{server-alias}}!!}
    DocumentRoot {{document-root}}
</VirtualHost>
TEMPLATE;

        $tags = [
            'ip'            => '192.168.4.2',
            'port'          => '8080',
            'server-name'   => 'marvin.dev',
            'server-alias'  => 'www.marvin.dev marvin.local.dev marvin.develop.host',
            'document-root' => '/home/marvin/site/public',
            'template-path' => 'path/to/template/file.stub',
        ];

        $expected = <<<EXPEC
<VirtualHost 192.168.4.2:8080>

    ServerName marvin.dev
    ServerAlias www.marvin.dev marvin.local.dev marvin.develop.host
    DocumentRoot /home/marvin/site/public
</VirtualHost>
EXPEC;


        $host = $this->getMockBuilder('Marvin\Contracts\Host')
                     ->disableOriginalConstructor()
                     ->setMethods([])
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
                   ->will($this->returnValue($templateContent))
                   ->with($this->equalTo($tags['template-path']));

        $filesystem->expects($this->once())
                   ->method('exists')
                   ->will($this->returnValue(true));

        $template = new Template($filesystem);

        $this->assertEquals($expected, $template->compile($host, $tags));
    }
}
