<?php
require("../PseudoMultiset.php");

example_ABC241_D();

function example_ABC241_D()
{
    // https://atcoder.jp/contests/abc241/submissions/30760857
    [$Q] = fscanf(STDIN, "%d");
    $S = new PseudoMultiSet();
    while ($Q--) {
        [$A, $B, $C] = fscanf(STDIN, "%d%d%d");
        if ($A === 1) {
            $S->add($B);
        } elseif ($A === 2) {
            $S->upper_bound($B);
            while ($C--) {
                $S->prev();
            }
            $ans[] = $S->get() ?? -1;

        } else {
            $C--;
            $S->lower_bound($B);
            while ($C--) {
                $S->next();
            }
            $ans[] = $S->get() ?? -1;

        }
    }
    echo implode("\n", $ans);
}

