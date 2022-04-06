<?php
require("../PseudoMultiset.php");

example_ABC234_D();

function example_ABC234_D(){
    // https://atcoder.jp/contests/abc234/submissions/30760768
    [$N, $K] = fscanf(STDIN, "%d%d");
    $P = fscanf(STDIN, str_repeat("%d", $N));
    $S = [];
    for($i=0;$i<$K-1;$i++){
        $S[] = $P[$i];
    }
    sort($S);
    $set = new PseudoMultiSet($S);
    $last = PHP_INT_MIN;
    for($i=$K-1;$i<$N;$i++){
        $set->add($P[$i]);
        if($P[$i] > $last){
            $last = $set->rank($i-$K+1)->get();
        }
        $ans[] = $last;

    }
    echo implode("\n", $ans);

}

