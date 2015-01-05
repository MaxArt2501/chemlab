<?php

namespace ChemLab\MainBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

class TwigExt extends \Twig_Extension {
    protected $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function getFunctions() {
        return array(
			new \Twig_SimpleFunction('bundleExists', array($this, 'bundleExists')),
			new \Twig_SimpleFunction('routeExists', array($this, 'routeExists'))
        );
    }

    public function bundleExists($bundle){
        return array_key_exists($bundle, $this->container->getParameter('kernel.bundles'));
    }

    public function routeExists($route){
		return $this->container->get('router')->getRouteCollection()->get($route) !== null;
    }

    public function getName() {
        return 'chem_lab_main.twig_ext';
    }
}