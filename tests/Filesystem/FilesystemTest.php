<?php
namespace Marvin\Filesystem;

function shell_exec($command)
{
    if (preg_match('/(sudo mv)/', $command)) {
        return $command;
    }
    return false;
}

class FilesystemTest extends \PHPUnit_Framework_TestCase
{

    public function testShouldIlluminateFilesystemInstance()
    {
        $filesystem = new Filesystem();
        $this->assertInstanceOf('Illuminate\Filesystem\Filesystem', $filesystem);
    }

    public function testShouldMoveFilesThatNeedEspecialPermissions()
    {
        $filesystem = new Filesystem();
        $file = '/app/tmp/file';
        $target = '/system/root/';
        $return = $filesystem->sysMove($file, $target);

        $this->assertRegExp('/sudo mv -v/', $return);
        $this->assertRegExp('/\/app\/tmp\/file/', $return);
        $this->assertRegExp('/\/system\/root\//', $return);
    }
}
