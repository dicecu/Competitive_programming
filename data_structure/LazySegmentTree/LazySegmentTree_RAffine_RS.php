<?php

/**
 * PHP8.1 + JIT 環境で 4000msくらいで通る。はず。
 * はやくこーい
 *
 * AtCoder Library Practice Contest
 * K - Range Affine Range Sum
 * @link https://atcoder.jp/contests/practice2/submissions/32459460
 *
 */

const MOD = 998244353;
[$N, $Q] = fscanf(STDIN, "%d%d");
$A = fscanf(STDIN, str_repeat("%d", $N));

foreach($A as &$value){
    $value = ($value << 32) + 1;
}


$st = new LazySegmentTree($A);
$ans = [];
while($Q--){
    [$T, $L, $R, $C, $D] = fscanf(STDIN, "%d%d%d%d%d");
    if($T === 0){
        $st->update($L, $R, ($C << 32) + $D);
    }else {
        $ans[] = $st->get($L, $R) >> 32;
    }
}
echo implode("\n", $ans);


/**
 * Class LazySegmentTree
 *
 * 遅延評価セグメント木
 * ref. AtCoder Library
 *
 * @version 2.0
 * @author dicecu
 * @link https://github.com/dicecu/Competitive_programming
 * @license CC0-1.0
 */
class LazySegmentTree
{
    private $tree;
    private $lz;
    private int $leaf_size;
    private int $tree_size;
    private int $height;
    private array $t = [0x0FFFFFFF00000000,
        0x00000000FFFF0000,
        0x000000000000FF00,
        0x00000000000000F0,
        0x000000000000000C,
        0x0000000000000002];
    private array $depth = [0,1,3,7,15,31,63,127,255,511,1023,2047,4095,8191,16383,32767,65535, 131071,
        262143,524287,1048575, 2097151, 4194303, 8388607, 16777215, 33554431, 67108863, 134217727, 268435455, 536870911,
        1073741823, 2147483647, 4294967295, 8589934591];


    ///////////////////////////////////////////////////////
    ///
    /// ここから書き換える
    ///
    ///////////////////////////////////////////////////////

    /**
     * 単位元 e
     * 取得時に作用させても値が変化しない
     * op(e, a) = a, op(b, e) = b を満たす
     * @var mixed
     */
    private $e = -1;

    private int $max = (1 << 32 )- 1;

    /**
     * 区間取得の演算 op
     * [a1, a3] を取得する場合、 op(op(a1, a2), a3)
     * @param mixed $a
     * @param mixed $b
     * @return mixed
     */
    private function op($a, $b)
    {
        if($a === $this->e){
            return $b;
        }
        if($b === $this->e){
            return $a;
        }
        $plus = $a + $b;
        $ans =  ((($plus >> 32) % MOD) << 32) + ($plus & $this->max);
        return $ans;
    }

    /**
     * 恒等写像 identity
     * 更新時要素aに作用させても値が変化しない
     * さらに、すでにある遅延評価の重ね合わせに作用させても変化しない
     * mapping(identity, a) = a -> tree[a] は変化しない
     * composition(identity, id) -> lz[id] は変化しない
     * @var mixed
     */
    private $identity = -1;

    /**
     * 配列id番目のlazyをdataに作用させる関数 mapping
     *
     * @param int $id //作用させるid
     * @return void
     */
    private function mapping(int $id):void
    {
        if($this->lz[$id] === $this->identity) return; //書き換えない

        ///////////////////////////////////////
        /// ここから書き換える
        ///////////////////////////////////////
        //lzを適用する
        $lza = $this->lz[$id] >> 32;
        $lzb = $this->lz[$id] & $this->max;
        $tra = $this->tree[$id] >> 32;
        $trb = $this->tree[$id] & $this->max;
        $this->tree[$id] = (((($lza * $tra) % MOD + (($lzb * $trb) % MOD)) % MOD) << 32) + $trb ;
        //下に配るlz
        $next_lazy = $this->lz[$id];
        ///////////////////////////////////////
        /// ここまで書き換える
        ///////////////////////////////////////
        //lzを下に配る
        if ($id < $this->leaf_size) {
            $this->composition($next_lazy, $id * 2);  //書き換えない
            $this->composition($next_lazy, $id * 2 + 1);  //書き換えない
        }
        $this->lz[$id] = $this->identity;  //書き換えない
    }

