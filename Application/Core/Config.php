<?php

namespace PQ\Core;

class Config {
    public static $config;

    public static function get($key)
    {
        if (!self::$config) {

            $config_file = '../Application/Config/config.php';

            if (!file_exists($config_file)) {
                return false;
            }

            self::$config = require $config_file;
        }

        return self::$config[$key];
    }
}