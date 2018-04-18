# Flowy
__Awaitable for PocketMine-MP__

```php
<?php
namespace FlowyExample;

use Flowy\Flowy;
use function Flowy\listen;

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

		while(true){
			$event = yield listen(PlayerChatEvent::class)->filter($filter_player)
				->filter(function($ev){ return $ev->getMessage() === 'heysiri'; })
				->branch(function(){ yield listen(PlayerQuitEvent::class); });//exit

			$player->sendMessage("What can I help you with?\nGo ahead. I'm listening...");
			$event->setCancelled();

			$event = yield listen(PlayerChatEvent::class)->filter($filter_player)
				->timeout(20 * 15, function() use ($player){ $player->sendMessage("(♪popon)"); }, true/*continue*/)
				->branch(function(){ yield listen(PlayerQuitEvent::class); });//exit

			if($event !== null){
				$player->sendMessage("I'm not sure I understand.");
				$event->setCancelled();
			}
		}
	}
}
```
