<?php

namespace BqCore\Entity;

interface EntityInterface {
    public function getId();
    public function getEntityName();
    public function setEntityName($entityName);
}
