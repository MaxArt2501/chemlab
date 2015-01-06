<?php

namespace ChemLab\RequestBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller {
    public function indexAction() {
        return $this->render('ChemLabRequestBundle:Default:index.html.twig');
    }
}
