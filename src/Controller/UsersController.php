<?php

namespace App\Controller;


use App\Repository\UserRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;


#[Route('/api')]
class UsersController extends AbstractController
{

    public function __construct(private UserRepository $userRepository)
    {
    }


    #[Route('/me', methods: 'GET')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function me(): JsonResponse
    {
        $user = $this->userRepository->findOneBy(['email'=>$this->getUser()->getUserIdentifier()]);
        if (!$user) {
            throw $this->createNotFoundException('User was not found!');
        }

        $data = [
            'id'=>$user->getId(),
            'email'=>$user->getEmail(),
            'roles'=>$user->getRoles(),
            'name'=>$user->getName(),
            'surname'=>$user->getSurname(),
            'aisId'=>$user->getAisId(),
            'message'=>'User was found!'
        ];


        return new JsonResponse($data);
    }
}
