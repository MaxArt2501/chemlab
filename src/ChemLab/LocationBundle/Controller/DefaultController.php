<?php

namespace ChemLab\LocationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller {
    public function indexAction() {
        return $this->render('ChemLabLocationBundle:Default:index.html.twig');
    }
}
