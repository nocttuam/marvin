<?php
namespace Marvin\Filesystem;

use Marvin\Contracts\Host;

class Template
{
    protected $host;

    protected $filesystem;

    protected $file;

    protected $content;

    /**
     * Template constructor.
     *
     * @param Host       $host
     * @param Filesystem $filesystem
     */
    public function __construct(Host $host, Filesystem $filesystem)
    {
        $this->host       = $host;
        $this->filesystem = $filesystem;
    }

    public function file($file)
    {
        if ( ! $this->filesystem->exists($file)) {
            throw new \InvalidArgumentException('Template file not exist');
        }
        $this->file    = $file;
        $this->content = $this->filesystem->get($file);
    }

    public function content($file = null)
    {
        if ( ! is_null($file)) {
            $this->file($file);
        }

        return $this->content;
    }

    public function render(array $tags)
    {
        $content = $this->content();
        foreach ($tags as $tag => $value) {
            $content = $this->replaceTag($tag, $value, $content);
        }

        return $content;
    }

    protected function replaceTag($tag, $value, $content)
    {
        $pattern = '/(' . '{{' . $tag . '}}' . ')/';
        $result  = preg_replace($pattern, $value, $content);

        return $result;
    }
}