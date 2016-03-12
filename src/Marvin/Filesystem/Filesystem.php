<?php
namespace Marvin\Filesystem;

use \Illuminate\Filesystem\Filesystem as IlluminateFilesystem;

class Filesystem extends IlluminateFilesystem
{
    /**
     * Move Files using especial permissions in Linux
     *
     * @param $file
     * @param $target
     *
     * @return string
     */
    public function sysMove($file, $target)
    {
        return shell_exec('sudo mv -v ' . $file . ' ' . $target);
    }
}