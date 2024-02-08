<?php

/**
 * Class WaveletMatrix
 *
 * 完備辞書を用いないWavelet行列もどき
 *
 * @version 1.0
 * @author dicecu
 * @link https://github.com/dicecu/Competitive_programming
 * @license CC0-1.0
 */
class WaveletMatrix
{
    private int $msb;
    private int $max_bit_value;
    private int $count;
    private array $initial_array;
    private array $sorted_array;
    private array|\FFI\CData $w_array;
    private array|\FFI\CData $w_sum_array;
    private array $w_border;
    private array $w_start_pos;
    private bool $with_range_sum;


    /**
     * 1-indexed
     *
     * rank(n, end) [1, end]で n が出現する回数を数える
     * rank_range(n, from, end) [from, end]で n が出現する回数を数える
     * select(n, k) k番目の n の座標を求める
     * kth_largest(k, from, to) [from, to]で k番目に大きい数を返す
     * kth_smallest(k, from, to) [from, to]で k番目に小さい数を返す
     * top_k(k, from, to) [from, to]で出現頻度上位 k位を頻度とともに返す
     * sum(from, to) [from to]の合計を求める
     * sum_range_n_range(n_from, n_to, from, to) [from, to]の範囲内にある[n_from, n_to]に含まれる値の合計を求める
     * rank_range_n_range(n_from, n_to, from, to) [from, to]の範囲内にある[n_from, n_to]に含まれる値の個数を求める
     * less_than(n, from, to) [from, to]の範囲内にある n 以下の最大値を求める
     * more_than(n, from, to) [from, to]の範囲内にある n 以上の最小値を求める
     *
     * @param array $array Wavelet行列を計算する配列
     * @param int $max  $arrayの最大値 (Optional)
     * @param bool $with_range_sum  範囲和を求める前計算を行うかどうか **∑array < 64 bit** (Optional, default: true)
     * @param bool $with_FFI 配列にFFIを用いるかどうか 大きな数を扱うとき省メモリになる場合がある (Optional, default: false)
     */
    function __construct(array $array, int $max = -1, bool $with_range_sum = true, bool $with_FFI = false)
    {
        if($max === -1){
            $max = max($array);
        }
        $this->msb = $this->calc_msb($max + 1);
        $this->max_bit_value = 1 << ($this->msb - 1);
        $this->count = count($array);
        $this->initial_array = $array;
        /**
         * w_array[i] は　最上位bit から数えてi番目のビットに対し、1となる要素の累積和になっている
         * value   | 1 | 6 | 2 | 5 | 3 | 4 |
         * [0] | 0 | 0 | 1 | 1 | 2 | 2 | 3 | ** & 4 の累積和
         * ** sort | 1 | 2 | 3 ||6 | 5 | 4 | ** 実際には保存されていない with_range_sum=true の時、累積和が保存される (以下同様)
         * [1] | 0 | 0 | 1 | 2 | 3 | 3 | 3 | ** & 2 の累積和
         * sort    | 1 | 5 | 4 ||2 | 3 | 6 | ** 実際には保存されていない
         * [2] | 0 | 1 | 2 | 2 | 2 | 3 | 3 | ** & 1 の累積和
         *
         * sorted_array
         *         | 4 | 2 | 6 ||1 | 5 | 3 |
         *
         */

        if($with_FFI){
            // FFIを用いると省メモリ
            $this->w_array = \FFI::new("unsigned int[{$this->msb}][".($this->count + 1) ."]");
        }else{
            // N = 10**6, MAX = 10**18 等の条件でMLEになる
            $this->w_array = array_fill(0, $this->msb, array_fill(0, $this->count+1, 0));
        }
        if($with_range_sum){
            $this->with_range_sum = true;
            // 各bit列毎に累積和を保存する
            if($with_FFI){
                $this->w_sum_array = \FFI::new("long long[". ($this->msb + 1) ."][".($this->count + 1) ."]");
            }else{
                $this->w_sum_array = array_fill(0, $this->msb+1, array_fill(0, $this->count+1, 0));
            }
        }else{
            $this->with_range_sum = false;
            $this->w_sum_array = [];
        }

        $this->w_border = array_fill(0, $this->msb, 0);
        $this->w_start_pos = [];
        $this->calc_wavelet();
    }

