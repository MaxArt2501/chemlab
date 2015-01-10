<?php

namespace ChemLab\AccountBundle\Controller;

use ChemLab\AccountBundle\Entity\User;
use ChemLab\AccountBundle\Form\Type\RegisterType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;

use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\LockedException;
use Symfony\Component\Security\Core\Exception\DisabledException;

class SecurityController extends Controller {
	public function loginAction(Request $request) {
		$session = $request->getSession();

		// Controllo errori di login
		if ($request->attributes->has(Security::AUTHENTICATION_ERROR)) {
			$error = $request->attributes->get(
				Security::AUTHENTICATION_ERROR
			);
		} elseif (!is_null($session) && $session->has(Security::AUTHENTICATION_ERROR)) {
			$error = $session->get(Security::AUTHENTICATION_ERROR);
			$session->remove(Security::AUTHENTICATION_ERROR);
		} else {
			$error = '';
		}
		if ($error) {
			if ($error instanceof BadCredentialsException)
				$error = 'Credenziali errate';
			elseif ($error instanceof LockedException || $error instanceof DisabledException)
				$error = 'Utenza non attiva';
			elseif ($error instanceof \Exception)
				$error = $error->getMessage().' ('.get_class($error).')';

			$request->getSession()->getFlashBag()->add('danger', $error);
		}

		$last = is_null($session) ? '' : $session->get(Security::LAST_USERNAME);

		return $this->render('ChemLabAccountBundle:Security:login.html.twig', array( 'last' => $last ));
	}

	public function registerAction(Request $request) {
		$user = new User();
		$form = $this->createForm(new RegisterType(), $user);

		$form->handleRequest($request);

		if ($form->isValid()) {
			$user->setPassword($this->container->get('security.password_encoder')
					->encodePassword($user, $user->getPassword()));
			$manager = $this->getDoctrine()->getManager();
			$manager->persist($user);
			$manager->flush();

			$request->getSession()->getFlashBag()->add('success', 'Utenza creata con successo. Effettuare il login per utilizzare ChemLab.');

			return $this->redirect($this->generateUrl('login'));
		}

		return $this->render('ChemLabAccountBundle:Security:register.html.twig', array( 'form' => $form->createView() ));
	}
}
