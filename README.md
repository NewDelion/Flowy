# Flowy
__Awaitable for PocketMine-MP__  
# This project is moved => [FlowyProject](https://github.com/FlowyProject)

## Install
__Composer__
```
composer config repositories.NewDelion/Flowy vcs https://github.com/NewDelion/Flowy
composer require NewDelion/Flowy:dev-master
```
__GitSubmodule__
```
git submodule add https://github.com/NewDelion/Flowy
```

## Example
```php
<?php
namespace FlowyExample;

use Flowy\Flowy;
use Flowy\ListenAwaitable;
use function Flowy\listen;
#use function Flowy\delay;
#use function Flowy\done;
#use function Flowy\cancel;

use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerChatEvent;

class HeySiri extends Flowy{
    function onEnable(){
        ListenAwaitable::registerMethod('filter_eq', 
            function(string $methodName, $obj, bool $strict = true){
                return $this->filter(function($ev) use ($methodName, $obj, $strict){
                    if($strict){
                        return $ev->{$methodName}() === $obj;
                    }
                    else{
                        return $ev->{$methodName}() == $obj;
                    }
                });
            }
        );
        $this->start($this->heysiri());
    }

    function heysiri(){
        $event = yield listen(PlayerJoinEvent::class);
        $this->start($this->heysiri());
        $player = $event->getPlayer();

        $branch_quit = function(){ yield listen(PlayerQuitEvent::class); };

        while(true){
            $event = yield listen(PlayerChatEvent::class)
                ->filter_eq('getPlayer', $player)
                ->filter_eq('getMessage', 'heysiri')
                ->branch($branch_quit);

            $player->sendMessage("What can I help you with?\nGo ahead. I'm listening...");
            $event->setCancelled();

            $event = yield listen(PlayerChatEvent::class)
                ->filter_eq('getPlayer', $player)
                ->timeout(20 * 15, [$player, 'sendMessage'], ["(♪popon)"], true)
                ->branch($branch_quit);

            if($event !== null){
                $player->sendMessage("I'm not sure I understand.");
                $event->setCancelled();
            }
        }
    }
}
```
