<?php
namespace Marvin\Contracts;

interface Execute
{
    /**
     * Set the Virtual Host Manager
     *
     * @param Host $host
     */
    public function setHost(Host $host);

    /**
     * Move configuration file to Apache configurations directory
     */
    public function moveConfig();

    /**
     * Run command to enable new host
     */
    public function enable();

    /**
     * Restart Apache service
     */
    public function restart();
}