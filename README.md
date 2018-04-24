# Flowy
__Awaitable for PocketMine-MP__

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
use function Flowy\listen;
#use function Flowy\delay;
#use function Flowy\done;
#use function Flowy\cancel;

use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerChatEvent;

class HeySiri extends Flowy{
    function onEnable(){
        $this->start($this->heysiri());
    }

    function heysiri(){
        $event = yield listen(PlayerJoinEvent::class);
        $this->start($this->heysiri());
        $player = $event->getPlayer();

        $filter_player = function($ev) use ($player){ return $ev->getPlayer() === $player; };
        $branch_quit = function(){ yield listen(PlayerQuitEvent::class); };

        while(true){
            $event = yield listen(PlayerChatEvent::class)->filter($filter_player)
                ->filter(function($ev){ return $ev->getMessage() === 'heysiri'; })
                ->branch($branch_quit);

            $player->sendMessage("What can I help you with?\nGo ahead. I'm listening...");
            $event->setCancelled();

            $event = yield listen(PlayerChatEvent::class)->filter($filter_player)
                ->timeout(20 * 15)
                ->branch($branch_quit);

            if($event === null){
                $player->sendMessage("(♪popon)");
            }
            else{
                $player->sendMessage("I'm not sure I understand.");
                $event->setCancelled();
            }
        }
    }
}
```
