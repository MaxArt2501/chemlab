<?php
namespace ChemLab\Utilities;

/**
 * Classe astratta per l'export delle proprietà di un oggetto in un named array,
 * e per l'import da un named array.
 */
class ArrayEntity {

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
	 * Importa le proprietà da un named array. Inserisce solo i valori
	 * di proprietà pubbliche o protette (per altro, si veda su).
	 */
	public function fromArray(array $array) {
		$props = get_object_vars($this);
		foreach ($array as $key => $value)
			if (array_key_exists($key, $props))
				$this->{$key} = $value;
	}

}
?>