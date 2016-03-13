<?php
namespace Marvin\Filesystem;

use Marvin\Contracts\HostManager;
use Marvin\Config\Repository as ConfigRepository;

class Template
{
    /**
     * @var HostManager
     */
    protected $host;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var \Marvin\Contracts\HostManager
     */
    protected $hostManager;

    /**
     * Template directory
     *
     * @var string
     */
    protected $templateDir;

    /**
     * Template constructor.
     *
     * @param ConfigRepository $configRepository
     * @param Filesystem       $filesystem
     */
    public function __construct(ConfigRepository $configRepository, Filesystem $filesystem)
    {
        $this->filesystem       = $filesystem;
        $this->configRepository = $configRepository;
    }


    /**
     * Build new content replacing tags in template content
     * Return final content
     *
     * @param HostManager $hostManager
     *
     * @return string
     */
    public function compile(HostManager $hostManager)
    {
        $this->hostManager = $hostManager;
        $this->templateDir = $this->configRepository->get('app.templates-dir');

        $content = $this->getTemplateContent($this->hostManager->get('host'));
        $content = $this->parseOptionalTags($content);
        $content = $this->parseRequiredTags($content);
        $content = $this->addID($content);

        $temporaryDir = $this->configRepository->get('app.temporary-dir');
        $dest         = $temporaryDir . DIRECTORY_SEPARATOR . $this->hostManager->get('file-name');

        $result = $this->filesystem->put($dest, $content);

        return $result;
    }

    /**
     * Get content in template file
     * Using name of the host search template file.
     *
     * @param $hostName
     *
     * @return string
     */
    protected function getTemplateContent($hostName)
    {
        $files   = $this->filesystem->files($this->templateDir);
        $content = '';
        array_walk($files, function ($file) use ($hostName, &$content) {
            if (preg_match('/' . $hostName . '/', $file)) {
                $content = $this->filesystem->get($file);
            }
        });

        return $content;
    }


    /**
     * Search and replace optional tags.
     * Delete wrap content if optional tag is not exist.
     *
     * @param $content
     *
     * @return mixed
     */
    protected function parseOptionalTags($content)
    {
        $wraps = ['{!!', '!!}', '{{', '}}'];

        return preg_replace_callback(
            '/[{]{1}[!]{2}(.*)({{)(.+)(}})(.*)[!]{2}[}]{1}/',
            function ($input) use ($wraps) {
                $key     = $input[3];
                $content = $input[0];
                $value   = $this->hostManager->get($key);
                $output  = '';
                if ( ! is_array($value)) {
                    $output = str_replace($key, $value, $content);
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
     * @param $content
     *
     * @throws \Exception
     *
     * @return mixed
     */
    protected function parseRequiredTags($content)
    {
        return preg_replace_callback(
            '/[{]{2}([A-Za-z-]+)[}]{2}/',
            function ($input) {
                $key = $input[1];

                $value = $this->hostManager->get($key);
                if (is_array($value)) {
                    throw new \Exception($key . ' is not a valid tag in template file.');
                }

                return $value;
            },
            $content
        );
    }

    protected function addID($content)
    {
        $id        = $this->hostManager->get('id');
        $finalLine = '# Created by Marvin // ID: ' . $id;

        $content .= PHP_EOL . PHP_EOL;
        $content .= $finalLine . PHP_EOL;

        return $content;
    }
}