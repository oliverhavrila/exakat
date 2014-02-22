<?php
    $args = $argv;
    
    if (!isset($args[1])) {
        print "Usage : create_test.php Testname\n ";
        die();
    }
    $test = $args[1];
    
    if (strpos($test, '/') === false) {
        print "The test should look like 'X/Y'. Aborting\n";
        die();
    }
    
    list($dir, $test) = explode('/', $test);
    
    if (!file_exists(dirname(dirname(__DIR__)).'/library/Analyzer/'.$dir)) {
        $groups = array_map('basename', glob(dirname(dirname(__DIR__)).'/library/Analyzer/*' , GLOB_ONLYDIR));
        $closest = closest_string($dir, $groups);
        print "No such analyzer group '$dir'. Did you mean '$closest' ? \nChoose among : ".join(', ', $groups);
        
        print ". Aborting.\n";
        die();
    }

    if (!file_exists(dirname(dirname(__DIR__)).'/library/Analyzer/'.$dir.'/'.$test.'.php')) {
        $groups = array_map( function ($name) { return substr(basename($name), 0, -4); }, 
                             glob(dirname(dirname(__DIR__)).'/library/Analyzer/'.$dir.'/*'));
        $closest = closest_string($dir, $groups);
        print "No such analyzer '{$dir}/$test'. Did you mean '$closest' ? \nChoose among : ".join(', ', $groups);
        
        print ". Aborting.\n";
        die();
    }
    
    // restore $test value
    $test = $dir.'_'.$test;
    $files = glob('source/'.$test.'.*.php');
    sort($files);
    $last = array_pop($files);
    $number = str_replace(array($test, '.php', '.', 'source/'), '', $last);
    
    if ($number + 1 == 100) { 
        print "Too many tests for $test : reaching 100 of them. Aborting\n";
        die();
    }
    $next = substr("00".($number + 1), -2);

    if (file_exists('Test/'.$test.'.php')) {
        $code = file_get_contents('Test/'.$test.'.php');
    } else {
        copy('Test/Skeleton.php', 'Test/'.$test.'.php');
        $code = file_get_contents('Test/'.$test.'.php');
        
        $code = str_replace('Skeleton', $test, $code);
    }

    $code = substr($code, 0, -4)."    public function test$test$next()  { \$this->generic_test('$test.$next'); }
".substr($code, -4);
    $count = $next + 0;
    $code = preg_replace('#/\* \d+ methods \*/#is', '/* '.$count.' methods */', $code);

    file_put_contents('Test/'.$test.'.php', $code);

    file_put_contents('./source/'.$test.'.'.$next.'.php', "<?php

?>");
    shell_exec('bbedit ./source/'.$test.'.'.$next.'.php');
    
    file_put_contents('./exp/'.$test.'.'.$next.'.php', "<?php

\$expected     = array();

\$expected_not = array();

?>");
    shell_exec('bbedit ./exp/'.$test.'.'.$next.'.php');
    
    $config = file_get_contents(dirname(dirname(__DIR__)).'/projects/test/config.ini');
    if (strpos($config, str_replace('_', '/', $test)) === false) {
        print "$test is not configured. Adding it.\n";
        
        $config .= "analyzer[] = '".str_replace('_', '/', $test)."'\n";

        file_put_contents(dirname(dirname(__DIR__)).'/projects/test/config.ini', $config);
    }
    
    function closest_string($string, $array) {
        $shortest = -1;

        foreach ($array as $a) {
            $lev = levenshtein($string, $a);

            if ($lev == 0) {
                $closest = $a;
                $shortest = 0;
                break;
            }

            if ($lev <= $shortest || $shortest < 0) {
                // set the closest match, and shortest distance
                $closest  = $a;
                $shortest = $lev;
            }
        }
        
        return $closest;
    }
?>