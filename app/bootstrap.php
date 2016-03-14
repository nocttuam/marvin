<?php
$marvinPath = dirname(__DIR__);

if (file_exists($marvinPath . '/vendor/autoload.php')) {
    require $marvinPath . '/vendor/autoload.php';
}

$configRepository              = new Marvin\Config\Repository();
$configRepository['apache']    = include $marvinPath . '/app/config/apache.conf.php';
$configRepository['app']       = include $marvinPath . '/app/config/app.conf.php';
$configRepository['default']   = include $marvinPath . '/app/config/default.conf.php';
$configRepository['hostsfile'] = include $marvinPath . '/app/config/hostsfile.conf.php';

$container['Filesystem']      = new \Marvin\Filesystem\Filesystem();
$container['Template']        = new \Marvin\Filesystem\Template($configRepository, $container['Filesystem']);
$container['ApacheManager']    = new \Marvin\Hosts\ApacheManager($configRepository, $container['Template']);
$container['EtcHostsManager'] = new \Marvin\Hosts\EtcHostsManager($container['Filesystem'], $configRepository);
$container['Execute']   = new \Marvin\Shell\Apache\Execute($configRepository);

$container['ConfigRepository'] = $configRepository;