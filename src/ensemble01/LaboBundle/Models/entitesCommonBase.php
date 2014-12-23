<?php
// src/ensemble01/LaboBundle/Models/entitesCommonBase.php

namespace ensemble01\LaboBundle\Models;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
// Tree
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * 
 * @ORM\MappedSuperclass
 *
 */
class entitesCommonBase {

   /**
     * @var \DateTime
     *
     * @ORM\Column(name="dateCreation", type="datetime", nullable=false)
     */
    private $dateCreation;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="dateExpiration", type="datetime", nullable=true)
     */
    private $dateExpiration;


    public function __construct() {
        $this->dateCreation = new \Datetime();
        $this->dateExpiration = null;
        $this->versions = new ArrayCollection();
    }

    /**
     * Set dateCreation
     *
     * @param \DateTime $dateCreation
     * @return baseEntity
     */
    public function setDateCreation($dateCreation) {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    /**
     * Get dateCreation
     *
     * @return \DateTime 
     */
    public function getDateCreation() {
        return $this->dateCreation;
    }

    /**
     * Set dateExpiration
     *
     * @param \DateTime $dateExpiration
     * @return baseEntity
     */
    public function setDateExpiration($dateExpiration) {
        $this->dateExpiration = $dateExpiration;
        return $this;
    }

    /**
     * Get dateExpiration
     *
     * @return \DateTime 
     */
    public function getDateExpiration() {
        return $this->dateExpiration;
    }



}
