<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact', methods: ['GET', 'POST'])]
    public function contact(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        $form = [
            'name' => $user instanceof User ? (string) $user->getName() : '',
            'email' => $user instanceof User ? (string) $user->getEmail() : '',
            'subject' => '',
            'message' => '',
        ];
        $errors = [];

        if ($request->isMethod('POST')) {
            foreach (array_keys($form) as $field) {
                $form[$field] = trim($request->request->getString($field));
            }
            $form['email'] = mb_strtolower($form['email']);

            if (!$this->isCsrfTokenValid('contact_form', $request->request->getString('_token'))) {
                $errors[] = 'Invalid security token. Please refresh the page and try again.';
            }
            if ($form['name'] === '' || mb_strlen($form['name']) > 120) {
                $errors[] = 'Please enter your name (maximum 120 characters).';
            }
            if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($form['email']) > 190) {
                $errors[] = 'Please enter a valid email address.';
            }
            if ($form['subject'] === '' || mb_strlen($form['subject']) > 190) {
                $errors[] = 'Please enter a subject (maximum 190 characters).';
            }
            if (mb_strlen($form['message']) < 10 || mb_strlen($form['message']) > 2000) {
                $errors[] = 'Your message must be between 10 and 2000 characters.';
            }

            if ($errors === []) {
                $message = new Message();
                $message->setName($form['name']);
                $message->setEmail($form['email']);
                $message->setSubject($form['subject']);
                $message->setMessage($form['message']);
                if ($user instanceof User) {
                    $message->setUser($user);
                }

                $entityManager->persist($message);
                $entityManager->flush();

                $this->addFlash('success', 'Thank you! Your message has been sent.');

                return $this->redirectToRoute('contact');
            }
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form,
            'errors' => $errors,
        ]);
    }
}
