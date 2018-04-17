# Flowy
__Awaitable for PocketMine-MP__

### Awaitable
```php
function awaitableExample(){
  $event = yield listen(PlayerJoinEvent::class);
}
```

### Filter
```php
function filterExample(){
  $event = yield listen(PlayerChatEvent::class)
    ->filter(function($ev){ return $ev->getMessage() === 'hello' })
    ->filter(function($ev){ return $ev->getMessage() === 'world' });
  print($event->getMessage()); /* "hello" or "world" */
}
```
