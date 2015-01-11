<?php

namespace ChemLab\InventoryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use ChemLab\Utilities\ArrayEntity;

/**
 * Entry
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Entry extends ArrayEntity {
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Assert\Type(type = "\ChemLab\CatalogBundle\Entity\Item", message = "Tipo articolo non valido")
     * @Assert\NotNull(message = "Indicare un articolo valido")
     * @ORM\ManyToOne(targetEntity="ChemLab\CatalogBundle\Entity\Item", inversedBy="entries")
     * @ORM\JoinColumn(name="item", referencedColumnName="id")
     */
    protected $item;

    /**
     * @Assert\Type(type = "\ChemLab\LocationBundle\Entity\Location", message = "Tipo locazione non valido")
     * @Assert\NotNull(message = "Indicare una locazione valida")
     * @ORM\ManyToOne(targetEntity="ChemLab\LocationBundle\Entity\Location", inversedBy="entries")
     * @ORM\JoinColumn(name="location", referencedColumnName="id")
     */
    protected $location;

    /**
     * @var integer
     *
     * @ORM\Column(name="quantity", type="integer")
     * @Assert\GreaterThanOrEqual(value=0, message="Inserire un valore non negativo")
     */
    protected $quantity;

    /**
     * @var string
     *
     * @ORM\Column(name="notes", type="string", length=1023)
     */
    protected $notes;

	public function __construct() {
		$this->quantity = 0;
		$this->notes = '';
	}

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
     * Set quantity
     *
     * @param integer $quantity
     * @return Entry
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity
     *
     * @return integer 
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set notes
     *
     * @param string $notes
     * @return Entry
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

    /**
     * Set item
     *
     * @param \ChemLab\InventoryBundle\Entity\Item $item
     * @return Entry
     */
    public function setItem($item = null)
    {
        $this->item = $item;

        return $this;
    }

    /**
     * Get item
     *
     * @return \ChemLab\InventoryBundle\Entity\Item 
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * Set location
     *
     * @param \ChemLab\LocationBundle\Entity\Location $location
     * @return Entry
     */
    public function setLocation($location = null)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location
     *
     * @return \ChemLab\LocationBundle\Entity\Location 
     */
    public function getLocation()
    {
        return $this->location;
    }

	// Implementazione di ArrayEntityInterface
	public function toArray() {
		return array(
			'id' => $this->id,
			'item' => empty($this->item) ? null : $this->item->toArray(),
			'location' => empty($this->location) ? null : $this->location->toArray(),
			'quantity' => $this->quantity,
			'notes' => $this->notes
		);
	}
}
