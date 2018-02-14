<?php
/*
 * Copyright 2012-2018 Damien Seguy – Exakat Ltd <contact(at)exakat.io>
 * This file is part of Exakat.
 *
 * Exakat is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Exakat is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Exakat.  If not, see <http://www.gnu.org/licenses/>.
 *
 * The latest code can be found at <http://exakat.io/>.
 *
*/

namespace Exakat\Analyzer\Performances;

use Exakat\Analyzer\Analyzer;

class DoubleArrayFlip extends Analyzer {
    public function analyze() {
        //$a = array_flip($b);
        //unset($a['c']);
        //$c = array_flip($a);
        $this->atomIs('Assignation')
             ->outIs('RIGHT')
             ->atomFunctionIs('\array_flip')
             ->inIs('RIGHT')
             ->atomIs('Assignation')
             ->outIs('LEFT')
             ->atomIs(self::$CONTAINERS)
             ->savePropertyAs('fullcode', 'container')
             ->inIs('LEFT')
             ->nextSiblings()
             ->atomIs('Assignation')
             ->outIs('RIGHT')
             ->functioncallIs('\array_flip')
             ->outWithRank('ARGUMENT', 0)
             ->atomIs(self::$CONTAINERS)
             ->samePropertyAs('fullcode', 'container')
             ->back('first');
        $this->prepareQuery();
    }
}

?>
