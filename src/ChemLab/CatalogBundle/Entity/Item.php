<?php

namespace ChemLab\CatalogBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use ChemLab\Utilities\ArrayEntity;

/**
 * Item
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Item extends ArrayEntity {
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
     * @Assert\NotBlank(message = "Il nome dell'articolo non può essere vuoto")
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=1023)
     */
    protected $description;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=24)
     * @Assert\NotBlank(message = "Il codice dell'articolo non può essere vuoto")
     */
    protected $code;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=24)
     * @Assert\Choice(choices = {"solvent", "reagent", "glassware", "equipment", "other"}, message = "Scegliere un tipo valido")
     */
    protected $type;

    /**
     * @var float
     *
     * @ORM\Column(name="price", type="float")
     * @Assert\GreaterThanOrEqual(value=0, message="Inserire un valore non negativo")
     */
    protected $price;


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
     * @return Item
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
     * Set description
     *
     * @param string $description
     * @return Item
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set code
     *
     * @param string $code
     * @return Item
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set price
     *
     * @param float $price
     * @return Item
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return float 
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return Item
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

}
