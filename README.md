# laravel-database-cache
Laravel数据库缓存

## 安装

    composer require lee2son/laravel-database-cache
    
## 使用
+ 在普通查询中使用：

        DB::table('user')->take(2)->cache()->get()

+ 在`Model`中使用：
        
        User::cache()->pluck('name', 'id');
        User::cache()->get();

*默认情况下缓存使用 sql 语句作为缓存的key，所以如果让两次查询走同一个缓存一定要保证SQL语句一模一样；直接写SQL语句的方式不支持缓存：*

    // Not from cache
    DB::cache()->select('select * from user limit 1');

## 配置
在使用中指定：

    User::cache(300, 'redis', 'number_one')->first()
    User::cache()->ttl(300)->driver('redis')->key('number_one')->first()

缓存driver可通过env配置：

    # .env
    DB_CACHE_DRIVER=redis

## 进阶
默认情况下缓存的数据是数据库中的原始数据(占用存储空间少)，有时候我们需要缓存 model:

    class User extends Illuminate\Database\Eloquent\Model {
        use \Lee2son\Database\Cacheable;
    }

    User::useModelCache()->get();
    User::useModelCache()->first();
    User::useModelCache()->find(1);
    User::useModelCache()->findMany([1,2,3]);

    User::useModelCache(300, 'redis', 'number_one')->first()
    User::useModelCache()->ttl(300)->driver('redis')->key('number_one')->first()

直接缓存model的好处是可以保存model的状态，示例：

    function foo() {
        $user = User::useModelCache()->driver('array')->first();
        $user->age = 22;
    }

    function bar() {
        $user = User::useModelCache()->driver('array')->first();
        echo $user->age;
    }

    foo();
    bar(); // 22