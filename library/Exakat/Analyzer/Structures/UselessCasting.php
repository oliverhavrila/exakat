<?php
/*
 * Copyright 2012-2017 Damien Seguy – Exakat Ltd <contact(at)exakat.io>
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
use Exakat\Data\Methods;

class UselessCasting extends Analyzer {
    public function analyze() {
        // Function returning a type, then casted to that type
        $casts = array('T_STRING_CAST'  => 'string',
                       'T_BOOL_CAST'    => 'bool',
                       'T_INT_CAST'     => 'int',
                       'T_ARRAY_CAST'   => 'array',
                       'T_DOUBLE_CAST'  => 'real'
                  );
        
        $data = new Methods();
        $returnTypes = $data->getFunctionsByReturn();
        
        foreach($casts as $token => $type) {
            $this->atomIs('Cast')
                 ->tokenIs($token)
                 ->outIs('CAST')
                 ->outIsIE('CODE') // In case there are some parenthesis
                 ->atomIs('Functioncall')
                 ->fullnspathIs($returnTypes[$type])
                 ->back('first');
            $this->prepareQuery();
        }
        
        // (bool) ($a > 2)
        $this->atomIs('Cast')
             ->tokenIs('T_BOOL_CAST')
             ->outIs('CAST')
             ->outIsIE('CODE') // In case there are some parenthesis
             ->atomIs('Comparison')
             ->back('first');
        $this->prepareQuery();
    }
}

?>
