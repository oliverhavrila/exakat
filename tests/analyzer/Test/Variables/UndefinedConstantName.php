<?php

namespace Test\Variables;

use Test\Analyzer;

include_once dirname(__DIR__, 2).'/Test/Analyzer.php';

class UndefinedConstantName extends Analyzer {
    /* 1 methods */

    public function testVariables_UndefinedConstantName01()  { $this->generic_test('Variables/UndefinedConstantName.01'); }
}
?>