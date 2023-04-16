<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegisterController extends AbstractController
{

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $JWTTokenManager,
        private UserRepository $userRepository,
    ) {
    }

    #[Route('/api/auth/register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $content = json_decode($request->getContent(), true);

        if (empty($content['email']) || empty($content['password'] || empty($content['role']))) {
            return new JsonResponse(["message"=>"Missing one of parameters: email, password", "code"=>Response::HTTP_NOT_ACCEPTABLE], Response::HTTP_NOT_ACCEPTABLE);
        }

        if ($this->userRepository->findBy(['email' => $content['email']])) {
            return new JsonResponse(["message"=>"Email already registered!", "code"=>Response::HTTP_CONFLICT], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setRoles($content['role']);
        $user->setEmail($content['email']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $content['password']));

        try {
            $this->userRepository->save($user, true);
        } catch (\Exception $exception) {
            return new JsonResponse(["message"=>$exception->getMessage(), "code"=>$exception->getCode()], 500);
        }

        $data = [
            'message' => "created User",
        ];

        return new JsonResponse($data, Response::HTTP_CREATED);
    }
}