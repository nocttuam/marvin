<?php
namespace Marvin\Commands;

use Marvin\Hosts\Apache;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateApacheCommand extends Command
{
    /**
     * @var Apache
     */
    protected $apacheManager;

    public function __construct(Apache $apacheManager, $name = null)
    {
        parent::__construct($name);

        $this->apacheManager = $apacheManager;
    }

    protected function configure()
    {
        $this->setName('create:apache')
             ->setDescription('This command is used to create virtual host Apache')
             ->addArgument(
                 'name',
                 InputArgument::REQUIRED,
                 'What is server name'
             )
             ->addArgument(
                 'document-root',
                 InputArgument::REQUIRED,
                 'Path for the directory containing visible document in web'
             )
             ->addOption(
                 'ip',
                 'i',
                 InputOption::VALUE_OPTIONAL,
                 'IP to access virtual host'
             )
             ->addOption(
                 'port',
                 'p',
                 InputOption::VALUE_OPTIONAL,
                 'Port to access virtual host'
             )
             ->addOption(
                 'log-path',
                 'l',
                 InputOption::VALUE_OPTIONAL,
                 'Path to server logs'
             )
             ->addOption(
                 'server-admin',
                 'a',
                 InputOption::VALUE_OPTIONAL,
                 'Admin e-mail address'
             );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name         = $input->getArgument('name');
        $documentRoot = $input->getArgument('document-root');
        $ip           = $input->getOption('ip');
        $port         = $input->getOption('port');
        $logPath      = $input->getOption('log-path');
        $serverAdmin  = $input->getOption('server-admin');

        $this->apacheManager->serverName($name)
                            ->documentRoot($documentRoot);

        $this->setIp($ip, $output)
             ->setPort($port)
             ->setLogPath($logPath)
             ->setServerAdmin($serverAdmin);

        $this->apacheManager->createConfigFile();
        $output->writeln($this->apacheManager->enableApacheSite());
        $output->writeln($this->apacheManager->restartServer());
    }

    protected function setIp($ip, $output)
    {
        if ($ip) {
            try {
                $this->apacheManager->ip($ip);
            } catch (\Exception $e) {
                $output->writeln($e->getMessage());
                exit();
            }
        }

        return $this;
    }

    protected function setPort($port)
    {
        if ($port) {
            $this->apacheManager->port($port);
        }

        return $this;
    }

    protected function setLogPath($logPath)
    {
        if ($logPath) {
            $this->apacheManager->logPath($logPath);
        }

        return $this;
    }

    protected function setServerAdmin($serverAdmin)
    {
        if ($serverAdmin) {
            $this->apacheManager->serverAdmin($serverAdmin);
        }

        return $this;
    }
}