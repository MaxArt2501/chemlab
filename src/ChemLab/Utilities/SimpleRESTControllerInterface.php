<?php
namespace ChemLab\Utilities;

use Symfony\Component\HttpFoundation\Request;

interface SimpleRESTControllerInterface {

	public function restAction($id, Request $request);

	public function listAction($start, $end, $sort, Request $request);

}
?>