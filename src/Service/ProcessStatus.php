<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Service;

/**
 * @phpstan-type Config array{
 *    ini: array<string>,
 *    fpm: array{
 *        configDirs: array<string>,
 *        status: array<string>,
 *    },
 *    opcache: array<string>
 *  }
 */
class ProcessStatus
{
    /**
     * @var Config
     */
    private readonly array $config;

    /**
     * @throws \JsonException
     */
    public function __construct(
        string $config = __DIR__ . '/processStatus.json',
        private readonly string $sapi = PHP_SAPI,
        private readonly Platform $platform = new Platform()
    ) {
        /** @var Config $data */
        $data = json_decode(
            file_get_contents($config) ?: '{}',
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $this->config = $data;
    }

    /**
     * @return array<string,mixed>
     */
    public function getStatus(): array
    {
        $status = [
            'user' => $this->platform->getUser(),
            'group' => $this->platform->getGroup(),
            'php' => [
                'version' => $this->platform->getVersion(),
                'ini' => $this->getIniSettings($this->config['ini'] ?? [])
            ]
        ];

        if ($this->sapi === 'fpm-fcgi') {
            $fpm = [];
            $fpm['config'] = $this->getFpmConfig(
                $this->config['fpm']['configDirs'] ?? []
            );
            $fpm['status'] = $this->getFpmPoolStatus(
                $this->config['fpm']['status'] ?? []
            );
            $status['php']['fpm'] = $fpm;
            $status['php']['opcache'] = $this->getOpcacheStatus(
                $this->config['opcache'] ?? []
            );
        }

        return $status;
    }

    /**
     * @param array<string> $names
     * @return array<string, mixed>
     */
    private function getIniSettings(array $names): array
    {
        $result = [
            'file' => $this->platform->getPhpIniLoadedFile()
        ];
        foreach ($names as $name) {
            $result[$name] = $this->platform->getIni($name);
        }
        return $result;
    }

    /**
     * @param array<string> $names
     * @return array<string, mixed>
     */
    private function getFpmPoolStatus(array $names): array
    {
        $status = $this->platform->getFpmPoolStatus();
        /** @var array<string, mixed> $result */
        $result = [];
        foreach ($names as $name) {
            $result[$name] = $status[$name];
        }
        return $result;
    }

    /**
     * @param array<string> $configDirs
     * @return array<string,mixed>
     */
    private function getFpmConfig(array $configDirs): array
    {
        $fpmConfigFile = $this->getFpmConfigFile($configDirs);
        if ($fpmConfigFile === null) {
            return [];
        }

        $globalConfig = parse_ini_file($fpmConfigFile, true) ?: [];

        $configs = [];
        if (isset($globalConfig['global']['include'])) {
            $include = $globalConfig['global']['include'];
            if ($include[0] !== '/') {
                $include = dirname($fpmConfigFile) . '/../' . $include;
            }

            foreach (glob($include) ?: [] as $file) {
                $configs[] = parse_ini_file($file, true) ?: [];
            }
        }

        return array_merge_recursive($globalConfig, ...$configs);
    }

    /**
     * @param array<string> $configDirs
     */
    private function getFpmConfigFile(array $configDirs): ?string
    {
        $version = explode('.', $this->platform->getVersion());
        foreach ($configDirs as $configDir) {
            $configDir = str_replace(
                [
                    '{PHP_VERSION_MAJOR}',
                    '{PHP_VERSION_MINOR}',
                    '{PHP_VERSION_PATCH}'
                ],
                [
                    $version[0],
                    $version[1],
                    $version[2]
                ],
                $configDir
            );
            $iniFilePath = $configDir . '/php-fpm.conf';
            if (file_exists($iniFilePath)) {
                return $iniFilePath;
            }
        }

        return null;
    }

    /**
     * @param array<string> $names
     * @return array<string, mixed>
     */
    private function getOpcacheStatus(array $names): array
    {
        $status = $this->platform->getOpcacheGetStatus();
        $result = [];
        foreach ($names as $name) {
            $result[$name] = $status[$name];
        }
        return $result;
    }
}
