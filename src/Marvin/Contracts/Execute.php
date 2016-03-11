<?php
namespace Marvin\Contracts;

interface Execute
{
    /**
     * Set the Virtual Host Manager
     *
     * @param HostManager $hostManager
     */
    public function setHostManager(HostManager $hostManager);

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