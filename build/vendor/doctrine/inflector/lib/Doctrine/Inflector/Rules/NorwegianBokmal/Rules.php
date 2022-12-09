<?php

declare (strict_types=1);
namespace Feed_Reader\Doctrine\Inflector\Rules\NorwegianBokmal;

use Feed_Reader\Doctrine\Inflector\Rules\Patterns;
use Feed_Reader\Doctrine\Inflector\Rules\Ruleset;
use Feed_Reader\Doctrine\Inflector\Rules\Substitutions;
use Feed_Reader\Doctrine\Inflector\Rules\Transformations;
final class Rules
{
    public static function getSingularRuleset() : Ruleset
    {
        return new Ruleset(new Transformations(...Inflectible::getSingular()), new Patterns(...Uninflected::getSingular()), (new Substitutions(...Inflectible::getIrregular()))->getFlippedSubstitutions());
    }
    public static function getPluralRuleset() : Ruleset
    {
        return new Ruleset(new Transformations(...Inflectible::getPlural()), new Patterns(...Uninflected::getPlural()), new Substitutions(...Inflectible::getIrregular()));
    }
}
