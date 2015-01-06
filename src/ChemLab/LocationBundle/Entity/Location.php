<?php

namespace ChemLab\LocationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use ChemLab\Utilities\ArrayEntity;

/**
 * Location
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Location extends ArrayEntity {
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=80)
     * @Assert\NotBlank(message = "Il nome del posto non può essere vuoto")
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="position", type="string", length=255)
     */
    protected $position;

    /**
     * @var integer
     *
     * @ORM\Column(name="capacity", type="integer")
     * @Assert\GreaterThanOrEqual(value=0, message="Inserire un valore non negativo")
     */
    protected $capacity;

    /**
     * @var string
     *
     * @ORM\Column(name="notes", type="string", length=1023)
     */
    protected $notes;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Location
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set position
     *
     * @param string $position
     * @return Location
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return string 
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set capacity
     *
     * @param integer $capacity
     * @return Location
     */
    public function setCapacity($capacity)
    {
        $this->capacity = $capacity;

        return $this;
    }

    /**
     * Get capacity
     *
     * @return integer 
     */
    public function getCapacity()
    {
        return $this->capacity;
    }

    /**
     * Set notes
     *
     * @param string $notes
     * @return Location
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * Get notes
     *
     * @return string 
     */
    public function getNotes()
    {
        return $this->notes;
    }
}
