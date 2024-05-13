<?php

namespace app\facade\bin;

class BaseFacade
{
    private static $instance;
    public static function getInstance()
    {
        if (static::$instance == null) {
            static::$instance = new self();
        }
        return static::$instance;
    }
}