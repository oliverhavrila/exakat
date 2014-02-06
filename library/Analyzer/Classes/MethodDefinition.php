<?php

namespace Analyzer\Classes;

use Analyzer;

class MethodDefinition extends Analyzer\Analyzer {

    public function analyze() {
        $this->atomIs("Class")
             ->outIs('BLOCK')
             ->outIs('CODE')
             ->outIs('ELEMENT')
             ->atomIs('Function')
             ->outIs('NAME');
        $this->prepareQuery();

        $this->atomIs("Class")
             ->outIs('BLOCK')
             ->outIs('CODE')
             ->atomIs('Function')
             ->outIs('NAME');
        $this->prepareQuery();
    }
}

?>