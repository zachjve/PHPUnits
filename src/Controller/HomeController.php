<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class HomeController extends AbstractController
{
    /**
     * @Route("/home", name="home")
     */
    public function index(): Response
    {
        $user = $this->getUser();

        return $this->render('home/index.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/profile", name="profile")
     */
    public function profile(): Response
    {
        // get the currently logged in user
        $user = $this->getUser();

        // If the user is not logged in, redirect them to the login page
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // render the profile template and pass the user object
        return $this->render('home/profile.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/runtests", name="run_tests")
     */
    public function runTests(): Response
    {
        $process = new Process(['php', '../vendor/bin/phpunit', '../tests/Units/UserTest.php']);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        // Le retour est le contenu que PHPUnit affiche généralement dans la console.
        $output = $process->getOutput();
        // Vous pouvez renvoyer cette sortie dans une vue ou dans une réponse JSON si vous préférez.
        return $this->render('home/results.html.twig', ['output' => $output]);
    }
}
