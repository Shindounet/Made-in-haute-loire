<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\WebLink\Link;

/**
 * @Route("/conversation", name="conversation_")
 */
class ConversationController extends AbstractController
{
    /**
     * @var UtilisateurRepository
     */
    private $utilisateurRepository;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var Utilisateur
     */
    private $utilisateur;

    public function __construct(UtilisateurRepository  $utilisateurRepository,
                                EntityManagerInterface $entityManager)
    {

        $this->utilisateurRepository = $utilisateurRepository;
        $this->entityManager = $entityManager;
        $this->utilisateur = $this->utilisateurRepository->findOneBy(array('email' => $this->getUser()->getUsername()));
    }

    /**
     * @Route("/newConversation/{id}", name="newConversation", methods={"POST"})
     * @throws \Exception
     * @return JsonResponse
     */
    public function index(Request $request , int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
         $otherUser = $this->utilisateurRepository->find($id);
         $utilisateur = $this->utilisateurRepository->findOneBy(array('email' => $this->getUser()->getUsername()));


         if (is_null($otherUser)){ // L'utilitaire choisi n'existe pas
             throw new \Exception("L'utilisateur n'a pas été trouvé ");

         } elseif ($otherUser->getid() === $utilisateur->getId() ){ // Il est impossible de créer avec soi-même
             throw new \Exception("Vous ne pouvez pas créer de conversation avec vous-même ");
         }
         $conversation = $utilisateur->findConversationByParticipants($otherUser);
         if ($conversation){
             throw new \Exception("la conversation existe déjà");
         }
         $conversation = new Conversation();
         $conversation->addUser($otherUser);
         $conversation->addUser($utilisateur);

         $this->entityManager = $this->getDoctrine()->getManager();
         $this->entityManager->persist($conversation);
         $this->entityManager->flush();




         return $this->json([
             'id' => $conversation->getId()
         ],Response::HTTP_CREATED,[],[]
         );
    }

    /**
     * @Route ("/",name= "Conversation" ,methods={"POST"})
     * @return JsonResponse
     */
    public function getConvs(Request $request){
        $this->denyAccessUnlessGranted('ROLE_USER');

        $hubUrl = $this->getParameter('mercure.default_hub');
        $this->addLink($request, new Link('mercure',$hubUrl));

        $conversation = $this->utilisateur->getConversations();
        return $this->json($conversation);
    }
}
