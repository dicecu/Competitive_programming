# Pseudo Multiset

座標圧縮せずにBIT + 座標圧縮 と同じくらいの速さで動く(はずの)multisetもどき

### 参考
https://github.com/tatyam-prime/SortedSet

### 概要
配列を100-350個毎に一つのブロックとして管理することで挿入・削除を（そこそこ）高速に行います。  
PHPは配列へのinsertが遅いため、√N個に分割するよりも小さなブロックに分割したほうが速いようです。

定数倍は軽いですが、add, eraseの操作は要素数Nに対して（たぶん）amortized O(N)となるにせものです。  
N番目に小さな要素の取得、[L, R]に含まれる要素の個数取得、削除等も行うことができます。

JITが有効になれば、もう少し実用的になりそうです。

### サンプル
ABC 119 D (766 ms)  
https://atcoder.jp/contests/abc119/submissions/30761007

ABC 140 E (678 ms)  
https://atcoder.jp/contests/abc140/submissions/30760986

ABC 170 E (2811 ms)  
https://atcoder.jp/contests/abc170/submissions/30760964

ABC 217 D (664 ms)  
https://atcoder.jp/contests/abc217/submissions/30760945

ABC 228 D (641 ms)  
https://atcoder.jp/contests/abc228/submissions/30760933

ABC 234 D (1960 ms)  
https://atcoder.jp/contests/abc234/submissions/30760768

ABC 241 D (657 ms)  
https://atcoder.jp/contests/abc241/submissions/30760857

ABC 245 E (1143 ms)  
https://atcoder.jp/contests/abc245/submissions/30760905

### 更新履歴

#### v2.0
配列操作をarray\_sliceに変更。

#### v1.0
初版  
SPLFixedArrayの中心から挿入していきを前後に伸ばしていた。

