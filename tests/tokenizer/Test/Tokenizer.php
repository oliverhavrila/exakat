<?php

namespace Test;

class Tokenizer extends \PHPUnit_Framework_TestCase {
    public function generic_test($file) {
        $shell = 'cd ../..; sh scripts/clean.sh; php bin/load -r -f ./tests/tokenizer/source/'.$file.'.php -p test; php bin/build_root -p test; php bin/tokenizer -p test;';
        $res = shell_exec($shell);
        
        $shell = 'cd ../..; php bin/export -text -f ./tests/tokenizer/source/'.$file.'.php';
        $res = shell_exec($shell);
        
        $exp = file_get_contents('exp/'.$file.'.txt');
        $this->assertNotContains("Label : NEXT", $exp);
        $this->assertNotContains("Parse error", $exp);
        
        $this->assertEquals($exp, $res);

        $shell = 'cd ../..; php bin/stat -json';
        $decode = json_decode(shell_exec($shell));
        
        $this->assertEquals($decode->INDEXED_count, 0, 'There are '.$decode->INDEXED_count.' INDEXED left');
    }
}

?>