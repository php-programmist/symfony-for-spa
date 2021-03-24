<?php


namespace App\Controller\Frontend;


use App\Exception\FrontendRoutingFailException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends AbstractController
{
    /**
     * @Route("/email/{uuid}/confirm",
     *      name="email_confirm",
     *      requirements={"uuid": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"}
     * )
     * @param string $uuid
     * @return Response
     * @throws FrontendRoutingFailException
     */
    public function confirm(string $uuid): Response
    {
        throw new FrontendRoutingFailException();
    }

    /**
     * @Route("/password/reset/{token}/{id}",
     *      name="password_reset",
     *      requirements={
     *          "id": "\d+",
     *          "token": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"
     *      }
     * )
     * @param int $id
     * @param string $uuid
     * @return Response
     * @throws FrontendRoutingFailException
     */
    public function passwordReset(int $id, string $uuid): Response
    {
        throw new FrontendRoutingFailException();
    }
}