    private function calc_wavelet():void
    {
        $bit = $this->max_bit_value;
        $target = $this->initial_array;
        foreach($this->w_array as $i => &$w_array){
            if($this->with_range_sum){
                $range_sum_array = &$this->w_sum_array[$i];
                $range_sum_array[0] = 0;
            }
            $range_sum = 0;
            $one_array = [];
            $next_target = [];
            $cu_sum = 0;
            foreach($target as $j => $value){
                if(($value & $bit) > 0){
                    $one_array[] = $value;
                    $cu_sum++;
                }else{
                    $next_target[] = $value;
                }
                if($this->with_range_sum){
                    $range_sum += $value;
                    $range_sum_array[$j + 1] = $range_sum;
                }
                $w_array[$j + 1] = $cu_sum;
            }
            $target = $next_target;
            array_push($target, ...$one_array);
            $this->w_border[$i] = $this->count - count($one_array);
            $bit >>= 1;
        }
        unset($w_array);
        $last = "";
        $range_sum = 0;
        if($this->with_range_sum){
            $range_sum_array = &$this->w_sum_array[$this->msb];
            $range_sum_array[0] = 0;
        }
        foreach($target as $i => $value){
            if($this->with_range_sum){
                $range_sum += $value;
                $range_sum_array[$i + 1] = $range_sum;
            }
            if($value !== $last){
                $this->w_start_pos[$value] = $i + 1;
                $last = $value;
            }
        }
        $this->sorted_array = $target;
    }


    /**
     * [1, end]で　n　が出現する回数を数える
     * 1-indexed
     *
     * @param int $n 探索する値
     * @param int|null $end 探索範囲 (nullの時、全範囲)
     * @return mixed
     */
    public function rank(int $n, int|null $end=null):int
    {
        if($end === null || $end > $this->count){
            $end = $this->count;
        }
        if(!isset($this->w_start_pos[$n])){
            return 0;
        }
        $t = $this->max_bit_value;
        for($i=0;$i<$this->msb;$i++){
            $target = $t & $n;
            $end = $this->_rank($i, $end, $target);
            if($end === 0){
                return 0;
            }
            $t >>= 1;
        }
        return $end - $this->w_start_pos[$n] + 1;
    }

    private function _rank(int $level, int $end, bool $target): int
    {
        if($end === 0){
            return ($target === true ? $this->w_border[$level] : 0);
        }
        if($target){
            return $this->w_border[$level] + $this->w_array[$level][$end];
        }else{
            return $end - $this->w_array[$level][$end];
        }
    }

    /**
     * [from, to] で n が出現する回数を数える
     * 1-indexed
     *
     * @param int $n
     * @param int $from
     * @param int|null $to
     * @return int
     */
    public function rank_range(int $n, int $from, int|null $to=null):int
    {
        if($from === 1){
            return $this->rank($n, $to);
        }else{
            return $this->rank($n, $to) - $this->rank($n, $from-1);
        }
    }

    /**
     * k個目のnの座標を求める
     * そのような数が存在しない場合、-1を返す
     * 存在する場合、1-indexedで先頭か何個目かを返す
     * O(log(n) * log(σ))
     *
     * @param int $n
     * @param int $k
     * @return int
     */
    function select(int $n, int $k):int
    {
        if(!isset($this->w_start_pos[$n])){
            return -1;
        }
        $pos = $this->w_start_pos[$n] + $k - 1;

        if($pos > $this->count || $this->sorted_array[$pos-1] !== $n){
            return -1;
        }
        $bit = 1;
//        $pos = $k;
        for($i=$this->msb-1;$i>=0;$i--){
            $target = $bit & $n;
            if($target){
                $pos -= $this->w_border[$i];
            }
            $pos = $this->_select($i, $target, $pos);
            $bit <<= 1;

        }
        return $pos;
    }

    /**
     * O(log(n))のselect
     *
     * @param int $level
     * @param int $target
     * @param int $k
     * @return int
     */
    private function _select(int $level, int $target, int $k):int
    {
        $min = $k-1;
        $max = $this->count;
        $w_array = &$this->w_array[$level];
        if($target){
            while($max-$min > 1){
                $mid = $min + intdiv($max - $min, 2);
                if($w_array[$mid] < $k){
                    $min = $mid;
                }else{
                    $max = $mid;
                }
            }
        }else{
            while($max-$min > 1){
                $mid = $min + intdiv($max - $min, 2);
                if($mid - $w_array[$mid] < $k){
                    $min = $mid;
                }else{
                    $max = $mid;
                }
            }
        }
        return $max;
    }

