<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

Route::get('think', function () {
    // return 'hello,ThinkPHP5!';
});

Route::domain('admin.xxx.xyz', 'admin');
Route::domain('api.xxx.xyz', 'api');
// Route::domain('union.local-hx.com', 'api');

return [

];
