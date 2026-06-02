<?php

declare(strict_types=1);

namespace Loom\Spinner\Command;

use Loom\Spinner\Classes\File\DockerComposeFileBuilder;
use Loom\Spinner\Classes\File\NginxConfigFileBuilder;
use Loom\Spinner\Classes\File\NginxDockerFileBuilder;
use Loom\Spinner\Classes\File\PHPDockerFileBuilder;
use Loom\Spinner\Classes\File\ProjectNginxConfigFileBuilder;
use Loom\Spinner\Classes\File\ProxyFileBuilder;
use Loom\Spinner\Classes\OS\PortGenerator;
use Loom\Spinner\Classes\ReverseProxyManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'spin:up', description: 'Spin up a new named development environment')]
class SpinCommand extends AbstractSpinnerCommand
{
    /**
     * @var array<string, int>
     */
    private array $ports;

    private string $projectWorkPath = '';

    public function __construct()
    {
        $portGenerator = new PortGenerator();
        $this->ports = [
            'php' => $portGenerator->generateRandomPort(),
            'vite' => $portGenerator->generateRandomPort(),
            'server' => $portGenerator->generateRandomPort(),
            'database' => $portGenerator->generateRandomPort(),
            'mailcatcher_smtp' => $portGenerator->generateRandomPort(),
            'mailcatcher_web' => $portGenerator->generateRandomPort(),
            'rabbitmq' => $portGenerator->generateRandomPort(),
            'rabbitmq_management' => $portGenerator->generateRandomPort(),
        ];

        parent::__construct();
    }

