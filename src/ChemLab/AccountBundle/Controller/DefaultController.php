<?php
namespace ChemLab\AccountBundle\Controller;

use ChemLab\AccountBundle\Entity\User;
use ChemLab\AccountBundle\Form\Type\ChangePasswordType;
use ChemLab\AccountBundle\Form\Model\ChangePassword;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller {

	public function indexAction() {
		return $this->render('ChemLabAccountBundle:Default:index.html.twig');
	}

	/**
	 * Azione per la pagina di cambiamento profilo e password. Sono gestiti due
	 * form (prfform e pwdform).
	 */
	public function profileAction(Request $request) {

		$user = $this->getUser();

		$prfform = $this->get('form.factory')->createNamedBuilder('prfform', 'form', $user)
				->add('name', 'text', array( 'label' => 'Nome', 'required' => true ))
				->add('surname', 'text', array( 'label' => 'Cognome', 'required' => true ))
				->add('email', 'email', array( 'required' => true ))
				->add('gender', 'choice', array(
					'choices'   => array( 'N' => 'N/A', 'F' => 'Femmina', 'M' => 'Maschio' ),
					'required'  => true, 'label' => 'Sesso'
				))
				->add('prfsave', 'submit', array('label' => 'Imposta profilo', 'attr' => array( 'class' => 'btn-primary' )))
				->getForm();

		$pwdform = $this->createForm(new ChangePasswordType(), new ChangePassword());

		if ($request->getMethod() === Request::METHOD_POST) {

			if ($request->request->has($prfform->getName())) {
				$form = $prfform;
				$form->handleRequest($request);
				$flash = 'Impostazioni cambiate con successo';
			} elseif ($request->request->has($pwdform->getName())) {
				$form = $pwdform;
				$form->handleRequest($request);
				$user->setPassword($this->container->get('security.password_encoder')
						->encodePassword($user, $form->getData()->getNewPassword()));
				$flash = 'Password cambiata con successo';
			}

			if (isset($form) && $form->isValid()) {
				$manager = $this->getDoctrine()->getManager();
				$manager->persist($user);
				$manager->flush();

				$request->getSession()->getFlashBag()->add('success', $flash);
			}
		}

		return $this->render('ChemLabAccountBundle:Default:profile.html.twig',
				array( 'prfform' => $prfform->createView(), 'pwdform' => $pwdform->createView() ));
	}

}
