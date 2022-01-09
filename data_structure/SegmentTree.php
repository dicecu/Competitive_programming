<?php

/**
 * SegmentTree
 *
 * ref. AtCoder Library
 *
 * @version 1.0
 * @author dicecu
 * @link https://github.com/dicecu/Competitive_programming
 * @license CC0-1.0
 */
class SegmentTree
{

    private array $tree = [];
    private int $leaf_size;
    private int $tree_size;
    private int $real_size;
    private int $log;
    private $e;
    private object $operation;
    private array $t = [0xFFFFFFFF00000000,
        0x00000000FFFF0000,
        0x000000000000FF00,
        0x00000000000000F0,
        0x000000000000000C,
        0x0000000000000002];

    /**
     * constructor
     *
     * SegmentTreeを構築します。
     * 計算量 O(n)
     *
     * @param int|array $sv treeのサイズもしくは初期値配列
     * @param object $op 2値比較演算子
     * @param int $e 単位元: op(e, a) = a, op(a, e)=a
     */
    function __construct($sv, callable $op, $e)
    {
        if(gettype($sv) === "integer"){
            $count = $sv;
            $list = array_fill(0, $count, $e);
        }elseif(gettype($sv) === "array"){
            $count = count($sv);
            $list = $sv;
        }else{
            $count = 0;
            $list = [];
        }
        $this->e = $e;
        $this->operation = $op;
        $this->real_size = $count;
        $this->log = $this->ceil_log2($count);
        $this->leaf_size = 1 << $this->log;
        $this->tree_size = $this->leaf_size * 2;
        $this->tree = [...array_fill(0,$this->leaf_size, $this->e),
            ...$list,
            ...array_fill(0, $this->leaf_size - $count, $this->e)];

        for($i = $this->leaf_size - 1; $i >= 1; $i--){
            $this->update($i);
        }
    }

    private function update(int $k):void
    {
        $this->tree[$k] = ($this->operation)($this->tree[2 * $k], $this->tree[2 * $k + 1]);

    }

    public function set(int $p, $x):void
    {
        assert(0 <= $p && $p < $this->real_size);
        $p += $this->leaf_size;
        $this->tree[$p] = $x;
        for($i = 1; $i <= $this->log; $i++){
            $this->update($p >> $i);
        }
    }

    public function get(int $p)
    {
        assert(0 <= $p && $p < $this->real_size);
        return $this->tree[$p + $this->leaf_size];
    }

    /**
     * prod_all
     *
     * op(a[1], ..., a[n]) を計算します。n=1 のときは e() を返します。
     *
     * @return mixed
     */
    public function prod_all()
    {
        return $this->tree[1];
    }


    /**
     * prod
     *
     * op(a[l], ..., a[r-1]) を、モノイドの性質を満たしていると仮定して計算します。
     * 添え字は<em>0</em>スタート <em>toの手前まで</em>[from, to)を計算します。
     *
     * @param int $from
     * @param int $to
     * @return mixed
     */
    public function prod(int $from, int $to ){
        assert(0 <= $from && $from <= $to && $to <= $this->real_size);
        $sml = $smr = $this->e;
        $from += $this->leaf_size;
        $to += $this->leaf_size;
        while ($from < $to){
            if($from & 1) $sml = ($this->operation)($sml, $this->tree[$from++]);
            if($to & 1) $smr = ($this->operation)($this->tree[--$to], $smr);
            $from >>= 1;
            $to >>= 1;
        }

        return ($this->operation)($sml, $smr);

    }


