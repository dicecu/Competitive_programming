# Pseudo Multiset

座標圧縮せずに BIT + 座標圧縮 と同じくらいの速さで動く(はずの)multisetもどき

### 参考
https://github.com/tatyam-prime/SortedSet

### 概要
配列を200-350個毎に一つのブロックとして管理することで挿入・削除を（そこそこ）高速に行います。

PHPでは、配列へのinsertがとてもとても遅いため、各ブロックあらかじめサイズ700のSplFixedArrayを用意しておき、
中心の要素から前後に伸長します。  
2x10^5回の挿入を行う際に最も高速に動くことを期待して定数を設定しましたが、最適ではないかもしれません。

要素の位置はpseudo-iterator配列 ( [ブロック番号, ブロック内の位置] からなる配列) を用いて表現します。  

### サンプル
ABC 119 D  
https://atcoder.jp/contests/abc119/submissions/30513451

ABC 217 D  
https://atcoder.jp/contests/abc217/submissions/30491210

ABC 228 D  
https://atcoder.jp/contests/abc228/submissions/30514391

ABC 241 D  
https://atcoder.jp/contests/abc241/submissions/30491219

ABC 245 E  
https://atcoder.jp/contests/abc245/submissions/30491179

