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

    #[Route('/', methods: 'GET')]
    #[IsGranted("ROLE_ADMIN")]
    public function index(): JsonResponse
    {
        $users = $this->userRepository->findAll();
        $data = [];

        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/{id}', methods: 'GET')]
    #[IsGranted("ROLE_ADMIN")]
    public function show(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $data = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ];

        return new JsonResponse($data);
    }

    #[Route('/', methods: 'POST')]
    #[IsGranted("ROLE_ADMIN")]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword($data['password']);
        $user->setRoles($data['roles']);

        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();

        $response = new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ]);
        $response->setStatusCode(Response::HTTP_CREATED);

        return $response;
    }

    #[Route('/{id}', methods: 'PUT')]
    #[IsGranted("ROLE_ADMIN")]
    public function update(Request $request, int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $data = json_decode($request->getContent(), true);
        $user->setEmail($data['email']);
        $user->setRoles($data['roles']);

        $em = $this->getDoctrine()->getManager();
        $em->flush();

        $response = new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ]);
        $response->setStatusCode(Response::HTTP_OK);

        return $response;
    }

    #[Route('/{id}', methods: 'DELETE')]
    #[IsGranted("ROLE_ADMIN")]
    public function delete(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($user);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
