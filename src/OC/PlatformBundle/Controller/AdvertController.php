<?php
	// src/OC/PlatformBundle/Controller/AdvertController.php
	
	namespace OC\PlatformBundle\Controller;
	
	use Symfony\Bundle\FrameworkBundle\Controller\Controller;
	use Symfony\Component\HttpFoundation\Request;
	use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
	use OC\PlatformBundle\Entity\Advert;
	
	use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
	use Symfony\Component\Form\Extension\Core\Type\DateType;
	use Symfony\Component\Form\Extension\Core\Type\FormType;
	use Symfony\Component\Form\Extension\Core\Type\SubmitType;
	use Symfony\Component\Form\Extension\Core\Type\TextareaType;
	use Symfony\Component\Form\Extension\Core\Type\TextType;
    use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
	
	
	class AdvertController extends Controller
	{
		
		public function menuAction($limit)
		{
			$em = $this->getDoctrine()->getManager();
			
			$listAdverts = $em->getRepository('OCPlatformBundle:Advert')->findBy(
			array(),                 // Pas de critère
			array('date' => 'desc'), // On trie par date décroissante
			$limit,                  // On sélectionne $limit annonces
			0                        // À partir du premier
			);
			
			return $this->render('OCPlatformBundle:Advert:menu.html.twig', array(
			'listAdverts' => $listAdverts
			));
		}
		
		
		public function indexAction($page)
		{
			if ($page < 1) {
				throw new NotFoundHttpException('Page "'.$page.'" inexistante.');
			}
			
			// Pour récupérer la liste de toutes les annonces : on utilise findAll()
			$listAdverts = $this->getDoctrine()
			->getManager()
			->getRepository('OCPlatformBundle:Advert')
			->findAll()
			;
			
			// L'appel de la vue ne change pas
			return $this->render('OCPlatformBundle:Advert:index.html.twig', array(
			'listAdverts' => $listAdverts,
			));
		}
		
		
		public function viewAction($id)
		{
			$em = $this->getDoctrine()->getManager();
			$advert = $em->getRepository('OCPlatformBundle:Advert')->find($id);
			
			// $advert est donc une instance de OC\PlatformBundle\Entity\Advert
			// ou null si l'id $id  n'existe pas, d'où ce if :
			if (null === $advert) {
				throw new NotFoundHttpException("L'annonce d'id ".$id." n'existe pas.");
			}
			
			// Le render ne change pas, on passait avant un tableau, maintenant un objet
			return $this->render('OCPlatformBundle:Advert:view.html.twig', array(
			'advert' => $advert
			));
		}
		
		
		public function editAction($id, Request $request)
		{
			$em = $this->getDoctrine()->getManager();
			
			// Récupération d'une annonce déjà existante, d'id $id.
			$advert = $this->getDoctrine()->getManager()->getRepository('OCPlatformBundle:Advert')->find($id);
			
			// Et on construit le formBuilder avec cette instance de l'annonce, comme précédemment
			$formBuilder = $this->get('form.factory')->createBuilder(FormType::class, $advert);
			
			// On ajoute les champs de l'entité que l'on veut à notre formulaire
			$formBuilder->add('date',      DateType::class);
			$formBuilder->add('title',     TextType::class);
			$formBuilder->add('content',   TextareaType::class);
			$formBuilder->add('author',    TextType::class);
            $formBuilder->add('type', ChoiceType::class, array(
                'choices' => array('CDI' => 'CDI', 'CDD' => 'CDD', 'Alternance' => 'Alternance'
                )));
			$formBuilder->add('published', CheckboxType::class);
			$formBuilder->add('save',      SubmitType::class);
			// À partir du formBuilder, on génère le formulaire
			$form = $formBuilder->getForm();
			
			if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
				// Inutile de persister ici, Doctrine connait déjà notre annonce
				$em->flush();
				
				$request->getSession()->getFlashBag()->add('notice', 'Annonce bien modifiée.');
				
				return $this->redirectToRoute('oc_platform_view', array('id' => $advert->getId()));
			}
			
			return $this->render('OCPlatformBundle:Advert:edit.html.twig', array('form' => $form->createView(),'advert' => $advert));
		}
		
		
		public function addAction(Request $request)
		{
			// On crée un objet Advert
			$advert = new Advert();
			
			// On crée le FormBuilder grâce au service form factory
			$formBuilder = $this->get('form.factory')->createBuilder(FormType::class, $advert);
			
			// On ajoute les champs de l'entité que l'on veut à notre formulaire
			$formBuilder->add('date',      DateType::class);
			$formBuilder->add('title',     TextType::class);
			$formBuilder->add('content',   TextareaType::class);
			$formBuilder->add('author',    TextType::class);
            $formBuilder->add('type', ChoiceType::class, array(
                'choices' => array('CDI' => 'CDI', 'CDD' => 'CDD', 'Alternance' => 'Alternance'
                )));
			$formBuilder->add('published', CheckboxType::class);
			$formBuilder->add('save',      SubmitType::class);
			
			// À partir du formBuilder, on génère le formulaire
			$form = $formBuilder->getForm();
			// Si la requête est en POST
			if ($request->isMethod('POST')) {
				// On fait le lien Requête <-> Formulaire
				// À partir de maintenant, la variable $advert contient les valeurs entrées dans le formulaire par le visiteur
				$form->handleRequest($request);
				
				// On vérifie que les valeurs entrées sont correctes
				if ($form->isValid()) {
					// On enregistre notre objet $advert dans la base de données, par exemple
					$em = $this->getDoctrine()->getManager();
					$em->persist($advert);
					$em->flush();
					
					$request->getSession()->getFlashBag()->add('notice', 'Annonce bien enregistrée.');
					
					// On redirige vers la page de visualisation de l'annonce nouvellement créée
					return $this->redirectToRoute('oc_platform_view', array('id' => $advert->getId()));
				}
			}
			
			// cas d'une requete GET ou de champs non valides : on réaffiche le formulaire
			return $this->render('OCPlatformBundle:Advert:add.html.twig', array(
			'form' => $form->createView(),
			));
		}
		
		
		
		public function deleteAction(Request $request, $id)
		{
			$em = $this->getDoctrine()->getManager();
			$advert = $em->getRepository('OCPlatformBundle:Advert')->find($id);
			if (null === $advert) {
				throw new NotFoundHttpException("L'annonce d'id ".$id." n'existe pas.");
			}
			
			$em->remove($advert);
			$em->flush();
			$request->getSession()->getFlashBag()->add('info', "L'annonce a bien été supprimée.");
			return $this->redirectToRoute('oc_platform_home');			
		}

        public function userAction($name)
        {
            $listAdverts = $this->getDoctrine()
                ->getManager()
                ->getRepository('OCPlatformBundle:Advert')
                ->findBy(['author'=>$name]);
            ;

            return $this->render('OCPlatformBundle:Advert:user.html.twig', array(
                'listAdverts' => $listAdverts
            ));
        }

        public function typeAction($name)
        {
            $listAdverts = $this->getDoctrine()
                ->getManager()
                ->getRepository('OCPlatformBundle:Advert')
                ->findBy(['type'=>$name]);
            ;

            return $this->render('OCPlatformBundle:Advert:type.html.twig', array(
                'listAdverts' => $listAdverts
            ));
        }
		
	}
	
