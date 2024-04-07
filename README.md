Install

```
composer require oralunal/taggable-memcached
```

Usage

```php
use Oralunal\TaggableMemcached\Cache;

$memcached = new Cache::getInstance(server:'localhost', port:11211, prefix:'taggable_');

$key = 'some_key';
$value = 'some value';

// Set a value
$memcached->set($key, $value);

// Get a value
$value = $memcached->get($key);

// Delete a value
$memcached->delete($key);

// Flush all values
$memcached->flush();

// Set a value with tags
$tags = ['tag1', 'tag2'];
$memcached->set($key, $value)->setTag($tags);

$tag = 'tag3';
$memcached->set($key, $value)->setTag($tag);

// Delete values by tag
$memcached->deleteByTag($tag);
```

TODOs
- [ ] Add tests
- [ ] Add more error handling and logging
- [ ] Add more documentation
- [ ] Check for other PHP versions (I've just tested with PHP 8.3)