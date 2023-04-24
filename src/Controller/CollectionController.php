<?php

namespace App\Controller;
use App\Entity\Kolekcia;
use App\Entity\Priklad;
use App\Repository\KolekciaRepository;
use App\Repository\PrikladRepository;
use App\Repository\UserRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class CollectionController extends AbstractController{
    public function __construct(private UserRepository $userRepository,
                                private KolekciaRepository $kolekciaRepository,
                                private PrikladRepository $prikladRepository){}

    #[Route('/collection', methods: 'GET')]
    public function getAll(){
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        $kolekcias = $this->kolekciaRepository->findAll();
        $data = [];
        foreach ($kolekcias as $kolekcia){
            $data[] = [
                'id' => $kolekcia->getId(),
                'name' => $kolekcia->getNameOfBlock(),
                'dateToOpen' => $kolekcia->getDateToOpen(),
                'maxPoints' => $kolekcia->getMaxPoints(),
                'message' => 'Kolekcia was found!'

            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/collection', methods: 'POST')]
    //#[IsGranted("teacher")]
    public function createCollection(Request $request): JsonResponse {

        // Clear the database table
        $em = $this->getDoctrine()->getManager();
        //$em->createQuery('DELETE FROM App\Entity\Kolekcia')->execute();
        //$em->createQuery('DELETE FROM App\Entity\Priklad')->execute();

        $data = json_decode($request->getContent(), true);
        $createdCollections = [];

        // Iterate through the data array and create a new record in the database for each item
        foreach ($data as $item) {
            $kolekcia = $this->kolekciaRepository->findOneBy(['nameOfBlock' => $item['name']]);

            if (!$kolekcia) {
                $kolekcia = new Kolekcia();
                $kolekcia->setNameOfBlock($item['name']);
            }

            $foundPriklad = $this->prikladRepository->findBy(['collectionId' => $item['id']]);
            if ($foundPriklad) {
                continue;
            }

            $priklad = new Priklad();
            $priklad->setData($item['data']);
            $priklad->setImage($item['image']);
            $priklad->setSolution($item['solution']);
            $priklad->setCollectionId($item['id']);
            $kolekcia->addPriklad($priklad);
            $em->persist($kolekcia);
            $em->persist($priklad);
            $em->flush();

            $createdCollections[] = [
                'id' => $priklad->getId(),
                'prikladId' => $priklad->getPrikladId(),
                'data' => $priklad->getData(),
                'image' => $priklad->getImage(),
                'maxPoints' => $priklad->getMaxPoints(),
                'isSubmitted' => $priklad->isIsSubmitted(),
                'isCorrect' => $priklad->isIsCorrect(),
                'solution' => $priklad->getSolution(),
                "name" => $kolekcia->getNameOfBlock(),
                "CollectionId" => $kolekcia->getId(),
            ];
        }

        $response = new JsonResponse([
            'message' => 'Collections and Priklady created successfully',
            'priklady' => $createdCollections,
        ]);
        $response->setStatusCode(Response::HTTP_CREATED);

        return $response;
    }


    #[Route('/collection', methods: 'PUT')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function updateCollection(Request $request): JsonResponse {

        $data = json_decode($request->getContent(), true);

        $foundCollection = $this->kolekciaRepository->findOneBy(["name" => $data["name"]]);
        if (!$foundCollection) {
            throw $this->createNotFoundException('Collection was not found!');
        }

        $foundCollection->setDateToOpen($data["dateToOpen"]);
        $foundCollection->setMaxPoints($data["maxPoints"]);
        $this->kolekciaRepository->save($foundCollection,true);

        $foundPriklady = $this->prikladRepository->findBy(["name" => $data["name"]]);

        foreach ($data["students"] as $student){
            $foundStudent = $this->userRepository->findOneBy(["id" => $student["id"]]);
            $foundCollection->addUser($foundStudent);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($foundCollection);
        $em->flush();

        $response = new JsonResponse([
            'id' => $foundCollection->getId(),
            'name' => $foundCollection->getNameOfBlock(),
            'dateToOpen' => $foundCollection->getDateToOpen(),
            'maxPoints' => $foundCollection->getMaxPoints(),
            'students' => $foundCollection->getUsers(),
            'message' => 'Collection was updated successfully!'
        ]);
        $response->setStatusCode(Response::HTTP_CREATED);

        return $response;
    }

    #[Route('/collection/{id}', methods: 'GET')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    //get Priklady by studentId
    public function getCollection($id): JsonResponse {
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        $priklady = $this->prikladRepository->findBy(["collectionId" => $id]);
        $data = [];
        foreach ($priklady as $priklad){
            $data[] = [
                'id' => $priklad->getId(),
                'prikladId' => $priklad->getPrikladId(),
                'data' => $priklad->getData(),
                'image' => $priklad->getImage(),
                'maxPoints' => $priklad->getMaxPoints(),
                'isSubmitted' => $priklad->isIsSubmitted(),
                'isCorrect' => $priklad->isIsCorrect(),
                'solution' => $priklad->getSolution(),
                'students' => json_decode($serializer->serialize($priklad->getStudent(), 'json'),true),
                "CollectionId" => $priklad->getCollectionId(),
            ];
        }

        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/collection/student/{id}', methods: 'GET')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function getCollectionByStudent($id): JsonResponse {
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        $student = $this->userRepository->findOneBy(["id" => $id]);
        $data = [];


        foreach ($student->getPriklady() as $priklad){
            $priklad = $this->prikladRepository->findOneBy(["id" => $priklad]);
            $data[] = [
                'id' => $priklad->getId(),
                'prikladId' => $priklad->getPrikladId(),
                'data' => $priklad->getData(),
                'image' => $priklad->getImage(),
                'maxPoints' => $priklad->getMaxPoints(),
                'isSubmitted' => $priklad->isIsSubmitted(),
                'isCorrect' => $priklad->isIsCorrect(),
                'solution' => $priklad->getSolution(),
                'students' => json_decode($serializer->serialize($priklad->getStudent(), 'json'),true),
                "CollectionId" => $priklad->getCollectionId(),
            ];
        }
        return new JsonResponse($data, Response::HTTP_OK);
    }
}