<?php


/**
 * Class BIT
 *
 * 長さ N の配列に対し、
 * 1. 要素の 1 点加算
 * 2. 区間の要素の総和
 * 3. 区緩和がxを超える最小のiの取得
 * を O(logN) で求めることが出来るデータ構造です。
 * 参考: AtCoder Library
 *
 * @version 1.0
 * @author dicecu
 * @link https://github.com/dicecu/Competitive_programming
 * @license CC0-1.0
 */
class BIT
{
    private array $tree;
    private int $max;
    private int $top;

    /**
     * constructor
     *
     * 長さ n の配列を作る
     * 計算量 O(N)
     *
     * @param int $n 0 ≤ n ≤ 10^8
     */
    function __construct(int $n) //, bool $one_indexed = true)
    {
        assert(0 <= $n && $n <= 100000000);
        $this->max = $n;
        $i=1;
        while($i <= $n){
            $i <<= 1;
        }
        $this->top = ($i >> 1);
        $this->tree = array_fill(1,$n,0);
    }

    /**
     * 一点加算
     *
     * a[p] += x を行う<br>
     * 配列はは"1"スタート<br>
     * 計算量 O(log(n))
     *
     * @param int $p 挿入位置 0 ≤ p < n
     * @param int $x 挿入する数
     */
    public function add(int $p, int $x): void
    {
        assert($p > 0 && $p <= $this->max);
        for($i=$p;$i<=$this->max; $i += ($i & -$i)){
            $this->tree[$i] += $x;
        }
    }

    /**
     * (l, r] の和を求める
     *
     * [l + 1] + a[l + 2] + ... + a[r] を返す。<br>
     * 計算量 O(log(n))
     * @param int $l 0 ≤ l ≤ n
     * @param int $r l ≤ r ≤ n
     * @return int
     */
    public function sum(int $l, int $r): int
    {
        assert($l >= 0 && $l <= $r && $r <= $this->max);
        return $this->sum_from_zero($r) - $this->sum_from_zero($l);
    }

    /**
     * [0, r] の和を求める
     *
     * 計算量 O(1)
     *
     * @param int $r
     * @return int
     */
    private function sum_from_zero(int $r): int
    {
//        assert($r >= 0 && $r < $this->max);
        $sum = 0;
        for($i = $r; $i > 0; $i -= ($i & -$i) ){
            $sum += $this->tree[$i];
        }
        return $sum;
    }

    /**
     * lower bound
     *
     * 0からの区間和がtとなる最小の位置xを返す
     *
     * @param int $t
     * @return int x
     */
    public function lower_bound(int $t):int
    {
        if($t <= 0){
            return 0;
        }
        $x = 0;
        for($i = $this->top; $i > 0; $i >>=1){
            if($x + $i <= $this->max && $this->tree[$x + $i] < $t){
                $t -= $this->tree[$x + $i];
                $x += $i;
            }
        }
        return $x + 1;
    }

}

/**
 * 動作検証
 *
 * AtCoder Library Practice Contest
 * B Fenwick Tree
 * PHP 7.4.4 1200 ms
 *
 * @link https://atcoder.jp/contests/practice2/tasks/practice2_b
 *
 */
function example(){
    [$N, $Q] = fscanf(STDIN, "%d%d");
    $A = fscanf(STDIN, str_repeat("%d", $N));
    $BIT = new BIT($N);
    foreach($A as $key=> $value){
        $BIT->add($key+1, $value);
    }
    while($Q--){
        [$A, $B, $C] = fscanf(STDIN, "%d%d%d");
        if($A === 0){
            $BIT->add($B+1, $C);
        }else{
            echo $BIT->sum($B, $C).PHP_EOL;
        }
    }
}

//example();