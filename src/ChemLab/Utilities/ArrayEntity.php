<?php
namespace ChemLab\Utilities;

/**
 * Implementazione di default di ArrayEntityInterface
 */
class ArrayEntity implements ArrayEntityInterface {

	/**
	 * Restituisce semplicemente tutte le proprietà pubbliche e protette
	 * Per includere le proprietà private, o per qualcosa di più avanzato,
	 * usare ReflectionClass.
	 * @link http://php.net/manual/en/class.reflectionclass.php
	 * Come alternative si può usare il Serializer di Symfony, decodificando un
	 * output JSON; oppure JMS/Serializer
	 * @return array
	 */
	public function toArray() {
		return get_object_vars($this);
	}

	/**
	 * Importa le proprietà da un named array.
	 */
	public function fromArray(array $array) {
		foreach ($array as $key => $value)
			if (property_exists($this, $key))
				$this->{$key} = $value;
	}

}
?>