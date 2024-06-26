<?php

namespace Homeleon\Config;

use Exception;
use Homeleon\Support\ServiceProvider;

class ConfigServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->set(Config::class, function () {
            return new Config($this->loadConfig());
        });
    }

    /**
     * @throws Exception
     */
    public function loadConfig()
    {
        $configFile = ROOT . '/.env.php';

        if (!file_exists($configFile)) {
            throw new Exception('Config file does not exists. Please run "php fw" in root directory for build config file');
        }

        return require $configFile;
    }
}
