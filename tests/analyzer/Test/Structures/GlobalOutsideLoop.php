<?php

namespace Test\Structures;

use Test\Analyzer;

include_once dirname(__DIR__, 2).'/Test/Analyzer.php';

class GlobalOutsideLoop extends Analyzer {
    /* 2 methods */

    public function testStructures_GlobalOutsideLoop01()  { $this->generic_test('Structures_GlobalOutsideLoop.01'); }
    public function testStructures_GlobalOutsideLoop02()  { $this->generic_test('Structures/GlobalOutsideLoop.02'); }
}
?>