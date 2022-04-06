<?php

/**
 * Class PseudoMultiSet
 *
 * Multisetっぽいことをそこそこの速さでできるクラス
 *
 * @version 2.0
 * @author dicecu
 * @link https://github.com/dicecu/Competitive_programming
 * @license CC0-1.0
 */
class PseudoMultiSet // implements Countable, ArrayAccess, Iterator
{
    private int $max_size = 100;
    private int $rebuild_max_size = 300;
    private int $count;
    private array $tree;
    private array $BIT;
    private array $BIT_lazy;
    private array $pointer;
    private bool $BIT_broken = true;

    /**
     * PseudoMultiSet constructor
     *
     * 引数にソート済みの配列を与えると、高速に代入する。
     * ソート済みでない配列が与えられる可能性は考慮されていない。
     *
     * @param array $sorted_array
     */
    function __construct(array $sorted_array=[])
    {
        $this->tree = [];
        $this->BIT_lazy = [];
        $end_offset = count($sorted_array);
        if($end_offset > 0){
            $this->tree = array_chunk($sorted_array, $this->max_size);
        }else{
            $this->tree[] = $this->create_new_block();
        }
        $this->count = $end_offset;
    }

    /**
     * 現在のポインタの示す座標を返す
     * O(1)
     *
     * @return array
     */
    public function get_pointer():array
    {
        return $this->pointer;
    }

    /**
     * 与えられたポインタをセットする。
     * 引数のチェックは行わない。
     * O(1)
     *
     * @param array $pointer
     * @return $this
     */
    public function set_pointer(array $pointer):self
    {
        $this->pointer = $pointer;
        return $this;
    }

    /**
     * ポインタが示すキーを取得する
     * 存在しない場合nullを返す。
     * O(1)
     *
     * @return mixed|null
     */
    public function get()
    {
        if($this->pointer[0] === null || $this->pointer[1] === null){
            return null;
        }
        return $this->tree[$this->pointer[0]][$this->pointer[1]] ?? null;
    }

    /**
     * 値を追加する
     * O(logN) - O(N)
     *
     * @param $value
     * @return $this
     */
    public function add($value): self
    {
        $this->pointer = [null, null];
        $this->_add($this->_upper_bound($value), $value);
        return $this;
    }

    /**
     * 現在のポインタが示す要素を削除する
     * O(logN) - O(N)
     *
     * @return $this
     */
    public function erase():self
    {
        if($this->pointer[0] !== null && $this->pointer[1] !== null){
            $this->_erase($this->pointer);
        }
        if(count($this->tree[$this->pointer[0]]) === 0){
            $this->rebuild($this->pointer[0]);
        }

        $this->pointer = [null, null];
        return $this;
    }

    /**
     * [from, to] の範囲に含まれる全データを削除する
     *
     * @param $from
     * @param $to
     * @return $this
     */
    public function erase_range($from, $to): self
    {
        if($from > $to){
            return $this;
        }
        $from_pointer = $this->_lower_bound($from);
        $to_pointer = $this->_upper_bound($to);
        $this->_erase_range($from_pointer, $to_pointer, $this->count_range($from, $to));
        return $this;
    }

    /**
     * 指定した値を検索し、ポインタをセットする
     * 指定した値が存在しない場合、[null, null]がセットされる。
     * valueに合致するデータが複数ある場合、最も手前に座標がセットされる
     * O(logN)
     *
     * @param int $value
     * @return $this
     */
    public function find(int $value):self
    {
        $iterator = $this->_lower_bound($value);
        if(isset($this->tree[$iterator[0]][$iterator[1]]) && $this->tree[$iterator[0]][$iterator[1]] === $value){
            $this->pointer = $iterator;
        }else{
            $this->pointer = [null, null];
        }
        return $this;
    }

    /**
     * ポインタを指定したターゲット以上の最小の要素にセットする
     * O(logN)
     *
     * @param int $target
     * @return $this
     */
    public function lower_bound(int $target):self
    {
        $iterator = $this->_lower_bound($target);
        $this->pointer = $iterator;
        return $this;
    }

