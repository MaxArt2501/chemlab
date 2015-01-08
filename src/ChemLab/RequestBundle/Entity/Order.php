<?php

namespace ChemLab\RequestBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use ChemLab\Utilities\ArrayEntity;
use ChemLab\InventoryBundle\Entity\Item;
use ChemLab\AccountBundle\Entity\User;

/**
 * Order
 *
 * @ORM\Table(name="reqorder")
 * @ORM\Entity
 */
class Order extends ArrayEntity {
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
     * @ORM\ManyToOne(targetEntity="ChemLab\CatalogBundle\Entity\Item", inversedBy="orders")
     * @ORM\JoinColumn(name="item", referencedColumnName="id")
     */
    protected $item;

    /**
     * @Assert\Type(type = "\ChemLab\AccountBundle\Entity\User", message = "Tipo utente non valido")
     * @Assert\NotNull(message = "Indicare un utente valido")
     * @ORM\ManyToOne(targetEntity="ChemLab\AccountBundle\Entity\User", inversedBy="orders")
     * @ORM\JoinColumn(name="owner", referencedColumnName="id")
     */
    protected $owner;

    /**
     * @var integer
     *
     * @ORM\Column(name="quantity", type="integer")
     * @Assert\GreaterThan(value=0, message="Inserire una quantità positiva")
     */
    protected $quantity;

    /**
     * @var float
     *
     * @ORM\Column(name="total", type="float")
     * @Assert\GreaterThan(value=0, message="Inserire un totale positivo")
     */
    protected $total;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=20)
     * @Assert\Choice(choices = {"issued", "approved", "cancelled", "working", "complete"}, message = "Scegliere uno stato valido valido")
     */
    protected $status;

    /**
     * @var string
     *
     * @ORM\Column(name="datetime", type="datetime")
     * @Assert\NotBlank(message = "Inserire data/ora valide")
     */
    protected $datetime;


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
     * @return Request
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
     * Set total
     *
     * @param float $total
     * @return Request
     */
    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * Get total
     *
     * @return float 
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return Request
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set item
     *
     * @param \ChemLab\CatalogBundle\Entity\Item $item
     * @return Order
     */
    public function setItem(\ChemLab\CatalogBundle\Entity\Item $item = null)
    {
        $this->item = $item;

        return $this;
    }

    /**
     * Get item
     *
     * @return \ChemLab\CatalogBundle\Entity\Item 
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * Set owner
     *
     * @param \ChemLab\AccountBundle\Entity\User $owner
     * @return Order
     */
    public function setOwner(\ChemLab\AccountBundle\Entity\User $owner = null)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get owner
     *
     * @return \ChemLab\AccountBundle\Entity\User 
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set datetime
     *
     * @param \DateTime $datetime
     * @return Order
     */
    public function setDatetime($datetime)
    {
        $this->datetime = $datetime;

        return $this;
    }

    /**
     * Get datetime
     *
     * @return \DateTime 
     */
    public function getDatetime()
    {
        return $this->datetime;
    }

	public function toArray() {
		return array(
			'id' => $this->id,
			'item' => $this->item->toArray(),
			'owner' => $this->owner->toArray(),
			'quantity' => $this->quantity,
			'total' => $this->total,
			'status' => $this->status,
			'datetime' => empty($this->datetime) ? null : $this->datetime->format('c')
		);
	}
}
