<?php
/**
 * Class PseudoMultiSet
 *
 * Multisetっぽいことをそこそこの速さでできるクラス
 *
 * @version 1.0
 * @author dicecu
 * @link https://github.com/dicecu/Competitive_programming
 * @license CC0-1.0
 */
class PseudoMultiSet
{
    private int $max_size = 200;
    private int $rebuild_max_size = 350;
    private int $rebuild_min_size = 0;
    private int $skip_size;
    private int $offset;
    private array $tree;
    private array $count;
    //[null, null, 2, 3, 4, null, null]のとき、range = [2, 5]  となる(右側が+1)
    private array $range;

    /**
     * PseudoMultiSet constructor
     *
     * 引数にソート済みの配列を与えると、高速に代入する。
     * ソート済みでない配列を与えた場合、矛盾が生じた時点で代入を中止する。（エラーは発生しない）
     *
     * @param array $sorted_array
     */
    function __construct(array $sorted_array=[]){
        $this->count = [0];
        $this->offset = $this->rebuild_max_size;
        $this->range = [[ $this->offset,  $this->offset]];
        $this->default = new SplFixedArray($this->rebuild_max_size * 2 + 5);
        $this->tree = [$this->create_new_block()];
        $this->skip_size = $this->max_size + intdiv($this->rebuild_max_size - $this->max_size, 3);
        $L = PHP_INT_MIN;
        $block_id = 0;
        $block = &$this->tree[$block_id];
        $offset = $this->offset;
        foreach($sorted_array as $value){
            if($value < $L){
                break;
            }
            if($offset === $this->offset -1){
                $this->tree[] = $this->create_new_block();
                $offset = $this->offset;
                $block = &$this->tree[$block_id];
                $this->range[] = [$this->offset, $this->offset];
            }
            $L = $value;
            $block[$offset] = $L;
            $offset++;
            if($offset > $this->offset + $this->max_size){
                $this->range[$block_id][1] = $offset;
                $block_id++;
                $offset = $this->offset - 1;
            }

        }
        if($offset > $this->offset){
            $this->range[$block_id][1] = $offset;
        }
    }

    /**
     * pseudo-iteratorから値を取得する
     * add, eraseなどの操作をした場合、ただしい値を返さない可能性がある
     *
     * @param $iterator
     * @return mixed|null
     */
    public function get($iterator){
        if($iterator[0] === null || $iterator[1] === null){
            return null;
        }
        return $this->tree[$iterator[0]][$iterator[1]] ?? null;
    }

    /**
     * 値を追加する
     *
     * @param $value
     */
    public function add($value): void
    {
        [$block_id, $address] = $this->upper_bound($value);

        $block = &$this->tree[$block_id];
        $range = &$this->range[$block_id];
        if($address - $range[0] < $range[1] - $address){
            $range[0]--;
            $next = $value;
            for($i=$address -1; $i >= $range[0]; $i--){
                $now = $block[$i];
                $block[$i] = $next;
                $next = $now;
            }
            if($range[1] - $range[0] >= $this->rebuild_max_size - 1){
                $this->rebuild($block_id);
            }

            if($this->range[$block_id][0] === 0){
                $this->realign($block_id);
            }
        }else{
            $range[1]++;
            $next = $value;
            for($i=$address; $i<$range[1]; $i++){
                $now = $block[$i];
                $block[$i] = $next;
                $next = $now;
            }
            if($range[1] - $range[0] >= $this->rebuild_max_size - 1){
                $this->rebuild($block_id);
            }
            if($this->range[$block_id][1] === $this->rebuild_max_size * 2 + 1){
                $this->realign($block_id);
            }
        }


    }

    /**
     * valueに一致するデータをすべて削除する
     *
     * @param int $value
     */
    public function erase_value(int $value){

        $iterator = $this->lower_bound($value);
        $start_block = $iterator[0];
        while($this->get($iterator) === $value){
            $this->_erase($iterator);
            $iterator = $this->next($iterator);
        }
        $this->rebuild($start_block);
    }

    private function _erase($iterator){

        [$block_id, $address] = $iterator;
        $block = &$this->tree[$block_id];
        $range = &$this->range[$block_id];
        if($address - $range[0] < $range[1] - $address){
            $next = null;
            for($i=$range[0]; $i <= $address; $i++){
                $now = $block[$i];
                $block[$i] = $next;
                $next = $now;
            }
            $range[0]++;
        }else{
            $next = null;
            $range[1]--;
            for($i=$range[1]; $i>=$address; $i--){
                $now = $block[$i];
                $block[$i] = $next;
                $next = $now;
            }
        }

    }

    /**
     * pseudo-iteratorの値を削除する
     *
     * @param $iterator
     */
    public function erase($iterator){
        $this->_erase($iterator);
        $range = $this->range[$iterator[0]];
        if($range[1] - $range[0] <= $this->rebuild_min_size){
            $this->rebuild($iterator[0]);
        }
    }

