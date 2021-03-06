<?php
namespace BqCore\Entity;

use Zend\Db\RowGateway\RowGateway;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventManagerAwareInterface;
use BqCore\Event\DataEvent;
use BqCore\Event\EntityEvent;

abstract class AbstractEntity extends RowGateway implements EntityInterface, 
    EventManagerAwareInterface
{
    protected $eventManager;
    protected $relyonEntities = array();
    protected $entityName;

    public function getId() { return $this->id; }

    public function getEntityName() { return $this->entityName; }
    public function setEntityName($entityName) {
        $this->entityName = $entityName;
        return $this;
    }

    public function getRelyonEntities($entityName, Array $ids=array(), 
        Array $params=array()) {

        $paramString = array();
        foreach($params as $name=>$value)
            $paramString[] = sprintf('%s=%s', $name, $value);
        $cacheKey = sprintf(
            '%s?%s&id=%s', 
            $entityName, 
            implode('&', $paramString)
            json_encode($ids)
        );
        $cacheKey = md5($cacheKey);

        if(isset($this->relyonEntities[$cacheKey]))
            return $this->relyonEntities[$cacheKey];

        $entityEvent = new EntityEvent();
        $entityEvent->setTarget($this)->setParams($params)
            ->setRelyonEntityName($entityName)
            ->setRelyonEntityIds($ids)
            ->setName(EntityEvent::EVENT_GET_RELYON_ENTITY);
        $results = $this->getEventManager()->trigger($entityEvent, 
            function($result) {
                return ($result instanceof Relyon);
        });

        if($results->stopped()) {
            $relyon = $results->last();
            if($relyon instanceof Relyon) {
                $this->setRelyonEntities($cacheKey, $relyon->getEntities());
                return $relyon->getEntities();
            }
        }

        return false;
    }

    public function addRelyonEntity(EntityInterface $entity) {
        $entityEvent = new EntityEvent();
        $entityEvent->setTarget($this)->setRelyonEntity($entity)
            ->setName(EntityEvent::EVENT_ADD_RELYON_ENTITY);
        $this->getEventManager()->trigger($entityEvent);
    }

    public function setRelyonEntities($entityName, $entities) {
        $this->relyonEntities[$entityName] = $entities;
        return $this;
    }

    public function getEventManager() { return $this->eventManager; }
    public function setEventManager(EventManagerInterface $eventManager) {
        $this->eventManager = $eventManager;
        return $this;
    }

    public function save() {
        if(!isset($this->id) || empty($this->id)) {
            $eventName     = DataEvent::EVENT_INSERT;
            $postEventName = DataEvent::EVENT_INSERT_POST;
        } else {
            $eventName     = DataEvent::EVENT_CHANGE;
            $postEventName = DataEvent::EVENT_CHANGE_POST;
        }

        $eventManager = $this->getEventManager();
        $event = new DataEvent();
        $event->setTarget($this)->setName($eventName);
        $eventManager->trigger($event);

        $result = parent::save();

        $event->setName($postEventName);
        $eventManager->trigger($event);
        
        return $result;
    }
}
