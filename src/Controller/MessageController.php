<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/messages", name="messages_")
 */
class MessageController extends AbstractController
{
    const ATTRIBUT_TO_SERIALIZE = ['id', 'corps', 'date','mine'];

    /**
     * @Route("/{id}", name="getMessages", methods={"GET"})
     * @param Request $request
     * @param Conversation $conversation
     * @return Response
     */
    public function index(Request $request, Conversation $conversation): Response
    {
        // Ne pas pouvoir voir la conversation
        $this->denyAccessUnlessGranted('ROLE_USER');
        $this->denyAccessUnlessGranted('view', $conversation);
        $messages = $conversation->getMessages()->getValues();


        /**
         * @var $message Message
         */
        foreach ($messages as $message){
            if ($message->getUtilisateur() === $this->getUser()) {
                $message->setMine(true);
            }else{
                $message->setMine(false);
            }
        }

        return $this->json($messages, Response::HTTP_OK, [], [
            'attributes' => self::ATTRIBUT_TO_SERIALIZE
        ]);
    }

    /**
     * @Route("/{id}", name="newMessage", methods={"POST"})
     * @return JsonResponse
     */
    public function newMessage(Request $request,
                               Conversation $conversation,
                               UtilisateurRepository $utilisateurRepository,EntityManagerInterface $entityManager)
    {
        //todo Les commentaires sont uniquement là pour la phase de dev 
       //$utilisateur = $utilisateurRepository->findOneBy(array('email' => $this->getUser()->getUsername()));
        $content = $request->get('content', null);

        $message = new Message();
        $message->setCorps($content);
        $message->setDate(new \DateTime());
        //$message->setUtilisateur($utilisateur);
        $message->setUtilisateur($utilisateurRepository->find(1));
        $message->setMine(true);

        $conversation->addMessage($message);
        $conversation->setLastMessage($message);

        $entityManager->getConnection()->beginTransaction();
        try {
            $entityManager->persist($message);
            $entityManager->persist($conversation);
            $entityManager->flush();
            $entityManager->commit();
        }catch (\Exception $e){
            $entityManager->rollback();
            throw $e;
        }

        return $this->json($message, Response::HTTP_CREATED, [],[
            'attributes' => self::ATTRIBUT_TO_SERIALIZE

        ]);
    }
}
