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


namespace Exakat\Analyzer\Structures;

use Exakat\Analyzer\Analyzer;

class RepeatedPrint extends Analyzer {
    public function analyze() {
        // first one in sequence
        $this->atomIs(array('Print', 'Echo'))
             ->is('rank', 0)
             ->nextSibling()
             ->atomIs(array('Print', 'Echo'))
             ->back('first');
        $this->prepareQuery();

        $this->atomIs(array('Print', 'Echo'))
             ->isNot('rank', 0)
             ->nextSibling()
             ->atomIs(array('Print', 'Echo'))
             ->back('first')
             ->previousSibling()
             ->atomIsNot(array('Print', 'Echo'))
             ->back('first');
        $this->prepareQuery();
    }
}

?>
