<?php

declare(strict_types=1);

use Dotenv\Dotenv;

if (! function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     *
     * @throws \RuntimeException
     */
    function env(string $key, mixed $default = null): mixed
    {
        static $dotenv = null;
        static $dotenvLoaded = false;

        if (! $dotenvLoaded) {
            $appRoot = dirname(__DIR__);
            $dotenv = Dotenv::createUnsafeImmutable($appRoot);
            if (file_exists($appRoot . '/.env')) {
                $dotenv->load();
            }

            $dotenvLoaded = true;
        }

        if ($key === '' && is_array($default)) {
            try {
                $dotenv->required($default);
            } catch (\Dotenv\Exception\ValidationException $e) {
                throw new \RuntimeException($e->getMessage(), (int) $e->getCode(), $e);
            }

            return null;
        }

        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => $value,
        };
    }
}

if (! function_exists('env_required')) {
    /**
     * Validates that the given environment variables are set.
     *
     * @param array<int, string>|string $keys
     *
     * @throws \RuntimeException
     */
    function env_required(array|string $keys): void
    {
        env('', (array) $keys);
    }
}
