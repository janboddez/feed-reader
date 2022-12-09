<?php

declare (strict_types=1);
namespace Feed_Reader\Doctrine\Inflector;

use Feed_Reader\Doctrine\Inflector\Rules\English;
use Feed_Reader\Doctrine\Inflector\Rules\French;
use Feed_Reader\Doctrine\Inflector\Rules\NorwegianBokmal;
use Feed_Reader\Doctrine\Inflector\Rules\Portuguese;
use Feed_Reader\Doctrine\Inflector\Rules\Spanish;
use Feed_Reader\Doctrine\Inflector\Rules\Turkish;
use InvalidArgumentException;
use function sprintf;
final class InflectorFactory
{
    public static function create() : LanguageInflectorFactory
    {
        return self::createForLanguage(Language::ENGLISH);
    }
    public static function createForLanguage(string $language) : LanguageInflectorFactory
    {
        switch ($language) {
            case Language::ENGLISH:
                return new English\InflectorFactory();
            case Language::FRENCH:
                return new French\InflectorFactory();
            case Language::NORWEGIAN_BOKMAL:
                return new NorwegianBokmal\InflectorFactory();
            case Language::PORTUGUESE:
                return new Portuguese\InflectorFactory();
            case Language::SPANISH:
                return new Spanish\InflectorFactory();
            case Language::TURKISH:
                return new Turkish\InflectorFactory();
            default:
                throw new InvalidArgumentException(sprintf('Language "%s" is not supported.', $language));
        }
    }
}
