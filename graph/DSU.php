<?php

/**
 * Disjoint Set Union - DSU
 *
 * 無向グラフに対して、<br>
 * 1. 辺の追加<br>
 * 2. 2頂点が連結かの判定<br>
 * をならし O(α(n)) 時間で処理することが出来ます。<br>
 * また、内部的に各連結成分ごとに代表となる頂点を 1つ持っています。<br>
 * 辺の追加により連結成分がマージされる時、新たな代表元は元の連結成分の代表元のうちどちらかになります。<br>
 * 参考: AtCoder Library
 *
 * @version 1.0
 * @author dicecu
 * @link https://github.com/dicecu/Competitive_programming
 * @license CC0-1.0
 *
 */
class DSU
{
    private int $n;
    private array $parent;

    /**
     * DSU constructor.
     *
     * n頂点 0辺の無向グラフを作ります。
     * 制約: 0 ≤ n ≤ 10^8
     * 計算量: O(n)
     *
     * @param int $n
     */
    function __construct(int $n)
    {
        $this->n = $n;
        $this->parent = array_fill(0,$n,-1);
    }

    /**
     * function merge
     *
     * 辺 (a, b) を足します。<br>
     * a, bが連結だった場合はその代表元、非連結だった場合は新たな代表元を返します。<br>
     * 制約<br>
     * 0 ≤ a < n<br>
     * 0 ≤ b < n<br>
     * 計算量<br>
     * ならし O(α(n))
     * @param int $a
     * @param int $b
     * @return int
     */
    public function merge(int $a, int $b):int
    {
        assert(0 <= $a && $a < $this->n);
        assert(0 <= $b && $b < $this->n);
        $x = $this->leader($a);
        $y = $this->leader($b);
        if($x === $y){
            return $x;
        }
        if(-$this->parent[$x] < -$this->parent[$y]){
            [$x, $y] = [$y, $x];
        }
        $this->parent[$x] += $this->parent[$y];
        $this->parent[$y] = $x;
        return $x;
    }

    /**
     * function same
     *
     * 頂点 a, ba,b が連結かどうかを返します。<br>
     * 制約<br>
     * 0 ≤ a < n<br>
     * 0 ≤ b < n<br>
     * 計算量<br>
     * ならし O(α(n))
     * @param int $a
     * @param int $b
     * @return bool
     */
    public function same(int $a, int $b): bool
    {
        assert(0 <= $a && $a < $this->n);
        assert(0 <= $b && $b < $this->n);
        return $this->leader($a) === $this->leader($b);
    }

    /**
     * function leader
     *
     * 頂点aの属する連結成分の代表元を返します。<br>
     * 制約<br>
     * 0 ≤ a < n<br>
     * 計算量<br>
     * ならし O(α(n))<br>
     * @param int $a
     * @return int
     */
    public function leader(int $a): int
    {
        if($this->parent[$a] < 0){
            return $a;
        }
        return $this->parent[$a] = $this->leader($this->parent[$a]);
    }

    /**
     * function size
     *
     * 頂点aの属する連結成分のサイズを返します。<br>
     * 制約<br>
     * 0 ≤ a < n<br>
     * 計算量<br>
     * ならし O(α(n))<br>
     * @param int $a
     * @return int
     */
    public function size(int $a):int
    {
        assert(0 <= $a && $a < $this->n);
        return -$this->parent[$this->leader($a)];
    }

    /**
     * function groups
     *
     * グラフを連結成分に分け、その情報を返します。<br>
     * 返り値は「「一つの連結成分の頂点番号のリスト」のリスト」です。<br>
     * (内側外側限らず)vector内でどの順番で頂点が格納されているかは未定義です。<br>
     * 計算量<br>
     * O(n)<br>
     * @return array
     */
    public function groups(): array
    {
        $leader_buf = $group_size = array_fill(0,$this->n, 0);
        $result = [];
        for($i=0;$i<$this->n;$i++){
            $result[$this->leader($i)][] = $i;
        }
        return $result;
    }
}


/**
 * 検証
 *
 * AtCoder Library Practice Contest
 * A - Disjoint Set Union
 * PHP 7.4.4 287 ms
 *
 * @link https://atcoder.jp/contests/practice2/tasks/practice2_a
 *
 */
function example(){
    [$N, $Q] = fscanf(STDIN, "%d%d");
    $dsu = new DSU($N);
    while($Q--){
        [$T, $U, $V] = fscanf(STDIN, "%d%d%d");
        if($T === 0){
            $dsu->merge($U, $V);
        }else{
            if($dsu->same($U, $V)){
                echo 1 .PHP_EOL;
            }else{
                echo 0 .PHP_EOL;
            }
        }
    }
}
//example();