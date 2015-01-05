<?php

namespace ChemLab\CatalogBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller {
    public function indexAction() {
        return $this->render('ChemLabCatalogBundle:Default:index.html.twig');
    }
}
