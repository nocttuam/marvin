<?php
namespace Marvin\Contracts;

interface Host
{

    /**
     * Set IP used to access virtual host
     * If IP is invalid throw exception
     *
     * @param $ip
     */
    public function setIP($ip);

    /**
     * Set Port number used to access virtual host
     *
     * @param $port
     */
    public function setPort($port);

    /**
     * Name used to identify the virtual host
     *
     * @param $serverName
     */
    public function setServerName($serverName);

    /**
     * Alternate names for a host
     *
     * @param array $serverAlias
     */
    public function setServerAlias(array $serverAlias);

    /**
     * Directory containing main documents tree visible in web
     *
     * @param $path
     */
    public function setDocumentRoot($path);

    /**
     * Location where will log errors
     *
     * @param $path
     */
    public function setLogDir($path);

    /**
     * Set name used to the configuration file
     *
     * @param $name
     */
    public function setFileName($name);

    /**
     * Get the specified configuration value of the virtual host object
     *
     * @param string $key
     */
    public function get($key);

    /**
     * Create a temporary file containing virtual host configurations
     */
    public function create();
}