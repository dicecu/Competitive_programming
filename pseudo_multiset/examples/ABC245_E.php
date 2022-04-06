<?php
require("../PseudoMultiset.php");

example_ABC245_E();

function example_ABC245_E()
{
    // https://atcoder.jp/contests/abc245/submissions/30760905
    [$N, $M] = fscanf(STDIN, "%d%d");
    $A = fscanf(STDIN, str_repeat("%d",$N));
    $B = fscanf(STDIN, str_repeat("%d",$N));
    $C = fscanf(STDIN, str_repeat("%d",$M));
    $D = fscanf(STDIN, str_repeat("%d",$M));
    arsort($C);
    arsort($A);
    $AA = [];
    foreach($A as $id => $V){
        $AA[$V][] = $B[$id];
    }
    $each = [key($C), current($C)];
    $set = new PseudoMultiSet();
    foreach($AA as $V => $Bs){
        arsort($Bs);
        while($each[1] >= $V) {
            $set->add($D[$each[0]]);
            if(next($C) === false){
                $each[1] = PHP_INT_MIN;
            }else{
                $each = [key($C), current($C)];
            }
        }
        foreach($Bs as $BB){
            $set->lower_bound($BB);
            if(!$set->get()){
                echo "No";
                exit;
            }
            $set->erase();
        }

    }
    echo "Yes";
}

