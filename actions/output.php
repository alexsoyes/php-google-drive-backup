<?php

class Output
{
    public const COLOR_ERROR = 'error';
    public const COLOR_SUCCESS = 'success';
    public const COLOR_WARNING = 'warning';
    public const COLOR_INFO = 'info';

    public static function log(string $text, string $color = ""): void
    {
        switch ($color)
        {
            case self::COLOR_ERROR:
                echo "\033[31m$text \033[0m\n";
                break;
            case self::COLOR_SUCCESS:
                echo "\033[32m$text \033[0m\n";
                break;
            case self::COLOR_WARNING:
                echo "\033[33m$text \033[0m\n";
                break;
            case self::COLOR_INFO:
                echo "\033[36m$text \033[0m\n";
                break;
            default:
                echo "$text\n";
                break;
        }
    }
}
