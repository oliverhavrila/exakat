<?php

namespace Test\Structures;

use Test\Analyzer;

include_once dirname(__DIR__, 2).'/Test/Analyzer.php';

class ConcatenationInterpolationFavorite extends Analyzer {
    /* 2 methods */

    public function testStructures_ConcatenationInterpolationFavorite01()  { $this->generic_test('Structures/ConcatenationInterpolationFavorite.01'); }
    public function testStructures_ConcatenationInterpolationFavorite02()  { $this->generic_test('Structures/ConcatenationInterpolationFavorite.02'); }
}
?>