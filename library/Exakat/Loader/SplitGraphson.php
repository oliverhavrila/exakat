<?php declare(strict_types = 1);
/*
 * Copyright 2012-2019 Damien Seguy – Exakat SAS <contact(at)exakat.io>
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


namespace Exakat\Loader;

use Exakat\Data\Collector;
use Exakat\Tasks\Helpers\Atom;
use stdClass;

class SplitGraphson extends Loader {
    private const CSV_SEPARATOR = ',';
    private const LOAD_CHUNK      = 10000;
    private $load_chunk = 20000;
    private const LOAD_CHUNK_LINK = 20000;

    private $tokenCounts   = array('Project' => 1);
    private $functioncalls = array();

    private $config = null;

    private $id        = 1;

    private $graphdb        = null;
    private $path           = '';
    private $pathLink       = '';
    private $pathProperties = '';
    private $pathDef        = '';
    private $total          = 0;

    private $dictCode = null;

    private $datastore = null;
    private $sqlite3   = null;

    private $log = null;

    public function __construct(\Sqlite3 $sqlite3, Atom $id0) {
        $this->config         = exakat('config');
        $this->graphdb        = exakat('graphdb');

        $this->sqlite3        = $sqlite3;
        $this->path           = "{$this->config->tmp_dir}/graphdb.graphson";
        $this->pathLink       = "{$this->config->tmp_dir}/graphdb.link.graphson";
        $this->pathProperties = "{$this->config->tmp_dir}/graphdb.properties.graphson";
        $this->pathDef        = "{$this->config->tmp_dir}/graphdb.def";

        $this->dictCode  = new Collector();
        $this->datastore = exakat('datastore');

        $this->log = fopen($this->config->log_dir . '/loader.timing.csv', 'w+');

        $this->cleanCsv();

        $jsonText = json_encode($id0->toGraphsonLine($this->id)) . PHP_EOL;
        assert(!json_last_error(), 'Error encoding ' . $id0->atom . ' : ' . json_last_error_msg());

        file_put_contents($this->path, $jsonText, \FILE_APPEND);

        ++$this->total;
    }

    public function __destruct() {
        $this->cleanCsv();
    }

    public function finalize(array $relicat): bool {
        if ($this->total !== 0) {
            $this->saveNodes();
        }

        display("Init finalize\n");
        
        $this->saveProperties();
        
        $begin = microtime(true);
        $query = 'g.V().hasLabel("Project").id();';
        $res = $this->graphdb->query($query);
        $project_id = $res->toInt();

        $query = 'g.V().hasLabel("File").not(where( __.in("PROJECT"))).addE("PROJECT").from(__.V(' . $project_id . '));';
        $this->graphdb->query($query);

        $query = 'g.V().hasLabel("Virtualglobal").not(where( __.in("GLOBAL"))).addE("GLOBAL").from(__.V(' . $project_id . '));';
        $this->graphdb->query($query);

        $f = fopen('php://memory', 'r+');
        $total = 0;
        $chunk = 0;

        foreach($relicat as $row) {
            fputcsv($f, $row);
            ++$total;
            ++$chunk;
        }
        if ($chunk > $this->load_chunk) {
            $f = $this->saveLinks($f);
            $chunk = 0;
            $this->load_chunk = self::LOAD_CHUNK / 100 * rand(1, 100);
        }

        $res = $this->sqlite3->query('SELECT origin, destination FROM globals');
        while($row = $res->fetchArray(\SQLITE3_NUM)) {
            $row = array_map(array($this->graphdb, 'fixId'), $row);
            fputcsv($f, $row);
            ++$total;
            ++$chunk;
        }
        unset($res);
        if ($chunk > $this->load_chunk) {
            $f = $this->saveLinks($f);
            $chunk = 0;
            $this->load_chunk = self::LOAD_CHUNK / 100 * rand(1, 100);
        }

        $definitionSQL = <<<'SQL'
SELECT DISTINCT CASE WHEN definitions.id IS NULL THEN definitions2.id ELSE definitions.id END AS definition, GROUP_CONCAT(DISTINCT calls.id) AS call, count(calls.id) AS id
FROM calls
LEFT JOIN definitions 
    ON definitions.type       = calls.type       AND
       definitions.fullnspath = calls.fullnspath
LEFT JOIN definitions definitions2
    ON definitions2.type       = calls.type       AND
       definitions2.fullnspath = calls.globalpath 
WHERE (definitions.id IS NOT NULL OR definitions2.id IS NOT NULL)
GROUP BY definition
SQL;
        $res = $this->sqlite3->query($definitionSQL);
        // Fast dump, with a write to memory first
        while($row = $res->fetchArray(\SQLITE3_NUM)) {
            // Skip reflexive definitions, which never exist.
            if ($row[0] === $row[1]) { continue; }
            $total += $row[2];
            $chunk += $row[2];
            unset($row[2]);
            $row[0] = $this->graphdb->fixId($row[0]);
            $r = explode(',', $row[1]);
            $r = array_map(array($this->graphdb, 'fixId'), $r);
            $row[1] = implode('-', $r);
            fputcsv($f, $row);

            if ($chunk > $this->load_chunk) {
                $f = $this->saveLinks($f);
                $chunk = 0;
                $this->load_chunk = self::LOAD_CHUNK / 100 * rand(1, 100);
            }
        }

        if (empty($total)) {
            display('no definitions');
        } else {
            display("loading $total definitions");
            $this->saveLinks($f);
            display("loaded $total definitions");
        }
        $end = microtime(true);

        $this->saveTokenCounts();

        display('loaded nodes (duration : ' . number_format( ($end - $begin) * 1000, 2) . ' ms)');

        $this->cleanCsv();
        display('Cleaning CSV');

        return true;
    }

    private function saveProperties() {
        if (file_exists($this->pathProperties)) {
            $count = count(file($this->pathProperties));
            
            if ($count === 0) {
                $this->log("properties\t$count\t0");

                return;
            }

            $begin = hrtime(true);
            $query = <<<GREMLIN
new File('$this->pathProperties').eachLine {
    (property, targets) = it.split('-');
    vertices = targets.split(',');

    g.V(vertices).property(property, true).iterate();
}

GREMLIN;
            $this->graphdb->query($query);
            $end = hrtime(true);

            unlink($this->pathProperties);

            $this->log("properties\t$count\t" . ($end - $begin));
        }
    }

    private function saveLinks($f) {
        rewind($f);
        $fp = fopen($this->pathDef, 'w+');
        $length = fwrite($fp, stream_get_contents($f));
        fclose($fp);
        fclose($f);

        if ($length > 0) {
            $begin = hrtime(true);
            $query = <<<GREMLIN
new File('$this->pathDef').eachLine {
    (fromVertex, target) = it.split(',')

    toVertices = target.split('-');

    g.V(toVertices).addE('DEFINITION').from(V(fromVertex)).iterate();
}

GREMLIN;
            $this->graphdb->query($query);
            $end = hrtime(true);

            $this->log("links finalize\t" . ($end - $begin));
        }

        return fopen('php://memory', 'r+');
    }

    private function cleanCsv(): void {
        if (file_exists($this->path)) {
            unlink($this->path);
        }

        if (file_exists($this->pathLink)) {
            unlink($this->pathLink);
        }

        if (file_exists($this->pathProperties)) {
            unlink($this->pathProperties);
        }

        if (file_exists($this->pathDef)) {
            unlink($this->pathDef);
        }
    }

    private function saveTokenCounts(): void {
        $datastore = exakat('datastore');

        $datastore->addRow('tokenCounts', $this->tokenCounts);
    }

    public function saveFiles(string $exakatDir, array $atoms, array $links): void {
        $fileName = 'unknown';

        $json     = array();
        $properties = array('noscream'     => array(),
                            'reference'    => array(),
                            'variadic'     => array(),
                            'heredoc'      => array(),
                            'flexible'     => array(),
                            'constant'     => array(),
                            'enclosing'    => array(),
                            'final'        => array(),
                            'boolean'      => array(),
                            'bracket'      => array(),
                            'close_tag'    => array(),
                            'trailing'     => array(),
                            'alternative'  => array(),
                            'absolute'     => array(),
                            'abstract'     => array(),
                            'aliased'      => array(),
                            'isRead'       => array(),
                            'isModified'   => array(),
                            'static'       => array(),
                            'isNull'       => array(),
                            );
        foreach($atoms as $atom) {
            if ($atom->atom === 'File') {
                $fileName = $atom->code;
            }
            $json[$atom->id] = $atom->toGraphsonLine($this->id);
            foreach($atom->boolProperties() as $property) {
                $properties[$property][] = $atom->id;
            }

            if ($atom->atom === 'Functioncall' &&
                !empty($atom->fullnspath)) {
                if (isset($this->functioncalls[$atom->fullnspath])) {
                    ++$this->functioncalls[$atom->fullnspath];
                } else {
                    $this->functioncalls[$atom->fullnspath] = 1;
                }
            }
        }

        foreach($links as &$link) {
            if (isset($this->tokenCounts[$link[0]])) {
                ++$this->tokenCounts[$link[0]];
            } else {
                $this->tokenCounts[$link[0]] = 1;
            }

            $link[1] = $this->graphdb->fixId($link[1]);
            $link[2] = $this->graphdb->fixId($link[2]);
            $link = implode('-', $link);
        }

        $total = 0; // local total
        $append = array();
        foreach($json as $j) {
            $V = $j->properties['code'][0]->value;
            $j->properties['code'][0]->value = $this->dictCode->get($V);

            $j->properties['lccode'][0]->value = $this->dictCode->get($j->properties['lccode'][0]->value);

            if (isset($j->properties['propertyname']) ) {
                $j->properties['propertyname'][0]->value = $this->dictCode->get($j->properties['propertyname'][0]->value);
            }

            if (isset($j->properties['globalvar']) ) {
                $j->properties['globalvar'][0]->value = $this->dictCode->get($j->properties['globalvar'][0]->value);
            }

            $X = $this->json_encode($j);
            assert(!json_last_error(), $fileName . ' : error encoding normal ' . $j->label . ' : ' . json_last_error_msg() . "\n" . print_r($j, true));
            $append[] = $X;

            if (isset($this->tokenCounts[$j->label])) {
                ++$this->tokenCounts[$j->label];
            } else {
                $this->tokenCounts[$j->label] = 1;
            }
            ++$this->total;

            ++$total;
        }

        file_put_contents($this->path, implode(PHP_EOL, $append) . PHP_EOL, \FILE_APPEND);
        file_put_contents($this->pathLink, implode(PHP_EOL, $links) . PHP_EOL, \FILE_APPEND);
        foreach($properties as $property => $targets) {
            if (!empty($targets)) {
                file_put_contents($this->pathProperties, $property.'-'.implode(',', $targets) . PHP_EOL, \FILE_APPEND);
            }
        }

        if ($this->total > $this->load_chunk) {
            $this->saveNodes();
            $this->load_chunk = self::LOAD_CHUNK / 100 * rand(1, 100);
        }

        $this->datastore->addRow('dictionary', $this->dictCode->getRecent());
    }

    private function saveNodes(): void {
        $begin = hrtime(true);
        $this->graphdb->query("graph.io(IoCore.graphson()).readGraph(\"$this->path\");");
        unlink($this->path);
        $end = hrtime(true);
        $this->log("path\t{$this->total}\t" . ($end - $begin));

        if (file_exists($this->pathLink)) {
            $count = count(file($this->pathLink));
            $begin = hrtime(true);
            $query = <<<GREMLIN
new File('$this->pathLink').eachLine {
    (theLabel, fromVertex, toVertex) = it.split('-');

    g.V(fromVertex).addE(theLabel).to(V(toVertex)).iterate();
}

GREMLIN;
            $this->graphdb->query($query);
            $end = hrtime(true);

            unlink($this->pathLink);

            $this->log("links\t$count\t" . ($end - $begin));
        }

        $this->total = 0;
    }

    private function json_encode(Stdclass $object): string {
        // in case the function name is full of non-encodable characters.
        if (isset($object->properties['fullnspath']) && !mb_check_encoding($object->properties['fullnspath'][0]->value, 'UTF-8')) {
            $object->properties['fullnspath'][0]->value = utf8_encode($object->properties['fullnspath'][0]->value);
        }
        if (isset($object->properties['propertyname']) && !mb_check_encoding((string) $object->properties['propertyname'][0]->value, 'UTF-8')) {
            $object->properties['propertyname'][0]->value = utf8_encode($object->properties['propertyname'][0]->value);
        }
        if (isset($object->properties['fullcode']) && !mb_check_encoding((string) $object->properties['fullcode'][0]->value, 'UTF-8')) {
            $object->properties['fullcode'][0]->value = utf8_encode($object->properties['fullcode'][0]->value);
        }
        if (isset($object->properties['code']) && !mb_check_encoding((string) $object->properties['code'][0]->value, 'UTF-8')) {
            $object->properties['code'][0]->value = utf8_encode($object->properties['code'][0]->value);
        }
        if (isset($object->properties['noDelimiter']) && !mb_check_encoding((string) $object->properties['noDelimiter'][0]->value, 'UTF-8')) {
            $object->properties['noDelimiter'][0]->value = utf8_encode($object->properties['noDelimiter'][0]->value);
        }
        if (isset($object->properties['globalvar']) && !mb_check_encoding((string) $object->properties['globalvar'][0]->value, 'UTF-8')) {
            $object->properties['globalvar'][0]->value = utf8_encode($object->properties['globalvar'][0]->value);
        }
        return json_encode($object);
    }

    private function log(string $message): void {
        fwrite($this->log, $message . PHP_EOL);
    }
}

?>
