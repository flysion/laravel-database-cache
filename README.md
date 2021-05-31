# laravel-database-cache
Laravel数据库缓存

*该插件仅仅支持MYSQL，不是因为其他数据库支持不了而是因为我的项目暂时没用到其他数据库，所以懒得支持。其次，也没有做laravel版本兼容性。如果你有任何问题联系：sss60@qq.com。我会全力支持*

## 特色

### 多级缓存
示例：从`array`获取数据，如果数据获取不到就从`file`获取数据，如果还没获取到才从数据库获取数据并缓存起来（缓存到file和array中）

    $user = User::whereKey($id)
        ->cache(['driver' => 'file', 'ttl' => 600])->cache(['driver' => 'array'])
        ->firstOrFail();

### 双重缓存方式
#### DB缓存

    use \Illuminate\Support\Facades\DB;
    
    DB::table('user')->where('id', 100)
        ->cache(['driver' => 'file', 'ttl' => 600])
        ->get()

#### ORM缓存
将`Eloquent Model`缓存起来（配合`array`缓存可实现模型共享，脑洞大开可以解决很多场景问题）
    
    $id = 100;
    
    $a = User::whereKey($id)->cache(['driver' => 'array', 'ttl' => 600, 'use_model_cache' => true])->firstOrFail();
    $a->username = "xxxyyyzzz111";
    
    $b = User::whereKey($id)->cache(['driver' => 'array', 'ttl' => 600, 'use_model_cache' => true])->firstOrFail();
    echo $b->username; // xxxyyyzzz111

### 安全刷新
如果数据源获取失败（失败的定义是抛出了异常），则继续返回上一次缓存的数据

### 懒查询
支持将数据源的查询结果`NULL`缓存起来，避免如果数据源返回`NULL`就不停的查询数据源

## 安装

    composer require lee2son/laravel-database-cache
    
## 使用

    class User extends \Illuminate\Database\Eloquent\Model {
        use \Flysion\Database\Cacheable;
    }
    
示例：

    // 从【redis】获取数据
    // 在【86400】秒内每【300】秒读取一次数据库并刷新到【redis】
    // 如果缓存刷新失败则继续使用上一次缓存的数据，直至redis数据过期【86400】或不存在
    User::cache(['driver' => 'redis', 'ttl' => 86400, 'refresh_ttl' => 300])->get();
    
    // 如果数据库查询不到数据，则下次不再查询数据库，直接返回空数据
    User::cache(['driver' => 'redis', 'ttl' => 86400, 'allow_empty' => true])->get();
    
    // 无限期缓存数据，在缓存期间每【300】秒读取一次数据库（并刷新到【redis】）
    // 如果缓存刷新失败则继续使用上一次缓存的数据
    User::cache(['driver' => 'redis', 'refresh_ttl' => 300])->get();
    
    // 二级缓存
    // 从【array】获取数据，如果【array】没有获取到再从【redis】获取数据
    User::cache(['driver' => 'redis'])->cache(['driver' => 'array'])->get();
    
    // 模型缓存（更多的是配合 array 缓存使用）
    // 模型缓存序列化成字符串之后占用的空间会很大
    User::cache(['driver' => true, use_model_cache' => true])->get();

## 缓存选项
选项 | 类型 | 默认值 | 说明
----|----|----|----
driver|string|null|缓存介质，见 `config('cache.stores')`
ttl|int|null|存储在介质中的过期时间
refresh_ttl|int|null|缓存刷新周期
allow_empty|bool/null|null(false)|如果数据库读取不到数据则下次不再继续读取数据库
use_model_cache|bool|false|是否缓存 model


## 配置
如果缓存的`driver`设置为`null`，则使用默认缓存（`congig('cache.default')`），在`ORM`环境下可以在`model`中指定默认缓存的`driver`

    class User extends \Illuminate\Database\Eloquent\Model {
        use \Flysion\Database\Cacheable;

        public $cacheDriver = 'redis';
    }
    
    // 或者：
    
    class User extends \Illuminate\Database\Eloquent\Model {
        use \Flysion\Database\Cacheable;

        public function cacheDriver() {
            return 'redis';
        }
    }
    
二级及以上缓存如果`refresh_ttl`、`ttl`、`allow_empty`传`null`则直接继承上一级缓存的设置，示例如下：

    User::cache(['driver' => 'redis', 'ttl' => 300, 'refresh_ttl' => 86400, 'allow_empty' => true])->cache(['driver' => 'array'])->get();
    // 等同于
    User::cache(['driver' => 'redis', 'ttl' => 300, 'refresh_ttl' => 86400, 'allow_empty' => true])->cache(['driver' => 'array', 'ttl' => 300, 'refresh_ttl' => 86400, 'allow_empty' => true])->get();