    /**
     * ポインタを指定したターゲットより大きい最小の要素にセットする
     * O(logN)
     *
     * @param int $target
     * @return $this
     */
    public function upper_bound(int $target):self
    {
        $iterator = $this->_upper_bound($target);
        $this->pointer = $iterator;
        return $this;
    }

    /**
     * 小さいほうか数えてrank番目に小さい数を返す
     * 最小値はrank(0)、最大値はrank(count()-1)
     * O(logN) - O(N)
     *
     * @param $rank
     * @return $this
     */
    public function rank($rank): self
    {
        $target = $this->lower_bound_BIT($rank+1);
        $this->pointer = [$target[0], $rank - $target[1]];
        return $this;
    }

    /**
     * 指定した値の出現回数を数える
     * O(logN) - O(N)
     *
     * @param $target
     * @return int
     */
    public function count_value($target): int
    {
        $left = $this->_lower_bound($target);
        $right = $this->_upper_bound($target);
        return $this->count_pointer($left, $right);
    }

    /**
     * [from, to]に含まれる数を数える
     * O(logN) - O(N)
     *
     * @param int $from
     * @param int $to
     * @return int
     */
    public function count_range(int $from, int $to): int
    {
        $left = $this->_lower_bound($from);
        $right = $this->_upper_bound($to);
        return $this->count_pointer($left, $right);
    }

    /**
     * 二つのpointerの間に存在する要素の個数[from, to)を返す
     * O(logN) - O(N)
     *
     * @param array $from get_pointer()で得られる配列
     * @param array $to get_pointer()で得られる配列
     * @return int
     */
    public function count_pointer(array $from, array $to): int
    {
        if($from === $to){
            return 0;
        }else{
            $count = $this->count_BIT($from[0], $to[0]);
            $count -= $from[1] + (count($this->tree[$to[0]]) - $to[1]);
            return $count;
        }
    }

    /**
     * ポインタを一つ進める。
     * 最終要素からさらに進め、get()した場合nullを返す
     * O(1)
     *
     * @return $this
     */
    public function next():self
    {
        $this->pointer = $this->_next($this->pointer);
        return $this;
    }

    /**
     * ポインタを一つ戻す。
     * 最終要素からさらに進め、get()した場合nullを返す
     * O(1)
     *
     * @return $this
     */
    public function prev()
    {
        $this->pointer = $this->_prev($this->pointer);
        return $this;
    }

    /**
     * ポインタを先頭(最小値)にセットする
     * O(1)
     *
     * @return $this
     */
    public function begin():self
    {
        $this->pointer = [0,0];
        return $this;
    }

    /**
     * ポインタを末尾(最大値)にセットする
     * O(1)
     *
     * @return $this
     */
    public function end():self
    {
        $last = array_key_last($this->tree);
        $this->pointer = [$last, count($this->tree[$last])-1];
        return $this;
    }

    /**
     * 登録されている要素の数を数える
     * O(1)
     *
     * @return int
     */
    public function count(): int
    {
        return $this->count;
    }

    public function offsetExists($offset): bool
    {
        if(is_int($offset)){
            if($this->count > $offset && $offset >= 0){
                return true;
            }
        }
        return false;
    }

    public function offsetGet($offset)
    {
        return $this->rank($offset)->get();
    }

    public function offsetSet($offset, $value): void
    {
        if($offset === null){
            $this->add($value);
        }elseif($this->offsetExists($offset)){
            $this->rank($offset)->erase()->add($value);
        }
    }

    public function offsetUnset($offset): void
    {
        $this->rank($offset)->erase();
    }

    public function current(): int
    {
        return $this->get();
    }

    public function key(): int
    {
        if($this->pointer[0] === 0){
            return $this->pointer[1];
        }else{
            return $this->count_BIT(0, $this->pointer[0] - 1) + $this->pointer[1];
        }
    }

    public function rewind(): void
    {
        $this->begin();
    }

    public function valid(): bool
    {
        return isset($this->tree[$this->pointer[0]][$this->pointer[1]]);
    }

