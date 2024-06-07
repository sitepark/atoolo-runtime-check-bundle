<?php

declare(strict_types=1);

namespace Atoolo\Runtime\Check\Service;

class ProcessStatus
{
    private readonly array $config;

    public function __construct(
        string $config = __DIR__ . '/phpStatus.json'
    ) {
        $this->config = json_decode(
            file_get_contents($config),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }
    public function getStatus(): array
    {
        $status = [
            'user' => (posix_getpwuid(posix_geteuid())
                ?: ['name' => posix_geteuid()])['name'],
            'group' => (posix_getgrgid(posix_getegid())
                ?: ['name' => posix_getegid()])['name'],
            'php' => [
                'version' => PHP_VERSION,
                'ini' => $this->getIniSettings($this->config['ini'] ?? [])
            ]
        ];

        if (PHP_SAPI === 'fpm-fcgi') {
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

    private function getIniSettings(array $names): array
    {
        $result = [
            'file' => php_ini_loaded_file()
        ];
        foreach ($names as $name) {
            $result[$name] = ini_get($name);
        }
        return $result;
    }

    private function getFpmPoolStatus(array $names): array
    {
        $status = fpm_get_status();
        $result = [];
        foreach ($names as $name) {
            $result[$name] = $status[$name];
        }
        return $result;
    }

    private function getFpmConfig(array $configDirs): array
    {
        $version = explode('.', PHP_VERSION);
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
                break;
            }
        }

        if ($iniFilePath === null || !file_exists($iniFilePath)) {
            return [];
        }

        $globalConfig = parse_ini_file($iniFilePath, true);

        $include = $globalConfig['global']['include'] ?? '';
        if ($include[0] !== '/') {
            $include = dirname($iniFilePath) . '/../' . $include;
        }

        $configs = [];
        foreach (glob($include) as $file) {
            $c = parse_ini_file($file, true);
            $configs[] = parse_ini_file($file, true);
        }

        return array_merge_recursive($globalConfig, ...$configs);
    }

    private function getOpcacheStatus(array $names): array
    {
        $status = opcache_get_status();
        $result = [];
        foreach ($names as $name) {
            $result[$name] = $status[$name];
        }
        return $result;
    }
}
