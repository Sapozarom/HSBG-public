<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;

use App\Message\DivideFileMessage;
use App\Service\LogFileDivider\LogFileDivider;
use App\Form\Type\LogUploadType;
use App\Entity\LogFile;


class LogController extends AbstractController
{
    /**
     * Handles uploading log file
     * 
     * @Route("/upload", name="upload")
     */
    public function uploadFile(Request $request, MessageBusInterface $messageBus): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();

        $file = new LogFile();
        $file->setUser($user);

        $form = $this->createForm(LogUploadType::class, $file);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $file = $form->getData();

            $entityManager = $this->getDoctrine()->getManager();

            $entityManager->persist($file);
            $entityManager->flush();

            $this->addFlash('success', 'Your file was uploaded! In few minutes it will apear in you game history');

            $messageBus->dispatch(new DivideFileMessage($file->getId()));

            return $this->redirectToRoute('user_games');

        } elseif ($form->isSubmitted() && !($form->isValid())) {
            $this->addFlash('fail', 'Something went wrong, pleaset try again.');
        } 

        return $this->render('user/upload.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
