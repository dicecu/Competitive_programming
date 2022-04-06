<?php
require("../PseudoMultiset.php");

example_ABC119_D();
function example_ABC119_D()
{
    // https://atcoder.jp/contests/abc119/submissions/30761007
    [$A, $B, $Q] = fscanf(STDIN, "%d%d%d");
    $S = new PseudoMultiSet();
    $T = new PseudoMultiSet();
    while($A--){
        [$s] = fscanf(STDIN, "%d");
        $S->add($s);
    }
    while($B--){
        [$t] = fscanf(STDIN, "%d");
        $T->add($t);
    }
    $A = [];
    while ($Q--) {
        [$X] = fscanf(STDIN, "%d");
        $sx = $S->lower_bound($X)->get()??100000000000;
        $sx2 = $S->prev()->get() ?? -1000000000000;
        $st = $T->lower_bound($X)->get() ?? 100000000000;
        $st2 = $T->prev()->get() ?? -1000000000000;
        $ans = max($sx, $st) - $X;
        $ans = min($ans, $X - min($sx2, $st2));
        $ans = min($ans, ($X - $sx2) * 2 + ($st - $X));
        $ans = min($ans, ($X - $sx2) + ($st - $X) * 2);
        $ans = min($ans, ($X - $st2) * 2 + ($sx - $X));
        $ans = min($ans, ($X - $st2) + ($sx - $X) * 2);
        $A[] = $ans;

    }
    echo implode("\n", $A);
}

