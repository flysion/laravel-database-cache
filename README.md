# laravel-database-cache
Laravel数据库缓存

## 特色

### 多级缓存
示例：从`array`获取数据，如果数据获取不到就从`file`获取数据，如果还没获取到才从数据库获取数据并缓存起来（缓存到file和array中）

    $user = User::whereKey($id)
        ->cache(300, null, false, 'file', true)->cacheFromArray()
        ->firstOrFail();

### 双重缓存方式
#### DB缓存

    use \Illuminate\Support\Facades\DB;
    DB::table('user')->where('id', 100)->cache(300, null, false, 'redis')->get()

#### ORM缓存
将`Eloquent Model`缓存起来（配合`array`缓存可实现模型共享，脑洞大开可以解决很多场景问题）
    
    $id = 100;
    
    $a = User::whereKey($id)->cache(300, null, false, 'array', true)->firstOrFail();
    $a->username = "xxxyyyzzz111";
    
    $b = User::whereKey($id)->cache(300, null, false, 'array', true)->firstOrFail();
    
    echo $b->username // xxxyyyzzz111
    
*即使在ORM环境下，默认使用的也是DB缓存，只有明确`cache`第5个参数为`true`才使用ORM缓存*

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
    // 在【86400】秒内每【300】秒读取一次数据库（并刷新到【redis】），如果读取失败则继续使用上一次缓存的数据）
    // 【86400】秒后如果还从数据库获取不到数据，就不再从缓存返回了
    // 如果数据库查询不到数据【false】，则下次继续读取数据库
    User::cache(300, 86400, false, 'redis')->get();
    
    // 如果数据库查询不到数据【true】，则下次不再查询数据库，直接返回空数据
    User::cache(300, 86400, true, 'redis')->get();
    
    // 无限期缓存数据，在缓存期间每【300】秒读取一次数据库（并刷新到【redis】），如果读取失败则继续使用上一次缓存的数据）
    User::cache(300, null, false, 'redis')->get();
    
    // 缓存数据300秒(期间不刷新）
    User::cache(null, 300, false, 'redis')->get();
    
    // 二级缓存
    // 从【array】获取数据，如果【array】没有获取到再从【redis】获取数据
    User::cache(null, 300, false, 'redis')->cache(null, null, false, 'array')->get();
    
    // 模型缓存
    User::cache(null, 300, false, 'redis', true)->get();

## cache 函数原型
由于同时支持**DB缓存**和**ORM缓存**所以文中提到的`cache`、`cacheFromArray`、`cacheFromFile`等方法，在以下两个地方都有实现（且返回原型有细微差别，进阶用法还需自己多看源码）：

+ `\Flysion\Database\EloquentBuilder::class`

        namespace Flysion\Database;
        
        class EloquentBuilder extends \Illuminate\Database\Eloquent\Builder
        {
            /**
             * @param int|null $refreshTtl
             * @param \DateTimeInterface|\DateInterval|int|null $ttl
             * @param bool $allowNull
             * @param string|\Illuminate\Contracts\Cache\Repository|null $driver
             * @param bool $useModelCache
             * @return static
             */
            public function cache($refreshTtl = null, $ttl = null, $allowNull = false, $driver = null, $useModelCache = false);
        }
        
+ `\Flysion\Database\QueryBuilder::class`

        namespace Flysion\Database;
        
        class QueryBuilder extends \Illuminate\Database\Query\Builder
        {
            /**
             * @param int|null $refreshTtl
             * @param \DateTimeInterface|\DateInterval|int|null $ttl
             * @param bool $allowNull
             * @param string|\Illuminate\Contracts\Cache\Repository|null $driver
             * @return static
             */
            public function cache($refreshTtl = null, $ttl = null, $allowNull = false, $driver = null)
        }
    
二级及以上缓存如果`$refreshTtl`、`$ttl`传`null`则直接继承上一级缓存的设置，示例如下：

    User::cache(1800, 3600, false, 'redis')->cache(null, null, false, 'array')->get();
    // 等同于
    User::cache(1800, 3600, false, 'redis')->cache(1800, 3600, false, 'array')->get();
    

一些便捷方法：

+ whereKeyWithCache

        User::whereKeyWithCache(100, 300, 86400, false, 'redis')->first()
        // 等同于
        User::whereKey(100)->cache(300, 86400, false, 'redis')->first()

+ whereWithCache

        User::whereWithCache(['id' => 100], 300, 86400, false, 'redis')->first()
        // 等同于
        User::where(['id' => 100])->cache(300, 86400, false, 'redis')->first()
        
+ cacheFromArray

        User::where(['id' => 100])->cacheFromArray(300, 86400, false)->first()
        // 等同于
        User::where(['id' => 100])->cache(300, 86400, false, 'array')->first()
        
+ cacheFromFile

        User::where(['id' => 100])->cacheFromFile(300, 86400, false)->first()
        // 等同于
        User::where(['id' => 100])->cache(300, 86400, false, 'file')->first()

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