    /**
     * 指定した値を検索し、pseudo-iteratorを返す
     * 指定した値が存在しない場合、戻り値は[null, null]となる
     * valueに合致するデータが複数ある場合、最初に登録したデータの座標を返す
     *
     * @param int $value
     * @return array|null[]
     */
    public function find(int $value):array
    {
        $iterator = $this->lower_bound($value);
        if($this->get($iterator) === $value){
            return $iterator;
        }else{
            return [null, null];
        }
    }

    /**
     * target以上で最小の値のpseudo-iteratorを返す
     *
     * @param $target
     * @return array
     */
    public function lower_bound($target):array
    {
        $min = -1;
        $max = count($this->tree);
        while($max - $min > 1){
            $center = $min + intdiv($max - $min, 2);
            if($this->tree[$center][$this->range[$center][1] -1] >= $target){
                $max = $center;
            }else{
                $min = $center;
            }
        }
        if($max === count($this->tree)){
            return [$max - 1, $this->range[$max-1][1]];
        }
        $block_id = $max;
        $block = &$this->tree[$block_id];
        [$min, $max] = $this->range[$block_id];
        $min--;
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

    /**
     * targetより大きい最小の値のpseudo-iteratorを返す
     *
     * @param $target
     * @return array
     */
    public function upper_bound($target){
        $min = -1;
        $max = count($this->tree);
        while($max - $min > 1){
            $center = $min + intdiv($max - $min, 2);
            if($this->tree[$center][$this->range[$center][1] - 1] > $target){
                $max = $center;
            }else{
                $min = $center;
            }
        }
        if($max === count($this->tree)){
            return [$max - 1, $this->range[$max-1 ][1]];
        }
        $block_id = $max;
        $block = &$this->tree[$block_id];
        [$min, $max] = $this->range[$block_id];
        $min--;
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

    /**
     * 指定した値の出現回数を数える
     * TODO　テスト
     *
     * @param $target
     * @return int|mixed
     */
    function count_value($target){
        $left = $this->lower_bound($target);
        $right = $this->upper_bound($target);
        return $this->count_iterator($left, $right);
    }

    /**
     * 二つのpseudo-iteratorの間に存在する要素の個数[from, to)を返す
     * TODO　テスト
     *
     * @param $from
     * @param $to
     * @return int|mixed
     */
    public function count_iterator($from, $to){
        if($from === $to){
            return 0;
        }else{
            if($from[0] === $to[0]){
                return $to[1] - $from[1];
            }else{
                $start = $from[0] + 1;
                $end = $to[0];
                $ans = $this->count[$from[0]] - $from[1];
                while($start !== $end){
                    $ans += $this->count[$start];
                    $start++;
                }
                $ans += $to[1];
                return $ans;
            }
        }
    }

    /**
     * pseudo-iteratorを一つ進める
     *
     * @param array $iterator
     * @return array|null[]
     */
    public function next(array $iterator){
        if($iterator[0] === null || $iterator[1] === null){
            return [null, null];
        }
        if($this->range[$iterator[0]][1] <= $iterator[1] + 1){
            if(isset($this->range[$iterator[0] + 1])){
                $iterator[0] ++;
                $iterator[1] = $this->range[$iterator[0]][0];
                return $iterator;

            }else{
                return [null, null];
            }
        }else{
            $iterator[1]++;
            return $iterator;
        }
    }

    /**
     * pseudo-iteratorを一つ戻す
     *
     * @param array $iterator
     * @return array|null[]
     */
    public function prev(array $iterator)
    {
        if($iterator[0] === null || $iterator[1] === null){
            return [null, null];
        }
        if($iterator[0] === 0 && $iterator[1] === $this->range[0][0]){
            return [null, null];
        }
        if($this->range[$iterator[0]][0] === $iterator[1]){
            $iterator[0]--;
            $iterator[1] = $this->range[$iterator[0]][1] - 1;
        }else{
            $iterator[1]--;
        }
        return $iterator;
    }

    /**
     * 最小値のpseudo-iteratorを返す
     *
     * @return array
     */
    public function begin():array
    {
        return [0, $this->range[0][0]];
    }

    /**
     * 最大値のpseudo-iteratorを返す
     *
     * @return array
     */
    public function end():array
    {
        $last = array_key_last($this->count);
        return [$last, $this->range[$last][1]];
    }

    private function create_new_block()
    {
//        return clone $this->default;
        return new SplFixedArray($this->rebuild_max_size * 2 + 5);
    }


    private function rebuild($block_id){
        $new_array = [];
        $new_range = [];
        $new_block_id = 0;
        $new_block_count = $this->offset;
        foreach($this->tree as $id => $block){
            if($this->range[$id][1] - $this->range[$id][0] === 0){
                continue;
            }
            if($block_id > $id){
                $new_array[] = $this->tree[$id];
                $new_range[] = $this->range[$id];
                $new_block_id++;
                $new_block_count = $this->offset;
                continue;
            }elseif($this->range[$id][1] - $this->range[$id][0] < $this->skip_size){
                if($new_block_count > $this->offset) {
                    $new_range[$new_block_id][1] = $new_block_count;
                    $new_block_id++;
                }
                $new_array[] = $this->tree[$id];
                $new_range[] = $this->range[$id];
                $new_block_id++;
                $new_block_count = $this->offset;
                continue;
            }
            for($i=$this->range[$id][0];$i<$this->range[$id][1];$i++){
                if($new_block_count === $this->offset){
                    $new_array[] = $this->create_new_block();
                    $now_new_block = &$new_array[$new_block_id];
                    $new_range[] = [$this->offset, $this->offset + 1];
                }
                $now_new_block[$new_block_count] = $block[$i];
                $new_block_count++;
                if($new_block_count === $this->offset + $this->max_size){
                    $new_range[$new_block_id][1] = $new_block_count;
                    $new_block_id++;
                    $new_block_count = $this->offset;
//                    $now_new_block = &$new_array[$new_block_id];
                }
            }
        }
        if(isset($new_range[$new_block_id])) {
            $new_range[$new_block_id][1] = $new_block_count;
        }
        if(count($new_array) === 0){
            $this->range = [[ $this->offset,  $this->offset]];
            $this->tree = [$this->create_new_block()];
        }else {
            $this->tree = $new_array;
            $this->range = $new_range;
        }
    }

    private function realign($block_id){
        $block = &$this->tree[$block_id];
        $range = $this->range[$block_id];
        $new = $this->offset;
        for($i=$range[0];$i<$range[1];$i++){
            $block[$new] = $block[$i];
            $block[$i] = null;
            $new++;
        }
        $this->range[$block_id] = [$this->offset, $this->offset + ($range[1] - $range[0])];
    }

}


function example_ABC119_D()
{
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
        $x = $S->lower_bound($X);
        $sx = $S->get($x)??100000000000;
        $sx2 = $S->get($S->prev($x)) ?? -1000000000000;
        $t = $T->lower_bound($X);
        $st = $T->get($t) ?? 100000000000;
        $st2 = $T->get($T->prev($t)) ?? -1000000000000;
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



function example_ABC217_D()
{
    [$L, $Q] = fscanf(STDIN, "%d%d");
    $S = new PseudoMultiSet();
    $S->add(0);
    $S->add($L);
    $ans = [];
    while ($Q--) {
        [$C, $X] = fscanf(STDIN, "%d%d");
        if ($C === 1) {
            $S->add($X);
        } else {
            $T = $S->lower_bound($X);
            $Z = $S->get($S->prev($T));
            $ans[] = $S->get($T) - $Z;
        }
    }
    echo implode("\n", $ans);
}

function example_ABC228_D()
{
    $MOD = 1048576;
    [$Q] = fscanf(STDIN, "%d");
    $S = new PseudoMultiSet(range(0,2**20-1));

    $L = [];
    $ans = [];
    while($Q--){
        [$T, $X] = fscanf(STDIN, "%d%d");
        if($T === 1){
            $XX = $X % $MOD;
            $A = $S->lower_bound($XX);
            if($S->get($A)){
                $L[$S->get($A)] = $X;
                $S->erase($A);
            }else{
                $A = $S->begin();
                $L[$S->get($A)] = $X;
                $S->erase($A);
            }
        }else{
            $X %= $MOD;
            $ans[] = $L[$X]??-1;
        }
    }
    echo implode("\n", $ans);
}


function example_ABC241_D()
{
    [$Q] = fscanf(STDIN, "%d");
    $S = new PseudoMultiSet();
    while ($Q--) {
        [$A, $B, $C] = fscanf(STDIN, "%d%d%d");
        if ($A === 1) {
            $S->add($B);
        } elseif ($A === 2) {
            $iterator = $S->upper_bound($B);
            while ($C--) {
                $iterator = $S->prev($iterator);
            }
            $ans[] = $S->get($iterator) ?? -1;

        } else {
            $C--;
            $iterator = $S->lower_bound($B);
            while ($C--) {
                $iterator = $S->next($iterator);
            }
            $ans[] = $S->get($iterator) ?? -1;

        }
    }
    echo implode("\n", $ans);
}

function example_ABC245_E()
{

    [$N, $M] = fscanf(STDIN, "%d%d");
    $A = fscanf(STDIN, str_repeat("%d",$N));
    $B = fscanf(STDIN, str_repeat("%d",$N));
    $C = fscanf(STDIN, str_repeat("%d",$M));
    $D = fscanf(STDIN, str_repeat("%d",$M));
    arsort($C);
    arsort($A);
    $AA = [];
    foreach($A as $id => $V){
        $AA[$V][] = $B[$id];
    }
    $each = [key($C), current($C)];
    $set = new PseudoMultiSet();
    foreach($AA as $V => $Bs){
        arsort($Bs);
        while($each[1] >= $V) {
            $set->add($D[$each[0]]);
            if(next($C) === false){
                $each[1] = PHP_INT_MIN;
            }else{
                $each = [key($C), current($C)];
            }
        }
        foreach($Bs as $BB){
            $T = $set->lower_bound($BB);
            if(!$set->get($T)){
                echo "No";
                exit;
            }
            $set->erase($T);
        }

    }
    echo "Yes";
}

