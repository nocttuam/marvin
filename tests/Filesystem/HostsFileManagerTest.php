<?php
namespace Marvin\Filesystem;

use Marvin\Filesystem\HostsFileManager;
use Marvin\Filesystem\Filesystem;

class HostsFileManagerTest extends \PHPUnit_Framework_TestCase
{

    protected $filesystem;

    protected $mockEtcDir;

    protected function setUp()
    {
        $this->filesystem = new Filesystem();
        $this->mockEtcDir = realpath('.') . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'etc';
        $this->createFolderStructure();
    }

    protected function createFolderStructure()
    {
        if ( ! file_exists($this->mockEtcDir)) {
            mkdir($this->mockEtcDir, 0777, true);
            file_put_contents(
                $this->mockEtcDir . DIRECTORY_SEPARATOR . 'hosts',
                $this->hostFileInitContent()
            );
        }
    }

    protected function tearDown()
    {
        $this->destructFolderStructure($this->mockEtcDir);
    }

    protected function destructFolderStructure($directory)
    {
        if (file_exists($directory)) {
            $files = glob($directory . '/*');
            foreach ($files as $file) {
                is_dir($file) ? $this->destructFolderStructure($file) : unlink($file);
            }
            rmdir($directory);
            return;
        }
    }

    public function testReceiveFilesystemInstance()
    {
        $filesystem       = new Filesystem();
        $hostsFileManager = new HostsFileManager($filesystem);

        $this->assertAttributeInstanceOf(
            Filesystem::class,
            'filesystem',
            $hostsFileManager
        );
    }

    public function testGetPathForEtcHostsFile()
    {
        $path             = '/etc/hosts';
        $newPath          = 'C:\Windows\System32\drivers\etc';
        $hostsFileManager = new HostsFileManager($this->filesystem);

        $this->assertAttributeEquals($path, 'filePath', $hostsFileManager);

        $hostsFileManager->setPath($newPath);

        $this->assertAttributeEquals($newPath, 'filePath', $hostsFileManager);
    }

    public function testShouldReadEtcHostsFile()
    {
        $file = new HostsFileManager($this->filesystem);

        $content = '192.168.42.42 marvin.app # ID: 923894213214152';

        $path = $this->mockEtcDir . DIRECTORY_SEPARATOR . 'hosts';

        $file->setPath($path);

        $this->assertContains($content, $file->read());
    }

    public function testShouldReturnAlertIfHostsFileNotExist()
    {
        $file = new HostsFileManager($this->filesystem);

        $path = realpath('.') . '/file/no/exist';

        $message = 'not exist';

        $file->setPath($path);

        $this->assertRegExp('/' . $message . '/', $file->read());
    }

    public function testWriteVirtualHostInformationInHostsFile()
    {
        $file = new HostsFileManager($this->filesystem);
        $path = $this->mockEtcDir . DIRECTORY_SEPARATOR . 'hosts';
        $file->setPath($path);

        $id          = '6c6b2c7c78c38576c7775e4ce82a9770';
        $ip          = '192.168.42.42';
        $serverName  = 'marvin.app';
        $serverAlias = ['www.marvin.app', 'marvin.dev', 'marvin.local'];

        $file->includeHost($id, $ip, $serverName, $serverAlias);

        $hostConfigs = '192.168.42.42 marvin.app www.marvin.app marvin.dev marvin.local ' .
                       '# ID: 6c6b2c7c78c38576c7775e4ce82a9770 // Created by Marvin';

        $fileContent = file_get_contents($path);

//        $this->assertRegExp('/' . $hostConfigs . '/', $fileContent);

        $this->assertContains($hostConfigs, $fileContent);

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