    /**
     * [from, to] でk番目に小さい数を返す
     * 1-indexed
     *
     * @param int $k
     * @param int $from
     * @param int $to
     * @return int k番目に小さい数
     */
    private function _quantile(int $k, int $from, int $to):int
    {
        $from--;
        for($i=0;$i<$this->msb;$i++){
            $rank_zero_to = $this->_rank($i, $to, false);
            $rank_zero_from = $this->_rank($i, $from, false);
            $rank_zero = $rank_zero_to - $rank_zero_from;
            $next_target = ($k > $rank_zero);
            if($next_target){
                $k -= $rank_zero;
            }
            if($next_target){
                $from = $this->w_border[$i] + ($from - $rank_zero_from);
                $to =  $this->w_border[$i] + ($to - $rank_zero_to);
            }else{
                $from = $rank_zero_from;
                $to = $rank_zero_to;
            }

        }
        return $this->sorted_array[$from + $k - 1];
    }


    /**
     * [from, to]でk番目に大きい数を返す
     * 1-indexed
     *
     * @param int $k
     * @param int $from
     * @param int $to
     * @return int
     */
    public function kth_largest(int $k, int $from, int $to):int
    {
        return $this->_quantile($to - $from + 2 - $k, $from, $to);
    }



    /**
     * [from, to]でk番目に小さい数を返す
     * 1-indexed
     *
     * @param int $k
     * @param int $from
     * @param int $to
     * @return int
     */
    public function kth_smallest(int $k, int $from, int $to):int
    {
        return $this->_quantile($k, $from, $to);
    }


    /**
     * [from, to]で出現頻度上位k位までを求める
     * 1-indexed
     *
     * @param int $k
     * @param int $from
     * @param int $to
     * @return array [$value, $freq][]
     */
    public function top_k(int $k, int $from, int $to):array
    {
        $Que = new SplPriorityQueue();
        $Que->insert([$from, $to, 0], $to - $from + 1);

        $top_k = [];
        while(!$Que->isEmpty()){
            [$from, $to, $level] = $Que->extract();
            if($level < $this->msb){
                $w_one_from = $this->w_array[$level][$from - 1];
                $w_one_to = $this->w_array[$level][$to];
                $w_one = $w_one_to - $w_one_from;
                $w_zero = $to - $from + 1 - $w_one;
                $w_zero_from = $from - 1 - $w_one_from;
                $next_zero_from = $w_zero_from + 1;
                $next_zero_to = $next_zero_from + $w_zero - 1;
                $next_one_from = $this->w_border[$level] + $w_one_from + 1;
                $next_one_to = $next_one_from + $w_one - 1;
                if($w_zero > 0){
                    $Que->insert([$next_zero_from, $next_zero_to, $level + 1], $w_zero);
                }
                if($w_one > 0){
                    $Que->insert([$next_one_from, $next_one_to, $level + 1], $w_one);
                }
            }else{
                $top_k[] = [$this->sorted_array[$from-1], $to - $from + 1];
                $k--;
                if($k === 0){
                    break;
                }
            }
        }
        return $top_k;
    }

    /**
     * [from, to]の合計を求める
     *
     * @param int $from
     * @param int $to
     * @return int
     */
    public function sum(int $from, int $to):int
    {
//        $ANS = 0;
//        foreach($this->top_k(PHP_INT_MAX, $from, $to) as [$number, $count]){
//            $ANS += $number * $count;
//        }
//        return $ANS;
        return $this->sum_range_n_range(0, PHP_INT_MAX, $from, $to);
    }

    /**
     * [from, to]の範囲内で[n_from, n_to]に含まれる値の合計値を求める
     * 1-indexed
     *
     * @param int $n_from
     * @param int $n_to
     * @param int $from
     * @param int|null $to
     * @return int
     */
    public function sum_range_n_range(int $n_from, int $n_to, int $from, int|null $to=null):int
    {
        assert($this->with_range_sum);
        return $this->_sum_range_upper($n_to, $from-1, $to)
            - $this->_sum_range_upper($n_from - 1,  $from-1, $to);
    }

