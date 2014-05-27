<?php

namespace Analyzer\Type;

use Analyzer;

class OneVariableStrings extends Analyzer\Analyzer {
    public function analyze() {
        $this->atomIs('String')
             ->outIs("CONTAIN")
             ->is('count', '1');
        $this->prepareQuery();
    }
}

?>