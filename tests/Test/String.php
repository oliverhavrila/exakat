<?php

namespace Test;

include_once(dirname(dirname(__DIR__)).'/library/Autoload.php');
spl_autoload_register('Autoload::autoload_test');
spl_autoload_register('Autoload::autoload_phpunit');

class String extends Tokenizeur {
    /* 9 methods */
    public function testString01()  { $this->generic_test('String.01'); }
    public function testString02()  { $this->generic_test('String.02'); }
    public function testString03()  { $this->generic_test('String.03'); }
    public function testString04()  { $this->generic_test('String.04'); }
    public function testString05()  { $this->generic_test('String.05'); }
    public function testString06()  { $this->generic_test('String.06'); }
    public function testString07()  { $this->generic_test('String.07'); }
    public function testString08()  { $this->generic_test('String.08'); }
    public function testString09()  { $this->generic_test('String.09'); }
}
?>