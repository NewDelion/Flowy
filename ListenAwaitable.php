<?php
namespace Flowy;

use pocketmine\event\Event;

if(!class_exists('Flowy\ListenAwaitable')){

class ListenAwaitable extends Awaitable{
	/** @var string[] */
	protected $targets = [];

	/** @var callable[] */
	protected $filters = [];

	public function filter(callable $filter){
		$this->filters[] = $filter;
		return $this;
	}

	public function timeout(int $tick, callable $flow = null, bool $continueWhenDone = true){
		return $this->branch(function() use ($flow, $tick){
			yield \Flowy\delay($tick);
			if($flow !== null){
				yield from $flow();
			}
		}, $continueWhenDone);
	}

	public function addListenTarget(string $event){
		if(!is_subclass_of($event, Event::class))
			throw new FlowyException("{$event} is not an Event.");
		if(!in_array($event, $this->targets))
			$this->targets[] = $event;
	}

	public function getTargetEvents(){
		return $this->targets;
	}

	public function getFilters(){
		return $this->filters;
	}
}

}// class_exists