<?php
namespace BqCore\Entity;
use Zend\Db\ResultSet\ResultSet;

class Relyon
{
    protected $entities;

    public function getEntities() { return $this->entities; }
    public function setEntities(ResultSet $entities) {
        $this->entities = $entities;
        return $this;
    }
}
