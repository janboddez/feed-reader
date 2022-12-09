<?php

declare (strict_types=1);
namespace Feed_Reader\Doctrine\Inflector;

interface WordInflector
{
    public function inflect(string $word) : string;
}
