<?php
namespace Marvin\Shell;

interface IExecute
{
    /**
     * Move configuration file to Apache configurations directory
     *
     * @param $file
     *
     * @return mixed
     */
    public function moveConfig($file);

    /**
     * Run command to enable new host
     *
     * @param string $file
     *
     * @return mixed
     */
    public function enable($file);

    /**
     * Restart Apache service
     *
     * @return mixed
     */
    public function restart();
}