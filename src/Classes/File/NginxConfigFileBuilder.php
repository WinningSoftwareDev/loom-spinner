<?php

declare(strict_types=1);

namespace Loom\Spinner\Classes\File;

use Loom\Spinner\Classes\Config\Config;
use Symfony\Component\Console\Input\InputInterface;

class NginxConfigFileBuilder extends AbstractFileBuilder
{
    public function __construct(Config $config, private readonly string $projectName, private readonly int $vitePort)
    {
        parent::__construct(
            sprintf('%s/conf.d/%s.conf', $config->getProxyDirectory(), $this->projectName),
            $config
        );
    }

    /**
     * @param InputInterface $input
     *
     * @throws \Exception
     *
     * @return AbstractFileBuilder
     */
    public function build(InputInterface $input): AbstractFileBuilder
    {
        if (!$content = $this->config->getConfigFileContents('proxy/conf.d/default.conf')) {
            throw new \RuntimeException('Could not locate the default configuration file');
        }

        $this->content = str_replace('{{PROJECT_NAME}}', $this->projectName, $content);
        $this->content = str_replace('3000', (string) $this->vitePort, $this->content);

        return $this;
    }
}
