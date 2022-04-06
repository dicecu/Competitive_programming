<?php
require("../PseudoMultiset.php");

example_ABC170_E();

function example_ABC170_E(){
    // https://atcoder.jp/contests/abc170/submissions/30760964
    [$N, $Q] = fscanf(STDIN, "%d%d");
    $E = [];
    $C = [];
    $L = [];
    for($i=1;$i<=$N;$i++){
        [$A, $B] = fscanf(STDIN, "%d%d");
        $C[$i] = [$A, $B];
        $L[$B][] = $A;
    }
    $empty = new PseudoMultiSet();
    $M = [];
    for($i=1;$i<=200000;$i++){
        if(isset($L[$i])){
            sort($L[$i]);
            $E[$i] = new PseudoMultiSet($L[$i]);
            $M[] = end($L[$i]);
        }
    }
    sort($M);
    $maxmin = new PseudoMultiSet($M);
    $ans = [];

    while($Q--){
        [$Ci, $Ei] = fscanf(STDIN, "%d%d");
        [$A, $B] = $C[$Ci];
        $max = $E[$B]->end()->get();
        if($max === $A){
            $E[$B]->erase();
            $maxmin->find($A);
            $newmax = $E[$B]->end()->get();
            if($newmax) {
                $maxmin->erase()->add($newmax);
            }else{
                $maxmin->erase();
            }
        }else{
            $E[$B]->find($A)->erase();
        }
        if(isset($E[$Ei])) {
            $max = $E[$Ei]->end()->get();
            if ($max === null) {
                $maxmin->add($A);
            } elseif ($max < $A) {
                $maxmin->find($max)->erase()->add($A);
            }
        }else{
            $maxmin->add($A);
            $E[$Ei] = clone $empty;
        }
        $E[$Ei]->add($A);
        $ans[] = $maxmin->begin()->get();
        $C[$Ci][1] = $Ei;
    }
    echo implode("\n", $ans);
}

