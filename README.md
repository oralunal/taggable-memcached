Install

```
composer require oralunal/taggable-memcached
```

Usage

```php
use Oralunal\TaggableMemcached\Cache;

$memcached = new Cache::getInstance(server:'localhost', port:11211, prefix:'taggable_');

// Best Practise
$key = 'some_key';
try{
    if(!is_null($value = $memcached->get($cache_key))){
        // Do something with $value
    } else {
        // Generate the value and save it
        $value = 'some value';
        $memcached->set($cache_key, $value, 60);
    }
} catch(\Oralunal\TaggableMemcached\Exceptions\GetException $e){    
    // Memcached failed to get the value
    // Log the error and define value here
    $value = 'some value'; // Don't save it to memcached, maybe there is a problem with memcached server.
} catch(\Oralunal\TaggableMemcached\Exceptions\SetException $e){
    // Memcached failed to set the value
    // Log the error
    // We don't need to define value here because we did it before saving it to the memcached server.
}

// Delete a value
$memcached->delete($key);

// Flush all values
$memcached->flush();

// Set a value with tags
$tags = ['tag1', 'tag2'];
$memcached->withTags($tags)->set($key, $value));

$tag = 'tag3';
$memcached->withTags($tag)->set($key, $value);

// Delete values by tag
$memcached->deleteByTag($tag);
```

TODOs
- [ ] Add tests
- [ ] Add more error handling and logging
- [ ] Add more documentation
- [ ] Check for other PHP versions (I've just tested with PHP 8.3)