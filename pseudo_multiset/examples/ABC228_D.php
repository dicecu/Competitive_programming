<?php
require("../PseudoMultiset.php");

example_ABC228_D();

function example_ABC228_D(){
    // https://atcoder.jp/contests/abc228/submissions/30760933
    $N = 2 ** 20;
    [$Q] = fscanf(STDIN, "%d");
    $S = new PseudoMultiSet(range(0,2**20 -1));
    $D = [];
    $ans = [];
    while($Q--){
        [$T, $X] = fscanf(STDIN, "%d%d");
        if($T === 1){
            $target = $X % $N;
            $add = $S->lower_bound($target)->get();
            if(!$add){
                $add = $S->begin()->get();
            }
            $D[$add] = $X;
            $S->erase();
        }else{
            $ans[] = $D[$X % $N] ?? -1;
        }
    }
    echo implode("\n", $ans);
}

