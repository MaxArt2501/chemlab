<?php

namespace ChemLab\AccountBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User
 *
 * @ORM\Table(name="appuser")
 * @ORM\Entity
 */
class User implements AdvancedUserInterface, \Serializable {
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
     * @ORM\Column(name="username", type="string", length=80, unique=true)
     * @Assert\NotBlank(message = "Il nome utente non può essere vuoto")
     */
    protected $username;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=80)
     * @Assert\NotBlank(message = "Il nome è richiesto")
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="surname", type="string", length=80)
     * @Assert\NotBlank(message = "Il cognome è richiesto")
     */
    protected $surname;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=255)
     * @Assert\NotBlank(message = "La password non può essere vuota")
     */
    protected $password;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255)
     * @Assert\NotBlank(message = "L'email non può essere vuota")
     * @Assert\Email(message = "L'indirizzo '{{ value }}' non è valido")
     */
    protected $email;

    /**
     * @var boolean
     *
     * @ORM\Column(name="gender", type="string", length=1)
     * @Assert\Regex(pattern="/^[FMN]$/", message="Genere non valido")
     */
    protected $gender;

    /**
     * @var boolean
     *
     * @ORM\Column(name="admin", type="boolean")
     */
    protected $admin;

    /**
     * @var boolean
     *
     * @ORM\Column(name="active", type="boolean")
     */
    protected $active;

    public function __construct() {
        $this->active = true;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set username
     *
     * @param string $username
     * @return User
     */
    public function setUsername($username) {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string 
     */
    public function getUsername() {
        return $this->username;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return User
     */
    public function setPassword($password) {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string 
     */
    public function getPassword() {
        return $this->password;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return User
     */
    public function setEmail($email) {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * Set admin
     *
     * @param boolean $admin
     * @return User
     */
    public function setAdmin($admin) {
        $this->admin = $admin;

        return $this;
    }

    /**
     * Get admin
     *
     * @return boolean 
     */
    public function getAdmin() {
        return $this->admin;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return User
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
     * Set surname
     *
     * @param string $surname
     * @return User
     */
    public function setSurname($surname)
    {
        $this->surname = $surname;

        return $this;
    }

    /**
     * Get surname
     *
     * @return string 
     */
    public function getSurname()
    {
        return $this->surname;
    }

    /**
     * Set gender
     *
     * @param boolean $gender
     * @return User
     */
    public function setGender($gender) {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Get gender
     *
     * @return string 
     */
    public function getGender() {
        return $this->gender;
    }

    /**
     * Set active
     *
     * @param boolean $active
     * @return User
     */
    public function setActive($active) {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active
     *
     * @return boolean 
     */
    public function getActive() {
        return $this->active;
    }

	// Funzioni imposte da AdvancedUserInterface
	public function isAccountNonExpired() {
		return $this->active;
	}

	public function isAccountNonLocked() {
		return $this->active;
	}

	public function isCredentialsNonExpired() {
		return $this->active;
	}

	public function isEnabled() {
		return $this->active;
	}

	// Funzioni imposte da UserInterface
    public function getRoles() {
		$roles = array($this->admin ? 'ROLE_ADMIN' : 'ROLE_USER');

        return $roles;
    }

	// Può resitutire null se si usa bcrypt
	public function getSalt() { return null; }

	public function eraseCredentials() {}

	// Funzioni imposte da Serializable
	public function serialize() {
		return serialize(array(
			$this->id,
			$this->username,
			$this->password
		));
	}

	public function unserialize($raw) {
        list(
            $this->id,
            $this->username,
            $this->password
        ) = unserialize($raw);
	}
}