    private function _add($pointer, $value): void
    {
        [$block_id, $address] = $pointer;
        $this->add_BIT_lazy($block_id, 1);
        $block = &$this->tree[$block_id];
        if($address === 0){
            array_unshift($block, $value);
        }elseif($address === count($block)){
            $block[] = $value;
        }elseif(count($block) - $address < 50){
            for($i=count($block);$i>$address;$i--){
                $block[$i] = $block[$i-1];
            }
            $block[$address] = $value;
        }else{
            $block = array_merge(array_slice($block, 0, $address), [$value], array_slice($block, $address));
        }
        if(count($block) === $this->rebuild_max_size){
            $this->rebuild($block_id);
        }
        $this->count++;
    }

    private function _erase_range($from_pointer, $to_pointer, $count): void
    {
        if($from_pointer === $to_pointer){
            return;
        }
        $to_pointer = $this->_prev($to_pointer);
        if($from_pointer[0] === $to_pointer[0]){
            if($from_pointer[1] > $to_pointer[1]){
                return;
            }
            $block = &$this->tree[$from_pointer[0]];
            if($from_pointer[1] === 0){
                $block = array_slice($block, $to_pointer[1] + 1);
            }else{
                $block = array_merge(array_slice($block, 0, $from_pointer[0]), array_slice($block, $to_pointer[1] + 1));
            }
        }else{

            $rebuild = [];
            if($from_pointer[1] === 0){
                $rebuild[0] = $from_pointer[0];
            }else{
                $block = &$this->tree[$from_pointer[0]];
                $block = array_slice($block, 0, $from_pointer[1]);
                $rebuild[0] = $from_pointer[0] + 1;
            }
            if($to_pointer[1] === count($this->tree[$to_pointer[0]]) -1){
                $rebuild[1] = $to_pointer[0];
            }else{
                $block = &$this->tree[$to_pointer[0]];
                $block = array_slice($block, $to_pointer[1] + 1);
                $rebuild[1] = $to_pointer[0] - 1;
            }
            if($rebuild[1] >= $rebuild[0]){
                $this->tree = array_merge(array_slice($this->tree, 0, $rebuild[0]), array_slice($this->tree, $rebuild[1] + 1));
            }
        }
        $this->BIT_broken = true;
        $this->count -= $count;
    }

    private function _erase($pointer): void
    {
        [$block_id, $address] = $pointer;
        $this->add_BIT_lazy($block_id, -1);
        $block = &$this->tree[$block_id];
        if($address === 0){
            array_shift($block);
        }elseif($address === count($block) - 1){
            array_pop($block);
        }else{
            $block = array_merge(array_slice($block,0,$address),array_slice($block,$address+1));
        }
        $this->count--;
    }

    private function _lower_bound($target): array
    {
        $min = -1;
        $max = count($this->tree) - 1;
        while($max - $min > 1){
            $center = $min + intdiv($max - $min, 2);
            if($this->tree[$center][count($this->tree[$center]) - 1] >= $target){
                $max = $center;
            }else{
                $min = $center;
            }
        }
        $block_id = $max;
        $block = &$this->tree[$block_id];
        [$min, $max] = [-1, count($block)];
        while($max - $min > 1){
            $center = $min + intdiv($max - $min, 2);
            if($block[$center] >= $target){
                $max = $center;
            }else{
                $min = $center;
            }
        }
        return [$block_id, $max];
    }

    private function _upper_bound($target): array
    {
        $min = -1;
        $max = count($this->tree) -1;
        while($max - $min > 1){
            $center = $min + intdiv($max - $min, 2);
            if($this->tree[$center][count($this->tree[$center]) - 1] > $target){
                $max = $center;
            }else{
                $min = $center;
            }
        }
        $block_id = $max;
        $block = &$this->tree[$block_id];
        [$min, $max] = [-1, count($block)];
        while($max - $min > 1){
            $center = $min + intdiv($max - $min, 2);
            if($block[$center] > $target){
                $max = $center;
            }else{
                $min = $center;
            }
        }
        return [$block_id, $max];
    }

    private function _next(array $pointer): array
    {

        if($pointer[0] === null || $pointer[1] === null){
            $pointer = [null, null];
            return $pointer;
        }
        if(count($this->tree[$pointer[0]]) > $pointer[1] + 1){
            $pointer[1]++;
        }else{
            if(isset($this->tree[$pointer[0] + 1])){
                $pointer[0]++;
                $pointer[1] = 0;
            }else{
                $pointer = [null, null];
            }
        }
        return $pointer;
    }

