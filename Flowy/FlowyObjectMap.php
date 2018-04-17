<?php
namespace Flowy;

if(!class_exists('Flowy\FlowyObjectMap')){

class FlowyObjectMap implements \ArrayAccess, \IteratorAggregate{
	private $container = [];
	public function getIterator(){
		return new \ArrayIterator($this->container);
	}

	private $class;
	public function getClass(){
		return $this->class;
	}

	public function __construct(string $class){
		if(!class_exists($class))
			throw new FlowyException('');
		$this->class = $class;
	}

	public function offsetExists($offset) : bool{
		return isset($this->container[$offset]);
	}

	public function offsetGet($offset){
		if(!isset($this->container[$offset]))
			throw new \OutOfBoundsException('');
		return $this->container[$offset];
	}

	public function offsetSet($offset, $value) : void{
		if(!isset($this->container[$offset]))
			throw new \OutOfBoundsException('');
		if(!is_object($value))
			throw new FlowyException('');
		if(get_class($obj) !== $this->class)
			throw new FlowyException('');
		$this->container[$offset] = $value;
	}

	public function offsetUnset($offset) : void{
		if(!isset($this->container[$offset]))
			throw new \OutOfBoundsException('');
		unset($this->container[$offset]);
	}

	public function add($obj) : int{
		if(!is_object($obj))
			throw new FlowyException('');
		if(get_class($obj) !== $this->class)
			throw new FlowyException('');
		$index = $this->allocateIndex();
		$this->container[$index] = $obj;
		return $index;
	}

	private function allocateIndex() : int{
		for($i = 0; $i < count($this->container); ++$i){
			if(!isset($this->container[$i]))
				break;
		}
		return $i;
	}

	public function remove(int $index) : void{
		if(!isset($this->container[$index]))
			#throw new \OutOfBoundsException('');
			return;
		$this->container[$index] = null;
		unset($this->container[$index]);
	}
}

}// class_exists