    private function _sum_range_upper(int $n_to, int $from, int|null $to):int
    {
        if($to === null || $to > $this->count){
            $to = $this->count;
        }
        if($n_to > $this->max_bit_value * 2 -1 ){
            $n_to =  $this->max_bit_value * 2 -1;
        }
        if($n_to <= 0){
            return 0;
        }

        $t = $this->max_bit_value;
        $eq_smaller = 0;
        $bigger = 0;
        for($i=0;$i<$this->msb;$i++){
            $target = $t & $n_to;
            if($target){
                $eq_smaller += $this->w_sum_array[$i+1][$this->_rank($i, $to, 0)]
                    - $this->w_sum_array[$i+1][$this->_rank($i, $from, 0)];
            }else{
//                $bigger += $this->w_array[$i][$to];
            }
            $from = $this->_rank($i, $from, $target);
            $to = $this->_rank($i, $to, $target);
            if($to < $from){
                return $eq_smaller;
            }
            $t >>= 1;
        }
        $eq_smaller += $this->w_sum_array[$this->msb][$to] - $this->w_sum_array[$this->msb][$from];
        return $eq_smaller;
    }

    /**
     * [from, to]の範囲内にある[n_from, n_to]に含まれる値の個数を求める
     * 1-indexed
     *
     * @param int $n_from
     * @param int $n_to
     * @param int $from
     * @param int|null $to
     * @return int
     */
    public function rank_range_n_range(int $n_from, int $n_to, int $from, int|null $to):int
    {
        return $this->_rank_range_upper($n_to, $from-1, $to)
            - $this->_rank_range_upper($n_from - 1,  $from-1, $to);
    }

    private function _rank_range_upper(int $n_to, int $from, int|null $to):int
    {
        if($to === null || $to > $this->count){
            $to = $this->count;
        }
        if($n_to > $this->max_bit_value * 2 -1 ){
            $n_to =  $this->max_bit_value * 2 -1;
        }
        if($n_to < 0){
            $n_to = 0;
        }

        $t = $this->max_bit_value;
        $eq_smaller = 0;
//        $bigger = 0;
        for($i=0;$i<$this->msb;$i++){
            $target = $t & $n_to;
            if($target){
                $eq_smaller += $to - $this->w_array[$i][$to] - ($from - $this->w_array[$i][$from]);

            }else{
//                $bigger += $this->w_array[$i][$to];
            }
            $from = $this->_rank($i, $from, $target);
            $to = $this->_rank($i, $to, $target);
            if($to < $from){
                return $eq_smaller;
            }
            $t >>= 1;
        }
        $eq_smaller += $to - $from;
        return $eq_smaller;
    }

    /**
     * [from, to]の範囲内にある n 以下の最大値を求める
     * 1-indexed
     *
     * @param int $n
     * @param int $from
     * @param int $to
     * @return int
     */
    public function less_than(int $n, int $from, int $to):int
    {
        $n = min($n, $this->max_bit_value * 2 -1);
        $from--;
        $bit = $this->max_bit_value;
        $eq_from = $from;
        $eq_to = $to;
        $enough_small = false;
        $small_from = 0;
        $small_to = 0;
        $eq_value = 0;
        $small_value = 0;
        for($i=0;$i<$this->msb;$i++) {
            $target = $bit & $n;
            $one_count = $this->w_array[$i][$eq_to] - $this->w_array[$i][$eq_from];
            $zero_count = $eq_to - $eq_from - $one_count;
            $next_eq_from = $next_eq_to = 0;
            $next_small_from = $next_small_to = 0;
            if ($target || $enough_small) {
                if ($zero_count > 0) {
                    $next_small_from = $eq_from - $this->w_array[$i][$eq_from];
                    $next_small_to = $next_small_from + $zero_count;
                    $small_value = $eq_value;
                }
                if ($one_count > 0) {
                    $next_eq_from = $this->w_border[$i] + $this->w_array[$i][$eq_from];
                    $next_eq_to = $next_eq_from + $one_count;
                    $eq_value += $bit;
                } else {
                    $next_eq_from = $next_small_from;
                    $next_eq_to = $next_small_to;
                    $enough_small = true;
                }
            } else {
                if ($zero_count > 0) {
                    $next_eq_from = $eq_from - $this->w_array[$i][$eq_from];
                    $next_eq_to = $next_eq_from + $zero_count;
                }
            }
            if ($next_small_to === 0) {
                $one_count = $this->w_array[$i][$small_to] - $this->w_array[$i][$small_from];
                $zero_count = $small_to - $small_from - $one_count;
                if ($one_count > 0) {
                    $next_small_from = $this->w_border[$i] + $this->w_array[$i][$small_from];
                    $next_small_to = $next_small_from + $one_count;
                    $small_value += $bit;
                } elseif ($zero_count > 0) {
                    $next_small_from = $small_from - $this->w_array[$i][$small_from];
                    $next_small_to = $next_small_from + $zero_count;
                }
            }
            $small_from = $next_small_from;
            $small_to = $next_small_to;
            $eq_from = $next_eq_from;
            $eq_to = $next_eq_to;
            $bit >>= 1;
        }
        if($eq_to > 0){
            return $eq_value;
        }elseif($small_to > 0){
            return $small_value;
        }else{
            return 0;
        }
    }

