[![Build Status](https://travis-ci.org/megumiteam/custom-feed-plugin-template.svg?branch=master)](https://travis-ci.org/megumiteam/custom-feed-plugin-template)
# custom-feed-plugin-template
## Overview
オリジナルフィードを開発する際に使用するテンプレートです

## Endpoint
http://example.com/feed/{フィード識別子}

## How to use
- 以下のファイルの名前空間を開発するフィード識別子に修正します
 - custom-feed.php
 - tests/test-custom-feed.php

https://github.com/megumiteam/custom-feed-plugin-template/blob/master/custom-feed.php#L13

- custom-feed.phpのCustom_Feedクラスを編集します
 - $revision_first_valueにリビジョンの初期値
 - $status配列にstatusタグの表示値を指定
~~~
private $revision_first_value = 1;
private $status       = array(
								'create' => 1,
								'update' => 2,
								'delete' => 3
								);
~~~
https://github.com/megumiteam/custom-feed-plugin-template/blob/master/custom-feed.php#L25-L30
