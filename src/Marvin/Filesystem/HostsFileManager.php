<?php
namespace Marvin\Filesystem;

use Illuminate\Contracts\Filesystem\FileNotFoundException;

class HostsFileManager
{
    protected $filePath = '/etc/hosts';

    protected $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function setPath($path)
    {
        $this->filePath = $path;
    }

    public function read()
    {
        try {
            return $this->filesystem->get($this->filePath);
        } catch (FileNotFoundException $e) {
            return $e->getMessage();
        }
    }

    public function includeHost($id, $ip, $serverName, array $serverAlias)
    {
        $serverAlias = implode(' ', $serverAlias);
        $content     = PHP_EOL . $ip . ' ' . $serverName . ' ' . $serverAlias . ' ' . '# ID: ' . $id . ' // Created by Marvin';
        $this->filesystem->append($this->filePath, $content);
    }

}