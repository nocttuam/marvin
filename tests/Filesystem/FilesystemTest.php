<?php

class FilesystemTest extends PHPUnit_Framework_TestCase
{

    public function testShouldIlluminateFilesystemInstance()
    {
        $filesystem = new \Marvin\Filesystem\Filesystem();
        $this->assertInstanceOf('Illuminate\Filesystem\Filesystem',$filesystem);
    }
}
