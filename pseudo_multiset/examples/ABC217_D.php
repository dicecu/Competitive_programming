<?php
require("../PseudoMultiset.php");

example_ABC217_D();

function example_ABC217_D(){
    // https://atcoder.jp/contests/abc217/submissions/30760945
    [$L, $Q] = fscanf(STDIN, "%d%d");
    $S = new PseudoMultiSet([0, $L]);
    $ans = [];
    while($Q--){
        [$C, $X] = fscanf(STDIN, "%d%d");
        if($C === 1){
            $S->add($X);
        }else{
            $to = $S->lower_bound($X)->get();
            $from = $S->prev()->get();
            $ans[] = $to - $from;
        }
    }
    echo implode("\n", $ans);
}

