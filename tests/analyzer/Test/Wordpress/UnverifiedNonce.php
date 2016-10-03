<?php

namespace Test;

include_once(dirname(dirname(dirname(dirname(__DIR__)))).'/library/Autoload.php');
spl_autoload_register('Autoload::autoload_test');
spl_autoload_register('Autoload::autoload_phpunit');
spl_autoload_register('Autoload::autoload_library');

class Wordpress_UnverifiedNonce extends Analyzer {
    /* 1 methods */

    public function testWordpress_UnverifiedNonce01()  { $this->generic_test('Wordpress/UnverifiedNonce.01'); }
}
?>