<?php
namespace Scoop\Persistence;

class ObjectRelation {
    const ONE_TO_ONE = 1;
    const ONE_TO_MANY = 2;
    const MANY_TO_MANY = 3;
    private $type;
    private $objRel;
    private $self;
    private $methodRel;

    public function __construct($self, $methodRel, $type = self::ONE_TO_MANY, $objRel = null)
    {
        $this->self =& $self;
        $this->objRel =& $objRel;
        $this->methodRel = $methodRel;
        $this->type = $type;
    }

    private function delete(&$obj, $remove)
    {
        if ($remove) {
            $method = 'remove'.$this->methodRel;
            $arg =& $this->self;
        } else {
            $method = 'set'.$this->methodRel;
            $arg = null;
        }
        $obj->$method($arg);
    }

    public function add(&$child)
    {
        if (!$this->objRel) {
            $this->objRel = new ObjectCollector();
        }
        if ( !$this->objRel->search($child) ) {
            $method = ($this->type === self::MANY_TO_MANY?'add':'set').$this->methodRel;
            $this->objRel->add($child);
            $child->$method($this->self);
        }
    }

    public function remove(&$child)
    {
        if (!$this->objRel) {
            $this->objRel = new ObjectCollector();
        }
        $this->delete($child, $this->type === self::MANY_TO_MANY);
        $this->objRel->remove($child);
        
    }

    public function set(&$parent)
    {
        $isSettable = $parent !== null && $this->objRel !== $parent;
        if ( $isSettable) {
            $method = ($this->type !== self::ONE_TO_ONE?'add':'set').$this->methodRel;
            $this->objRel && $this->delete($this->objRel, $this->type !== self::ONE_TO_ONE);
        }
        $this->objRel = $parent;
        $isSettable && $parent->$method($this->self);
    }

    public function get()
    {
        return $this->objRel instanceOf ObjectCollector?
                    $this->objRel->toArray():
                    $this->objRel;
    }
}