    /**
     * max_right
     *
     * 以下の条件を両方満たす r を(いずれか一つ)返します。 <br>
     * r = l もしくは f(op(a[l], a[l + 1], ..., a[r - 1])) = true <br>
     * r = n もしくは f(op(a[r], a[r + 1], ..., a[n])) = false <br>
     * fが単調だとすれば、f(op(a[l], a[l + 1], ..., a[r - 1])) = true となる最大の r、と解釈することが可能です。 <br>
     * 添え字は0スタート<br>
     * 計算量 O(log n)
     *
     * @param int $l 0 ≤ l ≤ n
     * @param callable $fn 評価関数
     * @return int = 左から評価して初めてfalseになる位置
     */
    public function max_right(int $l, callable $fn): int
    {
        assert(0 <= $l && $l <= $this->real_size);
        assert($fn($this->e));

        if($l === $this->real_size){
            return $this->real_size;
        }
        $l += $this->leaf_size;
        $sm = $this->e;
        do {
            while ($l % 2 === 0) {
                $l >>= 1;
            }
            if (!$fn(($this->operation)($sm, $this->tree[$l]))) {
                while ($l < ($this->leaf_size)) {
                    $l <<= 1;
                    if ($fn(($this->operation)($sm, $this->tree[$l]))) {
                        $sm = ($this->operation)($sm, $this->tree[$l]);
                        $l++;
                    }
                }
                return $l - $this->leaf_size;
            }
            $sm = ($this->operation)($sm, $this->tree[$l]);
            $l++;

        } while (($l & -$l) !== $l);
        return $this->real_size;
    }

    /**
     * min_left
     *
     * 以下の条件を両方満たす l を(いずれか一つ)返します。<br>
     * l = r もしくは f(op(a[l], a[l + 1], ..., a[r - 1])) = true<br>
     * l = 0 もしくは f(op(a[0], a[1], ..., a[l - 1])) = false
     * fが単調だとすれば、f(op(a[l], a[l + 1], ..., a[r - 1])) = true となる最小の l、と解釈することが可能です。
     * 制約
     * fを同じ引数で呼んだ時、返り値は等しい(=副作用はない)
     * f(e()) = true
     * 0 ≤ r ≤ n
     * 計算量O(log n)
     *
     * @param int $r
     * @param callable $fn
     * @return int 右から評価して最後にtrueとなる座標
     */
    public function min_left(int $r, callable $fn): int
    {
        assert(0 <= $r && $r <= $this->real_size);
        assert($fn($this->e));
        if($r === 0){
            return 0;
        }
        $r += $this->leaf_size;
        $sm = $this->e;
        do {
            $r--;
            while (($r % 2) && $r > 1) {
                $r >>= 1;
            }
            if (!$fn(($this->operation)($this->tree[$r], $sm))) {
                while ($r < $this->leaf_size){
                    $r = $r * 2 + 1;
                    if ($fn(($this->operation)($this->tree[$r], $sm))) {
                        $sm = ($this->operation)($this->tree[$r], $sm);
                        $r--;
                    }
                }
                return $r - $this->leaf_size + 1;
            }
            $sm = ($this->operation)($this->tree[$r], $sm);
        } while (($r & -$r) !== $r);
        return 0;
    }

    private function ceil_log2(int $x):int
    {
        $y = ((($x & ($x - 1)) == 0) ? 0 : 1);
        $j = 32;
        foreach ($this->t as $value) {
            $k = ((($x & $value) == 0) ? 0 : $j);
            $y += $k;
            $x >>= $k;
            $j >>= 1;
        }
        return $y;
    }
}


/**
 * 検証
 *
 * AtCoder Library Practice Contest
 * J - Segment Tree
 * PHP 7.4.4 778 ms
 *
 * @link https://atcoder.jp/contests/practice2/tasks/practice2_j
 *
 */
function example(){

    [$N, $Q] = fscanf(STDIN, "%d%d");
    $A = fscanf(STDIN, str_repeat("%d", $N));
    $fn = fn($x, $y) => max($x, $y);
    $st = new SegmentTree($A, $fn, -1);
    while($Q--){
        [$T, $X, $V] = fscanf(STDIN, "%d%d%d");

        if($T === 1){
            $X--;
            $st->set($X, $V);
        }elseif($T === 2){
            $X--;
            echo $st->prod($X,$V).PHP_EOL;
        }else{
            $X--;
            $f = fn($x) =>$x < $V;
            echo $st->max_right($X,$f) + 1 .PHP_EOL;
        }
    }
}
//example();