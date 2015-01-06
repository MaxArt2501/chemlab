<?php
namespace ChemLab\Utilities;

/**
 * Interfaccia per l'export delle proprietà di un oggetto in un named array,
 * e per l'import da un named array.
 */
interface ArrayEntityInterface {

	public function toArray();

	public function fromArray(array $array);

}
?>