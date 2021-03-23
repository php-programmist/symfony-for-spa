<?php


namespace App\Controller;


use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    /**
     * @Route(path="/api/me",name="api_me")
     * @return RedirectResponse
     */
    public function meAction(): RedirectResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        return $this->redirectToRoute('api_users_get_item', ['id' => $user->getId()]);
    }
}