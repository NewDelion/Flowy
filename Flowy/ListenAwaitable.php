<?php
namespace Flowy;

use pocketmine\event\Event;

if(!class_exists('Flowy\ListenAwaitable')){

class ListenAwaitable implements Awaitable{

	/** @var string[] */
	public $targets = [];

	/** @var callable[] */
	public $filters = [];

	public function addListenTarget(string $event){
		if(!is_subclass_of($event, Event::class))
			throw new FlowyException("{$event}はEventではありません");
		if(in_array($event, $this->targets))
			throw new FlowyException("{$event}は既にlistenしています");
		$this->target[] = $event;
	}

	public function filter(callable $filter){
		$this->filters[] = $filter;
		return $this;
	}
}

}// class_exists