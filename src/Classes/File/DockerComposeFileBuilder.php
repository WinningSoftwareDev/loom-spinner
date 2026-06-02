<?php

declare(strict_types=1);

namespace Loom\Spinner\Classes\File;

use Loom\Spinner\Classes\Config\Config;
use Symfony\Component\Console\Input\InputInterface;

class DockerComposeFileBuilder extends AbstractFileBuilder
{
    /**
     * @param Config $config
     * @param array<string, int> $ports
     *
     * @throws \Exception
     */
    public function __construct(Config $config, private readonly array $ports)
    {
        parent::__construct($config->getDataDirectory() . '/docker-compose.yaml', $config);
    }

    /**
     * @throws \Exception
     */
    public function build(InputInterface $input): self
    {
        if (!$content = $this->config->getConfigFileContents('php.yaml')) {
            throw new \RuntimeException('Could not locate default PHP configuration file.');
        }

        $this->content = $content;
        $this->content = str_replace('${VITE_PORT}', (string) $this->ports['vite'], $this->content);

        if ($this->config->isServerEnabled($input)) {
            $this->addNginxConfig();
        }

        if ($this->config->isMailcatcherEnabled($input)) {
            $this->addMailcatcherConfig();
        }

        $volumes = '';

        if ($this->config->isDatabaseEnabled($input) && $driver = $this->config->getDatabaseDriver($input)) {
            $databaseDriver = strtolower($driver);

            if (in_array($databaseDriver, ['sqlite3', 'sqlite'])) {
                $this->addSqliteDatabaseConfig();
            }

            if ($databaseDriver === 'mysql') {
                $this->addMysqlDatabaseConfig();
            }

            $volumes .= "\n mysql_data:";
        }

        if ($this->config->isRabbitMQEnabled($input)) {
            $this->addRabbitMQConfig();
            $volumes .= "\n rabbitmq_data:";
        }

        if (!empty($volumes)) {
            $this->content .= "\n\nvolumes:" . $volumes . "\n\n";
        }

        $this->addNetworks();

        return $this;
    }

    /**
     * @throws \Exception
     */
    private function addNginxConfig(): void
    {
        if (!$nginxContent = $this->config->getConfigFileContents('nginx.yaml')) {
            throw new \RuntimeException('Could not locate the default Nginx configuration file.');
        }

        $this->content .= str_replace(
            'services:',
            '',
            $nginxContent
        );
        $this->content = str_replace(
            './nginx/conf.d',
            $this->config->getDataDirectory() . '/nginx/conf.d',
            $this->content
        );
    }

    /**
     * @throws \Exception
     */
    private function addSqliteDatabaseConfig(): void
    {
        if (!$sqlLiteConfig = $this->config->getConfigFileContents('sqlite.yaml')) {
            throw new \RuntimeException('Could not locate the default SQLite configuration file.');
        }

        $sqlLiteConfig = str_replace('volumes:', '', $sqlLiteConfig);
        $this->content .= $sqlLiteConfig;
    }

    /**
     * @throws \Exception
     */
    private function addMysqlDatabaseConfig(): void
    {
        if (!$mysqlConfig = $this->config->getConfigFileContents('mysql.yaml')) {
            throw new \RuntimeException('Could not locate the default MySQL configuration file.');
        }

        $rootPassword = $this->config->getEnvironmentOption('database', 'rootPassword');

        if (!is_string($rootPassword) || $rootPassword === '') {
            throw new \Exception('The root database password is invalid.');
        }

        $mysqlConfig = str_replace('services:', '', $mysqlConfig);
        $mysqlConfig = str_replace('${ROOT_PASSWORD}', $rootPassword, $mysqlConfig);
        $mysqlConfig = str_replace('${DATABASE_PORT}', (string) $this->ports['database'], $mysqlConfig);
        $this->content .= $mysqlConfig;
    }

    /**
     * @throws \Exception
     */
    private function addMailcatcherConfig(): void
    {
        if (!$mailcatcherConfig = $this->config->getConfigFileContents('mailcatcher.yaml')) {
            throw new \Exception('Could not locate the default Mailcatcher configuration file.');
        }

        $mailcatcherConfig = str_replace('services:', '', $mailcatcherConfig);
        $mailcatcherConfig = str_replace('${MAILCATCHER_SMTP_PORT}', (string) $this->ports['mailcatcher_smtp'], $mailcatcherConfig);
        $mailcatcherConfig = str_replace('${MAILCATCHER_WEB_PORT}', (string) $this->ports['mailcatcher_web'], $mailcatcherConfig);
        $this->content .= $mailcatcherConfig;
    }

    /**
     * @throws \Exception
     */
    private function addRabbitMQConfig(): void
    {
        if (!$rabbitmqConfig = $this->config->getConfigFileContents('rabbitmq.yaml')) {
            throw new \Exception('Could not locate the default RabbitMQ configuration file.');
        }

        $rabbitmqConfig = str_replace('services:', '', $rabbitmqConfig);
        $rabbitmqConfig = str_replace('${RABBITMQ_PORT}', (string) $this->ports['rabbitmq'], $rabbitmqConfig);
        $rabbitmqConfig = str_replace('${RABBITMQ_MANAGEMENT_PORT}', (string) $this->ports['rabbitmq_management'], $rabbitmqConfig);
        $this->content .= $rabbitmqConfig;
    }

    /**
     * @throws \Exception
     */
    private function addNetworks(): void
    {
        if (!$networksConfig = $this->config->getConfigFileContents('network.yaml')) {
            throw new \Exception('Could not locate the default network configuration file.');
        }

        $this->addNewLine();
        $this->content .= $networksConfig;
    }
}
