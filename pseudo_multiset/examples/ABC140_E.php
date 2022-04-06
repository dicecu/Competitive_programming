<?php
require("../PseudoMultiset.php");

example_ABC140_E();

function example_ABC140_E(){
    // https://atcoder.jp/contests/abc140/submissions/30760986
    [$N] = fscanf(STDIN, "%d%d");
    $P = fscanf(STDIN, str_repeat("%d", $N));
    arsort($P);
    $S = new PseudoMultiSet();
    $min = 0;
    $max = $N+1;
    $ans = 0;
    foreach($P as $index => $value){
        $index++;
        $S->add($index);
        $l1 = $S->find($index)->prev()->get();
        $l2 = $S->prev()->get();
        $r1 = $S->find($index)->next()->get();
        $r2 = $S->next()->get();
        if($l1 === null && $r1 === null){
            continue;
        }
        $mul = 0;
        if($l1 !== null){
            $mul += (($r1 ?? ($max)) - $index) * ($l1 - ($l2 ?? $min)) ;
        }
        if($r1 !== null){
            $mul += (($r2 ?? $max) - $r1) * ($index - ($l1 ?? $min));
        }

        $ans += $mul * $value;


    }
    echo $ans;
}

