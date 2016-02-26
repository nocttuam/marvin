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
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
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
     * @param Host  $host
     * @param array $tags
     *
     * @return string
     */
    public function compile(Host $host, array $tags)
    {
        $this->setHost($host);
        $content = $this->content();
        $content = $this->parseOptionalTags($tags, $content);
        $content = $this->parseRequiredTags($tags, $content);

        return $content;
    }

    /**
     * Set Host with is calling a Template instance
     *
     * @param Host $host
     */
    protected function setHost(Host $host)
    {
        $this->host = $host;
        $this->file($this->host->get('template-path'));
    }

    /**
     * Search and replace optional tags.
     * Delete wrap content if optional tag is not exist.
     *
     * @param array $tags
     * @param       $content
     *
     * @return mixed
     */
    protected function parseOptionalTags(array $tags, $content)
    {
        $wraps = ['{!!', '!!}', '{{', '}}'];

        return preg_replace_callback(
            '/[{]{1}[!]{2}(.*)({{)(.+)(}})(.*)[!]{2}[}]{1}/',
            function ($input) use ($tags, $wraps) {
                $key     = $input[3];
                $content = $input[0];
                $output  = '';
                if (key_exists($key, $tags)) {
                    $output = str_replace($key, $tags[$key], $content);
                    $output = str_replace($wraps, "", $output);
                }

                return $output;

            },
            $content
        );
    }

    /**
     * Search and replace required tags.
     * Throw exception if tag is not in array $tags.
     *
     * @param array $tags
     * @param       $content
     *
     * @throws \Exception
     *
     * @return mixed
     */
    protected function parseRequiredTags(array $tags, $content)
    {
        return preg_replace_callback(
            '/[{]{2}([A-Za-z-]+)[}]{2}/',
            function ($input) use ($tags) {
                $key = $input[1];
                if ( ! key_exists($key, $tags)) {
                    throw new \Exception($key . ' is not a valid tag in template file.');
                }

                return $tags[$key];
            },
            $content
        );
    }
}