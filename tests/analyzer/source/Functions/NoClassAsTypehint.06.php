<?php

interface i { function m() ;}
class ci implements i {
    function m() {}
}
function foop(i $i11) { echo $i11->p; $i11::$p;}
function fooc(i $i12) { echo $i12::c;}
function foom(i $i13) { echo $i13->m(); $i13::m();}

function foopc(ci $i1) { echo $i1->p;}
function foocc(ci $i2) { echo $i2::c;}
function foomc(ci $i3) { echo $i3->m();}

?>