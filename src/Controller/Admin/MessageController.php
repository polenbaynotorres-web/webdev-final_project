<?php

namespace App\Controller\Admin;

use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard/messages', name: 'admin_message_')]
#[IsGranted('ROLE_ADMIN')]
final class MessageController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(MessageRepository $messageRepository): Response
    {
        return $this->render('admin/message/index.html.twig', [
            'messages' => $messageRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id, MessageRepository $messageRepository): Response
    {
        $message = $messageRepository->find($id);

        if (!$message) {
            throw $this->createNotFoundException('Message not found.');
        }

        return $this->render('admin/message/show.html.twig', ['message' => $message]);
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        int $id,
        Request $request,
        MessageRepository $messageRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $message = $messageRepository->find($id);

        if (!$message) {
            throw $this->createNotFoundException('Message not found.');
        }

        if (!$this->isCsrfTokenValid('message_delete_' . $id, $request->request->getString('_token'))) {
            $this->addFlash('danger', 'Invalid deletion request.');

            return $this->redirectToRoute('admin_message_index');
        }

        $entityManager->remove($message);
        $entityManager->flush();

        $this->addFlash('success', 'Message deleted.');

        return $this->redirectToRoute('admin_message_index');
    }
}
