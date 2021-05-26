# laravel-database-cache
Laravel数据库缓存

## 安装

    composer require lee2son/laravel-database-cache
    
## 使用

    class User extends \Illuminate\Database\Eloquent\Model {
        use \Flysion\Database\Cacheable;
    }
    
    User::cache("all")->get();
    
具体送方法见：`\Flysion\Database\Cacheable`

## 多级缓存

    User::cache("all", 3600, true, 'redis')->cacheFromArray(null, 300, false)->get()
    

## 配置
如果缓存的 driver 设置为 null，则使用默认缓存，可以在 model 中指定默认缓存的 driver

    class User extends \Illuminate\Database\Eloquent\Model {
        use \Flysion\Database\Cacheable;

        public $cacheDefaultDriver = 'redis';
    }