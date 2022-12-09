<?php

declare (strict_types=1);
namespace Feed_Reader\Doctrine\Inflector\Rules\Portuguese;

use Feed_Reader\Doctrine\Inflector\GenericLanguageInflectorFactory;
use Feed_Reader\Doctrine\Inflector\Rules\Ruleset;
final class InflectorFactory extends GenericLanguageInflectorFactory
{
    protected function getSingularRuleset() : Ruleset
    {
        return Rules::getSingularRuleset();
    }
    protected function getPluralRuleset() : Ruleset
    {
        return Rules::getPluralRuleset();
    }
}