    /**
     * lazyの重ね合わせ関数 composition
     * lazyを、既存のlazyを作用させて、続いてadd_lazyを作用させるよう書き換える
     *
     * @param mixed $add_lazy
     * @param int $id
     * @return void
     */
    private function composition($add_lazy, int $id):void
    {
        if($add_lazy === -1){
            return;
        }
        if($this->lz[$id] === -1){
            $this->lz[$id] = $add_lazy;
            return;
        }
        $lza = $this->lz[$id] >> 32;
        $lzb = $this->lz[$id] & $this->max;
        $adda = $add_lazy >> 32;
        $addb = $add_lazy & $this->max;
        $this->lz[$id] = ((($lzb * $adda + $addb) % MOD) + (($lza * $adda % MOD) << 32));
    }

    ///////////////////////////////////////////////////////
    ///
    /// ここまで書き換える
    ///
    ///////////////////////////////////////////////////////

    /**
     * tree[id]に何要素分のデータが入っているかを取得する
     *
     * @param int $id
     * @return int
     */
    public function element_count(int $id):int
    {
        return 2 << ($this->height - $this->ceil_log2($id + 1));
    }

    /**
     * LazySegmentTree constructor
     * 配列は0-indexed
     *
     * @param int|array $sv int 配列の数 | array 初期配列
     */
    function __construct($sv)
    {
        if(gettype($sv) === "integer"){
            $count = $sv;
            $list = array_fill(0, $count, $this->e);
        }elseif(gettype($sv) === "array"){
            $count = count($sv);
            $list = $sv;
        }else{
            $count = 0;
            $list = [];
        }
        $this->height = $this->ceil_log2($count);
        $this->leaf_size = 1 << $this->height;
        $this->tree_size = $this->leaf_size * 2 - 1;
        $this->lz = array_fill(0, $this->tree_size + 1, $this->identity);
        $this->tree = array_merge(array_fill(0,$this->leaf_size, $this->e),$list, array_fill(0, $this->leaf_size - $count, $this->e));
        for($i=$this->leaf_size-1; $i>=1;$i--){
            $this->tree[$i] = $this->op($this->tree[$i * 2], $this->tree[$i * 2 + 1]);
        }
    }

    /**
     * 区間[a, b)をxで更新する
     * 更新はcomposition(x, lz[id])
     * 0-indexed
     *
     * @param int $a
     * @param int $b
     * @param mixed $x
     * @return void
     */
    public function update(int $a, int $b, $x){
        $l = $a + $this->leaf_size;
        $r = $b + $this->leaf_size - 1;
        for($i = $this->height; $i>0; $i--){
            if ((($l >> $i) << $i ) !== $l) $this->mapping($l >> $i);
            if (((($r - 1) >> $i) << $i ) !== $r) $this->mapping(($r) >> $i);
        }
        for($r++; $l < $r; $l >>=1, $r >>= 1)
        {
            if($l & 1) {
                $this->composition($x, $l);
                $this->mapping($l);
                $l++;
            }
            if($r & 1){
                --$r;
                $this->composition($x, $r);
                $this->mapping($r);
            }
        }
        $l = $a + $this->leaf_size;
        $r = $b + $this->leaf_size - 1;
        for($i =1; $i<= $this->height;$i++){
            $lt = $l >> $i;
            if (($lt << $i ) !== $l) {
                $this->mapping($lt * 2);
                $this->mapping($lt * 2 + 1);
                $this->tree[$lt] = $this->op($this->tree[$lt * 2], $this->tree[$lt * 2 + 1]);
            }
            $rt = $r >> $i;
            if (((($r - 1) >> $i) << $i ) !== $r) {
                $this->mapping($rt * 2);
                $this->mapping($rt * 2 + 1);
                $this->tree[$rt] = $this->op($this->tree[$rt * 2], $this->tree[$rt * 2 + 1]);
            }
        }
    }

    /**
     * [a, b)の値を取得する
     *
     * @param int $a
     * @param int $b
     * @return mixed
     */
    public function get(int $a, int $b)
    {
        $l = $a + $this->leaf_size;
        $r = $b + $this->leaf_size - 1;
        for($i = $this->height; $i >0; $i--){
            if ((($l >> $i) << $i ) !== $l) $this->mapping($l >> $i);
            if (((($r - 1) >> $i) << $i ) !== $r) $this->mapping(($r) >> $i);
        }
        $vl = $this->e;
        $vr = $this->e;
        for($r++; $l < $r; $l >>=1, $r >>= 1)
        {
            if($l & 1) {
                $this->mapping($l);
                $vl = $this->op($vl, $this->tree[$l]);
                $l++;
            }
            if($r & 1){
                --$r;
                $this->mapping($r);
                $vr = $this->op($this->tree[$r], $vr);
            }
        }
        return $this->op($vl, $vr);
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


