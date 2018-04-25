<?php
namespace Flowy;

use pocketmine\event\Event;

if(!class_exists('Flowy\ListenAwaitable')){

class ListenAwaitable extends Awaitable{
	/** @var string[] */
	protected $targets = [];

	/** @var callable[] */
	protected $filters = [];

	/** @var callable[string] */
	protected static $extensions = [];

	public static function registerExtensionMethod(string $name, \Closure $method){
		if(isset(self::$extensions[$name]))
			throw FlowyException('');
		self::$extensions[$name] = $method;
	}

	public function __call($name, $args){
		if(!isset(self::$extensions[$name]))
			throw FlowyException('');
		call_user_func_array(self::$extensions[$name]->bindTo($this), $args);
		return $this;
	}

	public function filter(callable $filter){
		$this->filters[] = $filter;
		return $this;
	}

	public function timeout(int $tick, callable $flowDef = null, array $args_array = null, bool $continueWhenDone = true){
		return $this->branch(function() use ($flowDef, $args_array, $tick){
			yield \Flowy\delay($tick);
			if($flowDef !== null && ($flow = $flowDef(...$args_array)) instanceof \Generator){
				yield from $flow;
			}
		}, null, $continueWhenDone);
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