    private function _prev(array $pointer): array
    {
        if($pointer[0] === null || $pointer[1] === null){
            $pointer = [null, null];
            return $pointer;
        }
        if($pointer[1] > 0){
            $pointer[1]--;
        }else{
            if($pointer[0] === 0){
                $pointer = [null, null];
            }else{
                $pointer[0]--;
                $pointer[1] = count($this->tree[$pointer[0]])-1;
            }
        }
        return $pointer;

    }

    private function create_new_block():array
    {
        return [];
    }


    private function rebuild($block_id): void
    {
        $block = &$this->tree[$block_id];
        if(count($block) === 0){
            if(count($this->tree) === 1){
                $this->tree = [$this->create_new_block()];
            }else{
                if($block_id === 0){
                    array_shift($this->tree);
                }elseif($block_id === count($this->tree) -1){
                    array_pop($this->tree);
                }else{
                    $this->tree = array_merge(array_slice($this->tree, 0, $block_id), array_slice($this->tree, $block_id + 1));
                }
            }
            $this->BIT_broken = true;
        }elseif(count($block) === $this->rebuild_max_size){
            if($block_id === 0) {
                $this->tree = array_merge(
                    array_chunk($block, $this->max_size),
                    array_slice($this->tree, $block_id + 1));
            }elseif($block_id === count($this->tree) -1) {
                $chunk = array_chunk($block,  $this->max_size);
                foreach($chunk as $no => $data){
                    $this->tree[$block_id + $no] = $data;
                }
            }else{
                $this->tree = array_merge(array_slice($this->tree, 0, $block_id),
                    array_chunk($block, $this->max_size),
                    array_slice($this->tree, $block_id + 1));
            }
            $this->BIT_broken = true;
        }
    }

    private function rebuild_BIT(): void
    {
        $range_sum = [0];
        $this->BIT = [0];
        foreach($this->tree as $id => $range){
            $range_sum[$id + 1] = count($range) + $range_sum[$id];
            $this->BIT[$id + 1] = $range_sum[$id + 1] - $range_sum[$id + 1 -(($id + 1) & -($id + 1))];
        }
    }

    private function add_BIT_lazy($block, $value): void
    {
        if(isset($this->BIT_lazy[$block])){
            $this->BIT_lazy[$block] += $value;
        }else{
            $this->BIT_lazy[$block] = $value;
        }
    }

    private function apply_BIT_lazy(): void
    {
        if($this->BIT_broken){
            $this->rebuild_BIT();
        }else{
            foreach($this->BIT_lazy as $block => $value){
                if($value === 0){
                    continue;
                }
                $this->add_BIT($block, $value);
            }
        }
        $this->BIT_broken = false;
        $this->BIT_lazy = [];

    }

    private function add_BIT($block, $value): void
    {
        $block++;
        $target = $block;
        while(isset($this->BIT[$target])){
            $this->BIT[$target] += $value;
            $target += ($target & -$target);
        }
    }

    private function count_BIT($from, $to): int
    {
        $this->apply_BIT_lazy();
        $to_count = 0;
        for($i=$to + 1; $i>0; $i-= ($i & -$i)){
            $to_count += $this->BIT[$i];
        }
        for($i=$from; $i>0;$i -= ($i & -$i)){
            $to_count -= $this->BIT[$i];
        }
        return $to_count;
    }

    private function lower_bound_BIT($number): array
    {
        $this->apply_BIT_lazy();;
        $max = count($this->tree);
        $t = $this->msb($max);
        $offset = 0;
        $sum = 0;
        for($i=$t; $i>0; $i>>=1){
            if($offset + $i <= $max && $sum + $this->BIT[$i + $offset] < $number){
                $sum += $this->BIT[$i + $offset];
                $offset += $i;
            }
        }
        return [$offset, $sum];
    }

    private function msb(int $x): int
    {
        $x |= ($x >> 1);
        $x |= ($x >> 2);
        $x |= ($x >> 4);
        $x |= ($x >> 8);
        $x |= ($x >> 16);
        $x |= ($x >> 32);
        return 2 ** (int)gmp_popcount($x);
    }

}

