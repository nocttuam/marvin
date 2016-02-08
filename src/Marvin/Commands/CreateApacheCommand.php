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
                 'Host name used to access'
             )
             ->addArgument(
                 'document-root',
                 InputArgument::REQUIRED,
                 'Path for the directory containing visible document in web'
             )
             ->addOption(
                 'ip',
                 'i',
                 InputOption::VALUE_REQUIRED,
                 'IP to access virtual host'
             )
             ->addOption(
                 'port',
                 'p',
                 InputOption::VALUE_REQUIRED,
                 'Port to access virtual host'
             )
             ->addOption(
                 'log-path',
                 'l',
                 InputOption::VALUE_REQUIRED,
                 'Path to server logs'
             )
             ->addOption(
                 'server-admin',
                 'a',
                 InputOption::VALUE_REQUIRED,
                 'Admin e-mail address'
             )
             ->addOption(
                 'server-alias',
                 'A',
                 InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                 'Alternate name for the host'
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
        $serverAlias  = $input->getOption('server-alias');

        $this->apacheManager->set('server-name', $name)
                            ->set('document-root', $documentRoot);

        $this->setIp($ip, $output)
             ->setPort($port)
             ->setLogPath($logPath)
             ->setServerAlias($serverAlias)
             ->setServerAdmin($serverAdmin);

        $this->apacheManager->create($name);
        $output->writeln('Apache Virtual Host Created Success!');
    }

    protected function setIp($ip, $output)
    {
        if ($ip) {
            try {
                $this->apacheManager->set( 'ip', $ip);
            } catch (\InvalidArgumentException $e) {
                $output->writeln($e->getMessage());
                exit();
            }
        }

        return $this;
    }

    protected function setPort($port)
    {
        if ($port) {
            $this->apacheManager->set('port', $port);
        }

        return $this;
    }

    protected function setServerAlias($serverAlias)
    {
        if ( ! is_array($serverAlias)) {
            $serverAlias = [$serverAlias];
        }
        $this->apacheManager->set('server-alias', $serverAlias);

        return $this;
    }

    protected function setLogPath($logPath)
    {
        if ($logPath) {
            $this->apacheManager->set('log-path', $logPath);
        }

        return $this;
    }

    protected function setServerAdmin($serverAdmin)
    {
        if ($serverAdmin) {
            $this->apacheManager->set('server-admin', $serverAdmin);
        }

        return $this;
    }
}