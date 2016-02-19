<?php
namespace Marvin\Filesystem;

use Marvin\Contracts\Host;

class Template
{
    /**
     * @var Host
     */
    protected $host;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * Template path
     *
     * @var string
     */
    protected $file;

    /**
     * Template content
     *
     * @var string
     */
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
        $this->file($this->host->get('template-path'));
    }

    /**
     * Set template file path and template content.
     *
     * @param $file
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function file($file)
    {
        if ( ! $this->filesystem->exists($file)) {
            throw new \InvalidArgumentException('Template file not exist');
        }
        $this->file    = $file;
        $this->content = $this->filesystem->get($file);
    }

    /**
     * Get content in template file
     *
     * @param null $file
     *
     * @return string
     */
    public function content($file = null)
    {
        if ( ! is_null($file)) {
            $this->file($file);
        }

        return $this->content;
    }

    /**
     * Build new content replacing tags in template content
     * Return final content
     *
     * @param array $tags
     *
     * @return string
     */
    public function compile(array $tags)
    {
        $content = $this->content();
        foreach ($tags as $tag => $value) {
            $content = $this->replaceTag($tag, $value, $content);
        }

        return $content;
    }

    /**
     * Replace specified tag in template content
     *
     * @param string $tag
     * @param string $value
     * @param string $content
     *
     * @return string
     */
    protected function replaceTag($tag, $value, $content)
    {
        $pattern = '/(' . '{{' . $tag . '}}' . ')/';
        $result  = preg_replace($pattern, $value, $content);

        return $result;
    }
}