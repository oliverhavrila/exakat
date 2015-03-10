<?php
/*
 * Copyright 2012-2015 Damien Seguy – Exakat Ltd <contact(at)exakat.io>
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


namespace Report\Content;

class ReportInfo extends \Report\Content {
    protected $hash = array();

    public function collect() {
        $config = \Config::factory();
        
        if (file_exists($config->projects_root.'/projects/'.$this->project.'/code/.git/config')) {
            $gitConfig = file_get_contents($config->projects_root.'/projects/'.$this->project.'/code/.git/config');
            preg_match('#url = (\S+)\s#is', $gitConfig, $r);
            $this->list['Git URL'] = $r[1];
            
            $res = shell_exec('cd '.$config->projects_root.'/projects/'.$this->project.'/code/; git branch');
            $this->list['Git branch'] = trim($res);

            $res = shell_exec('cd '.$config->projects_root.'/projects/'.$this->project.'/code/; git rev-parse HEAD');
            $this->list['Git commit'] = trim($res);
        } else {
            $this->list['Repository URL'] = 'Downloaded archive';
        }

        $datastore = new \Datastore(\Config::factory());
        
        $this->list['Number of PHP files'] = $datastore->getHash('files');
        $this->list['Number of lines of code'] = $datastore->getHash('phploc');

        $this->list['Audit software version'] = \Exakat::VERSION;
        
        $this->list['Audit execution date'] = date('r', strtotime($datastore->getHash('date_start')) );
        $this->list['Report production date'] = date('r', strtotime('now'));
        
        $this->list['PHP version'] = substr(shell_exec('php -v'), 0, 11);

        $this->list['Audit software version'] = \Exakat::VERSION. ' ( Build '. \Exakat::BUILD . ') ';
    }

    public function getArray() {
        $return = array();
        foreach($this->list as $k => $v) {
            $return[] = array($k, $v);
        }
        return $return;
    }
}

?>
