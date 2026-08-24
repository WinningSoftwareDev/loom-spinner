<?php

declare(strict_types=1);

namespace Loom\Spinner\Classes\Config;

use Loom\Spinner\Classes\OS\System;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Yaml\Yaml;

class Config
{
    private string $configDirectory;
    private string $dataDirectory;

    public function __construct(string $projectName = '', private readonly ?string $projectWorkPath = null)
    {
        $this->configDirectory = sprintf('%s/config', dirname(__DIR__, 3));
        $this->dataDirectory = sprintf('%s/.spinner/environments/%s', $this->getHomeDirectory(), $projectName);
    }

    public function getProxyDirectory(): string
    {
        return sprintf('%s/.spinner/proxy', $this->getHomeDirectory());
    }

    public function getManagedEnvironmentsDirectory(): string
    {
        return sprintf('%s/.spinner/environments', $this->getHomeDirectory());
    }

    public function getDataDirectory(): string
    {
        return $this->dataDirectory;
    }

    public function getConfigFilePath(string $fileName): string
    {
        return sprintf('%s/%s', $this->configDirectory, $fileName);
    }

    public function getConfigFileContents(string $fileName): string|null
    {
        if (file_exists($path = sprintf('%s/%s', $this->configDirectory, $fileName))) {
            return file_get_contents($path) ?: null;
        }

        return null;
    }

    public function projectDataExists(string $projectName): bool
    {
        return file_exists($this->dataDirectory . '/environments/' . $projectName);
    }

    public function getPhpVersion(InputInterface $input): float
    {
        $phpVersion = $input->getOption('php');

        if (is_string($phpVersion) && is_numeric($phpVersion)) {
            return (float) $phpVersion;
        }

        $defaultPhpVersion = $this->getEnvironmentOption('php', 'version');

        return is_string($defaultPhpVersion) ? (float) $defaultPhpVersion : 8.4;
    }

    public function isServerEnabled(InputInterface $input): bool
    {
        if ($input->getOption('disable-server')) {
            return false;
        }

        return (bool) $this->getEnvironmentOption('server', 'enabled');
    }

    /**
     * @throws \Exception
     */
    public function isXDebugEnabled(InputInterface $input): bool
    {
        if ($input->getOption('disable-xdebug')) {
            return false;
        }

        return (bool) $this->getEnvironmentOption('php', 'xdebug');
    }

    public function isDatabaseEnabled(InputInterface $input): bool
    {
        if ($input->getOption('disable-database')) {
            return false;
        }

        return (bool) $this->getEnvironmentOption('database', 'enabled');
    }

    public function isMailcatcherEnabled(InputInterface $input): bool
    {
        if ($input->getOption('mailcatcher')) {
            return true;
        }

        return (bool) $this->getEnvironmentOption('mailcatcher', 'enabled');
    }

    public function isRabbitMQEnabled(InputInterface $input): bool
    {
        if ($input->getOption('rabbitmq')) {
            return true;
        }

        return (bool) $this->getEnvironmentOption('rabbitmq', 'enabled');
    }

    public function isRedisEnabled(InputInterface $input): bool
    {
        if ($input->getOption('redis')) {
            return true;
        }

        return (bool) $this->getEnvironmentOption('redis', 'enabled');
    }

    public function getRabbitMQManagementPort(InputInterface $input): ?int
    {
        $managementPort = $this->getEnvironmentOption('rabbitmq', 'managementPort');

        if (is_numeric($managementPort)) {
            return (int) $managementPort;
        }

        return null;
    }

    public function getDatabaseDriver(InputInterface $input): ?string
    {
        if (is_string($input->getOption('database'))) {
            return $input->getOption('database');
        }

        $defaultDatabaseDriver = $this->getEnvironmentOption('database', 'driver');

        if (is_string($defaultDatabaseDriver)) {
            return $defaultDatabaseDriver;
        }

        return null;
    }

    public function getNodeVersion(InputInterface $input): ?int
    {
        if (is_numeric($input->getOption('node'))) {
            return (int) $input->getOption('node');
        }

        if (is_numeric($this->getEnvironmentOption('node', 'version'))) {
            return (int) $this->getEnvironmentOption('node', 'version');
        }

        return null;
    }

    public function getEnvironmentOption(string $service, string $option): mixed
    {
        $projectCustomConfig = $this->getProjectCustomConfig();

        if ($projectCustomConfig) {
            return $projectCustomConfig[$service][$option] ?? $this->getDefaultConfig()[$service][$option] ?? null;
        }

        return $this->getDefaultConfig()[$service][$option] ?? null;
    }

    /**
     * @return array<string, array<string, bool|float|int|string>>|null
     */
    public function getProjectCustomConfig(): ?array
    {
        if ($this->projectWorkPath && file_exists($configFilePath = $this->getConfigYamlPath())) {
            return $this->getParsedEnvironment($configFilePath);
        }

        return null;
    }

    /**
     * @return array<string, array<string, bool|float|int|string>>|null
     */
    protected function getDefaultConfig(): ?array
    {
        return $this->getParsedEnvironment($this->getConfigYamlPath());
    }

    /**
     * @return array<string, array<string, bool|float|int|string>>|null
     */
    private function getParsedEnvironment(string $path): ?array
    {
        $parsedFile = Yaml::parseFile($path);

        if (!is_array($parsedFile) || !isset($parsedFile['options']) || !is_array($parsedFile['options']) || !isset($parsedFile['options']['environment']) || !is_array($parsedFile['options']['environment'])) {
            return null;
        }

        /**
         * @var array<string, array<string, bool|float|int|string>> $environment
         */
        $environment = $parsedFile['options']['environment'];

        return $environment;
    }

    private function getHomeDirectory(): string
    {
        if ($home = getenv('HOME')) {
            return $home;
        }

        if ((new System())->isWindows()) {
            return getenv('USERPROFILE') ?: getenv('HOMEDRIVE') . getenv('HOMEPATH');
        }

        throw new \RuntimeException('Unable to determine home directory.');
    }

    private function getConfigYamlPath(): string
    {
        return sprintf('%s/spinner.yaml', $this->configDirectory);
    }
}