    /**
     * [from, to]の範囲内にある n 以上の最小値を求める
     * 1-indexed
     *
     * @param int $n
     * @param int $from
     * @param int $to
     * @return int
     */
    public function more_than(int $n, int $from, int $to):int
    {
        if($n > $this->max_bit_value * 2 -1){
            return 0;
        }
//        $n = min($n, $this->max_bit_value * 2 -1);
        $from--;
        $bit = $this->max_bit_value;
        $eq_from = $from;
        $eq_to = $to;
        $enough_large = false;
        $large_from = 0;
        $large_to = 0;
        $eq_value = 0;
        $large_value = 0;
        for($i=0;$i<$this->msb;$i++) {
            $target = $bit & $n;
            $one_count = $this->w_array[$i][$eq_to] - $this->w_array[$i][$eq_from];
            $zero_count = $eq_to - $eq_from - $one_count;
            $next_eq_from = $next_eq_to = 0;
            $next_large_from = $next_large_to = 0;
            if (!$target || $enough_large) {
                if ($one_count > 0) {
                    $next_large_from = $this->w_border[$i] + $this->w_array[$i][$eq_from];
                    $next_large_to = $next_large_from + $one_count;
                    $large_value = $eq_value + $bit;
                }
                if ($zero_count > 0) {
                    $next_eq_from = $eq_from - $this->w_array[$i][$eq_from];
                    $next_eq_to = $next_eq_from + $zero_count;
                } else {
                    $next_eq_from = $next_large_from;
                    $next_eq_to = $next_large_to;
                    $enough_large = true;
                    $eq_value += $bit;
                }
            } else {
                if ($one_count > 0) {
                    $next_eq_from =  $this->w_border[$i] + $this->w_array[$i][$eq_from];
                    $next_eq_to = $next_eq_from + $one_count;
                    $eq_value += $bit;
                }
            }
            if ($next_large_to === 0) {
                $one_count = $this->w_array[$i][$large_to] - $this->w_array[$i][$large_from];
                $zero_count = $large_to - $large_from - $one_count;
                if ($zero_count > 0) {
                    $next_large_from = $large_from - $this->w_array[$i][$large_from];
                    $next_large_to = $next_large_from + $zero_count;
                } elseif ($one_count > 0) {
                    $next_large_from = $this->w_border[$i] + $this->w_array[$i][$large_from];
                    $next_large_to = $next_large_from + $one_count;
                    $large_value += $bit;
                }
            }
            $large_from = $next_large_from;
            $large_to = $next_large_to;
            $eq_from = $next_eq_from;
            $eq_to = $next_eq_to;
            $bit >>= 1;
        }
        if($eq_to > 0){
            return $eq_value;
        }elseif($large_to > 0){
            return $large_value;
        }else{
            return 0;
        }

    }


    /**
     * @param int $x
     * @return int
     */
    private function calc_msb(int $x):int
    {
        $x |= ($x >> 1);
        $x |= ($x >> 2);
        $x |= ($x >> 4);
        $x |= ($x >> 8);
        $x |= ($x >> 16);
        $x |= ($x >> 32);
        return $this->popcount($x);
    }

    /**
     * @param int $x
     * @return int
     */
    private function popcount(int $x):int
    {
        return (int)gmp_popcount($x);
    }

}