    /**
     * @throws \Exception
     */
    protected function configure(): void
    {
        $this
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'The name for your Docker container.'
            )
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'The absolute path to your projects root directory.'
            )
            ->addOption(
                'php',
                null,
                InputOption::VALUE_OPTIONAL,
                'The PHP version to use (e.g., 8.0).'
            )
            ->addOption(
                'disable-server',
                null,
                InputOption::VALUE_NONE,
                'Set this flag to not include a web server for your environment.'
            )
            ->addOption(
                'disable-xdebug',
                null,
                InputOption::VALUE_NONE,
                'Set this flag to disable XDebug for your environment.'
            )
            ->addOption(
                'disable-database',
                null,
                InputOption::VALUE_NONE,
                'Set this flag to not include a database for your environment.'
            )
            ->addOption('database', null, InputOption::VALUE_REQUIRED, 'The type of database to use (e.g., mysql, sqlite).', null, ['mysql', 'sqlite'])
            ->addOption('node', null, InputOption::VALUE_OPTIONAL, 'The Node.js version to use (e.g. 20).')
            ->addOption('mailcatcher', null, InputOption::VALUE_NONE, 'Set this flag to include Mailcatcher for your environment.')
            ->addOption('rabbitmq', null, InputOption::VALUE_NONE, 'Set this flag to include RabbitMQ for your environment.');
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (parent::execute($input, $output)) {
            return Command::FAILURE;
        }

        $projectPath = $input->getArgument('path');
        $projectName = $input->getArgument('name');
        $currentDirectory = getcwd();

        if (!is_string($projectPath) || $currentDirectory === false) {
            $this->style->error('The provided project path does not exist.');

            return Command::FAILURE;
        }

        if (!is_string($projectName) || $projectName === '') {
            $this->style->error('The provided project name is invalid.');

            return Command::FAILURE;
        }

        $this->projectWorkPath = $input->getArgument('path') === '.'
            ? $currentDirectory
            : $projectPath;

        if (!file_exists($this->projectWorkPath)) {
            $this->style->error('The provided project path does not exist.');

            return Command::FAILURE;
        }

        $this->setConfig($input, $this->projectWorkPath);

        if (file_exists($this->config->getDataDirectory())) {
            $this->style->error('A project with the same name already exists.');

            return Command::FAILURE;
        }

        if ($this->config->isServerEnabled($input) && !file_exists($this->config->getProxyDirectory())) {
            $this->style->info('Building reverse proxy...');
            $this->createProxyDirectory();
            $this->buildProxyDockerComposeFile($input);
            $this->style->success('Proxy data created.');
            exec(sprintf('docker compose -f %s/docker-compose.yaml up -d', $this->config->getProxyDirectory()));
            $this->style->success('Started reverse proxy container');
        }

        $this->style->info('Spinning up a new development environment...');
        $this->style->text('Creating project data...');

        try {
            $this->createProjectData($input, $projectName);
        } catch (\Exception $exception) {
            $this->style->error('Failed to create project data: '. $exception->getMessage());

            return Command::FAILURE;
        }

        $this->style->success('Project data created.');
        $this->style->text('Building Docker images...');

        passthru($this->buildDockerComposeCommand(sprintf('-p %s up', $projectName)));

        if ($this->config->isServerEnabled($input)) {
            (new ReverseProxyManager($this->style))->startProxyContainerIfNotRunning();
            exec('command -v mkcert', $output, $code);
            if ($code === 0) {
                $domain = $projectName . '.app';
                $certDir = $this->config->getProxyDirectory() . '/certs';

                if (!file_exists("$certDir/$domain.crt")) {
                    exec(sprintf(
                        'mkcert -key-file %s/%s.key -cert-file %s/%s.crt %s',
                        $certDir,
                        $projectName,
                        $certDir,
                        $projectName,
                        $domain
                    ));
                }
            }
            exec('docker exec loom-spinner-reverse-proxy nginx -s reload > /dev/null 2>&1');
        }

        $this->style->success('Environment built.');

        if ($this->config->isServerEnabled($input)) {
            $this->style->text('Add the following line to /etc/hosts to enable your application:');
            $this->style->text(sprintf('127.0.0.1 %s.app', $projectName));
            $this->style->newLine();
            $this->style->text(sprintf(
                'Or, in a terminal with elevated (sudo) privileges, run: loom env:hosts:add %s',
                $projectName
            ));
        }

        exec(sprintf('docker exec -it %s-php mkdir -p /etc/nginx && docker cp loom-spinner-reverse-proxy:/etc/nginx/certs/ /tmp/certs && docker cp /tmp/certs/ %s-php:/etc/nginx/certs/', $projectName, $projectName));

        return Command::SUCCESS;
    }

    /**
     * @throws \Exception
     */
    private function createProjectData(InputInterface $input, string $projectName): void
    {
        $this->createProjectDataDirectory($input);
        $this->createEnvironmentFile($input, $projectName);
        $this->addToNetwork($input, $projectName);
        $this->createProjectNginxConfig($input, $projectName);
        $this->buildDockerComposeFile($input);
        $this->buildDockerfiles($input);
    }

    /**
     * @throws \Exception
     */
    private function createProjectDataDirectory(InputInterface $input): void
    {
        if (
            !mkdir(
                directory: $concurrentDirectory = $this->config->getDataDirectory(),
                recursive: true
            ) && !is_dir($concurrentDirectory)
        ) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
        $this->createProjectDataSubDirectory('nginx/conf.d');
    }

    /**
     * @param InputInterface $input
     * @param string $projectName
     *
     * @throws \Exception
     *
     * @return void
     */
    private function addToNetwork(InputInterface $input, string $projectName): void
    {
        (new NginxConfigFileBuilder($this->config, $projectName, $this->ports['vite']))
            ->build($input)
            ->save();
    }

    /**
     * @throws \Exception
     */
    private function createEnvironmentFile(InputInterface $input, string $projectName): void
    {
        if (!$envTemplateContents = $this->config->getConfigFileContents('.template.env')) {
            throw new \Exception('Could not locate the default .env template.');
        }

        $rootDatabasePassword = $this->config->getEnvironmentOption('database', 'rootPassword');

        if (!is_string($rootDatabasePassword) || $rootDatabasePassword === '') {
            throw new \Exception('The root database password is invalid.');
        }

        file_put_contents(
            $this->config->getDataDirectory() . '/.env',
            sprintf(
                $envTemplateContents,
                $this->projectWorkPath,
                $projectName,
                $this->config->getPhpVersion($input),
                $this->ports['php'],
                $this->ports['vite'],
                $this->ports['server'],
                $this->ports['database'],
                $rootDatabasePassword,
                $this->ports['mailcatcher_smtp'],
                $this->ports['mailcatcher_web'],
                $this->ports['rabbitmq'],
                $this->ports['rabbitmq_management']
            )
        );
    }

    /**
     * @param InputInterface $input
     * @param string $projectName
     *
     * @throws \Exception
     *
     * @return void
     */
    private function createProjectNginxConfig(InputInterface $input, string $projectName): void
    {
        (new ProjectNginxConfigFileBuilder($this->config, $projectName))
            ->build($input)
            ->save();
    }

    private function createProxyDirectory(): void
    {
        if (
            !mkdir(
                directory: $concurrentDirectory = $this->config->getProxyDirectory() . '/conf.d',
                recursive: true
            ) && !is_dir($concurrentDirectory)
        ) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
        if (
            !mkdir(
                directory: $concurrentDirectory = $this->config->getProxyDirectory() . '/certs',
                recursive: true
            ) && !is_dir($concurrentDirectory)
        ) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
    }

    /**
     * @param InputInterface $input
     *
     * @throws \Exception
     *
     * @return void
     */
    private function buildProxyDockerComposeFile(InputInterface $input): void
    {
        (new ProxyFileBuilder($this->config->getProxyDirectory() . '/docker-compose.yaml', $this->config))
            ->build($input)
            ->save();
    }

    /**
     * @throws \Exception
     */
    private function buildDockerComposeFile(InputInterface $input): void
    {
        $this->createProjectDataSubDirectory('php-fpm');

        (new DockerComposeFileBuilder($this->config, $this->ports))->build($input)->save();
    }

    /**
     * @throws \Exception
     */
    private function buildDockerfiles(InputInterface $input): void
    {
        if ($this->config->isServerEnabled($input)) {
            (new NginxDockerFileBuilder($this->config))->build($input)->save();
        }

        (new PHPDockerFileBuilder($this->config))->build($input)->save();
    }

    private function createProjectDataSubDirectory(string $directory): void
    {
        if (!file_exists($this->config->getDataDirectory() . '/' . $directory)) {
            mkdir($this->config->getDataDirectory() . '/' . $directory, 0777, true);
        }
    }
}
