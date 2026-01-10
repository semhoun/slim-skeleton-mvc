<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final readonly class Settings
{
    /**
     * Constructs the class instance with specified settings.
     *
     * @param array<string, mixed> $settings An associative array of settings used to configure the instance.
     *                                       The keys are strings, and the values can be of any type.
     */
    public function __construct(
        private array $settings
    ) {
    }

    public function get(string $parentsStr): mixed
    {
        $settings = $this->settings;
        $parents = explode('.', $parentsStr);

        foreach ($parents as $parent) {
            if (is_array($settings) && (isset($settings[$parent]) || array_key_exists($parent, $settings))) {
                $settings = $settings[$parent];
            } else {
                throw new RuntimeException(sprintf('Trying to fetch invalid setting "%s"', implode('.', $parents)));
            }
        }

        return $settings;
    }

    public static function load(): self
    {
        $config = require self::getAppRoot() . '/config/settings/_base_.php';

        foreach (glob(self::getAppRoot() . '/config/settings/*.php') as $file) {
            $key = basename($file, '.php');
            if ($key === '_base_') {
                continue;
            }

            $config[$key] = require $file;
        }

        return new self($config);
    }

    public static function getAppRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}
