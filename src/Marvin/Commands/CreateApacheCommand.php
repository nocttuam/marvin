<?php
namespace Marvin\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateApacheCommand extends Command
{

    protected $container;

    /**
     * CreateApacheCommand constructor.
     *
     * @param array       $container
     * @param null|string $name
     */
    public function __construct(array $container, $name = null)
    {
        parent::__construct($name);
        $this->container = $container;
    }


    /**
     * Configure command options
     */
    protected function configure()
    {
        $this->setName('apache:create')
             ->setDescription('This command create a Apache virtual host')
             ->addArgument(
                 'server-name',
                 InputArgument::REQUIRED,
                 'Your server'
             )
             ->addArgument(
                 'document-root',
                 InputArgument::REQUIRED,
                 'Path to you document root'
             )
             ->addOption(
                 'ip',
                 'i',
                 InputOption::VALUE_REQUIRED,
                 'Ip used to access your virtual host'
             )
             ->addOption(
                 'port',
                 'p',
                 InputOption::VALUE_REQUIRED,
                 'Port used to access your virtual host'
             )
             ->addOption(
                 'server-admin',
                 null,
                 InputOption::VALUE_REQUIRED,
                 'Email address that the server includes in error messages'
             )
             ->addOption(
                 'alias',
                 null,
                 InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                 'Alternate names for a host'
             )
             ->addOption(
                 'log-dir',
                 null,
                 InputOption::VALUE_REQUIRED,
                 'Location where the server will log errors'

             );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {

    }


    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->setParameters($input, $output);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->container['ApacheManager']->create()) {
            $output->writeln('The configurations file of the virtual host created');
        }

        if ($this->container['EtcHostsManager']->addHost($this->container['ApacheManager'])) {
            $output->writeln('The new hosts file created');
        }

        $tmpDir = $this->container['ConfigRepository']->get('app.temporary-dir');

        $hostsFile = $tmpDir . DIRECTORY_SEPARATOR . 'hosts';

        if ($this->container['Filesystem']->exists($hostsFile)) {
            $target = $this->container['ConfigRepository']->get('hostsfile.path');
            $this->container['Filesystem']->sysMove($hostsFile, $target);
        }

        $apacheFile = $tmpDir . DIRECTORY_SEPARATOR . $this->container['ApacheManager']->get('file-name');

        if ($this->container['Filesystem']->exists($apacheFile)) {
            $target = $this->container['ConfigRepository']->get('apache.config-sys-dir') . DIRECTORY_SEPARATOR . 'sites-available';
            $this->container['Filesystem']->sysMove($apacheFile, $target);
        }

        $execute = $this->container['ApacheManager']->execute($this->container['Execute']);
        $execute->enable();
        $execute->restart();

        $output->writeln('The new host is finished');

    }

    protected function setParameters(InputInterface $input, OutputInterface $output)
    {
        $this->container['ApacheManager']->setServerName($input->getArgument('server-name'));
        $this->container['ApacheManager']->setDocumentRoot($input->getArgument('document-root'));
        $this->container['ApacheManager']->setFileName($input->getArgument('server-name'));

        if ($input->getOption('ip')) {
            $this->setIP($input->getOption('ip'), $output);
        }

        if ($input->getOption('port')) {
            $this->container['ApacheManager']->setPort($input->getOption('port'));
        }

        if ($input->getOption('server-admin')) {
            $this->container['ApacheManager']->setServerAdmin($input->getOption('server-admin'));
        }

        $alias = $input->getOption('alias');
        if ($input->getOption('alias')) {
            if ( ! is_array($alias)) {
                $alias = explode(' ', $alias);
            }
            $this->container['ApacheManager']->setServerAlias($alias);
        }

        if ($input->getOption('log-dir')) {
            $this->container['ApacheManager']->setLogDir($input->getOption('log-dir'));
        }

    }

    protected function setIP($ip, OutputInterface $output)
    {
        try {
            $this->container['ApacheManager']->setIP($ip);
        } catch (\Exception $e) {
            $output->writeln('Use a valid IP');
        }
    }
}