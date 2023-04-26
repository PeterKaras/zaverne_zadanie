<?php

namespace App\Controller;

use App\Entity\Kolekcia;
use App\Entity\Priklad;
use App\Repository\KolekciaRepository;
use App\Repository\PrikladRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

#[Route('/api')]
class CollectionController extends AbstractController
{

    public function __construct(
        private UserRepository $userRepository,
        private KolekciaRepository $kolekciaRepository,
        private PrikladRepository $prikladRepository
    ) {
    }

    #[Route('/collection', methods: 'GET')]
    public function getAll()
    {
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        $kolekcias = $this->kolekciaRepository->findAll();
        $data = [];
        foreach ($kolekcias as $kolekcia) {
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
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function createCollection(Request $request, ManagerRegistry $managerRegistry): JsonResponse
    {

        // Clear the database table
        $em = $managerRegistry->getManager();

        $data = json_decode($request->getContent(), true);
        $createdCollections = [];

        // Iterate through the data array and create a new record in the database for each item
        foreach ($data["mathProblems"] as $item) {
            $kolekcia = $this->kolekciaRepository->findOneBy([
                'name' => $item['name'],
                'teacher' => $data["teacherId"]
            ]);

            if (!$kolekcia) {
                $kolekcia = new Kolekcia();
                $kolekcia->setNameOfBlock($item['name']);
                $kolekcia->setTeacher($data["teacherId"]);
                $this->kolekciaRepository->save($kolekcia, true);
            }

            $foundPriklad = $this->prikladRepository->findOneBy(['prikladId' => $item['id']]);
            if ($foundPriklad || isset($item['prikladId'])) {
                continue;
            }

            $priklad = new Priklad();
            $priklad->setData($item['data']);
            $priklad->setImage($item['image']);
            $priklad->setSolution($item['solution']);
            $priklad->setPrikladId($item['id']);
            $priklad->setName($item['name']);
            $priklad->setCollectionId($kolekcia->getId());

            $this->prikladRepository->save($priklad, true);
            $encoders = [new JsonEncoder()];
            $normalizers = [new ObjectNormalizer()];
            $serializer = new Serializer($normalizers, $encoders);

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

        return new JsonResponse([
            'message' => 'Collections and Priklady created successfully',
            'priklady' => $createdCollections,
        ], Response::HTTP_CREATED);
    }

    #[Route('/collection', methods: 'PUT')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function updateCollection(Request $request): JsonResponse
    {

        $data = json_decode($request->getContent(), true);

        $foundCollection = $this->kolekciaRepository->findOneBy(["name" => $data["name"]]);
        if (!$foundCollection) {
            throw $this->createNotFoundException('Collection was not found!');
        }

        $foundCollection->setDateToOpen($data["dateToOpen"]);
        $foundCollection->setMaxPoints($data["maxPoints"]);
        $this->kolekciaRepository->save($foundCollection, true);

        $foundPriklady = $this->prikladRepository->findBy(["name" => $data["name"]]);
        if (!$foundPriklady) {
            throw $this->createNotFoundException('Priklady were not found!');
        }

        foreach ($data["students"] as $student) {
            $foundStudent = $this->userRepository->findOneBy(["id" => $student["id"]]);
            foreach ($foundPriklady as $priklady) {
                $existingPriklady = $foundStudent->getPriklady();
                $existingPriklady[] = $priklady->getId();
                $existingPriklady = array_unique($existingPriklady);
                $foundStudent->setPriklady($existingPriklady);

                $existingStudents = $priklady->getStudent();
                $existingStudents[] = $foundStudent->getId();
                $existingStudents = array_unique($existingStudents);
                $priklady->setStudent($existingStudents);

                $priklady->setMaxPoints($data["maxPoints"] / count($foundPriklady));
                $foundCollection->setStudent($existingStudents);
                $this->kolekciaRepository->save($foundCollection, true);
                $this->prikladRepository->save($priklady, true);
            }
            $foundStudent->setTeacher($data["teacherId"]);
            $this->userRepository->save($foundStudent, true);
        }

        $response = new JsonResponse([
            'id' => $foundCollection->getId(),
            'name' => $foundCollection->getNameOfBlock(),
            'dateToOpen' => $foundCollection->getDateToOpen(),
            'maxPoints' => $foundCollection->getMaxPoints(),
            'message' => 'Collection was updated successfully!'
        ]);
        $response->setStatusCode(Response::HTTP_CREATED);

        return $response;
    }

    #[Route('/collection/{id}', methods: 'GET')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    //get Priklady by studentId
    public function getCollection($id): JsonResponse
    {
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        $data = [];
        $priklady = $this->prikladRepository->findBy(["collectionId" => $id]);
        if (!$priklady) {
            return new JsonResponse($data, Response::HTTP_OK);
        }

        foreach ($priklady as $priklad) {
            $data[] = [
                'id' => $priklad->getId(),
                'prikladId' => $priklad->getPrikladId(),
                'data' => $priklad->getData(),
                'image' => $priklad->getImage(),
                'maxPoints' => $priklad->getMaxPoints(),
                'isSubmitted' => $priklad->isIsSubmitted(),
                'isCorrect' => $priklad->isIsCorrect(),
                'solution' => $priklad->getSolution(),
                'students' => json_decode($serializer->serialize($priklad->getStudent(), 'json'), true),
                "CollectionId" => $priklad->getCollectionId(),
            ];
        }

        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/collection/student/{id}', methods: 'GET')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function getCollectionByStudent($id): JsonResponse
    {
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        $data = [];
        $student = $this->userRepository->findOneBy(["id" => $id]);
        if (!$student) {
            return new JsonResponse($data, Response::HTTP_OK);
        }

        foreach ($student->getPriklady() as $priklad) {
            $priklad = $this->prikladRepository->findOneBy(["id" => $priklad]);
            if (!$priklad) {
                return new JsonResponse($data, Response::HTTP_OK);
            }
            $data[] = [
                'id' => $priklad->getId(),
                'prikladId' => $priklad->getPrikladId(),
                'data' => $priklad->getData(),
                'name' => $priklad->getName(),
                'image' => $priklad->getImage(),
                'maxPoints' => $priklad->getMaxPoints(),
                'isSubmitted' => $priklad->isIsSubmitted(),
                'isCorrect' => $priklad->isIsCorrect(),
                'solution' => $priklad->getSolution(),
                'students' => json_decode($serializer->serialize($priklad->getStudent(), 'json'), true),
                "CollectionId" => $priklad->getCollectionId(),
            ];
        }
        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/collection/teacher/{id}', methods: 'GET')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function getCollectionByTeacher($id): JsonResponse
    {
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        $teacher = $this->userRepository->findOneBy(["id" => $id]);
        if (!$teacher) {
            return new JsonResponse([], Response::HTTP_OK);
        }
        $data = [];
        $collection = $this->kolekciaRepository->findBy(["teacher" => $teacher->getId()]);
        if (!$collection) {
            return new JsonResponse($data, Response::HTTP_OK);
        }
        for ($i = 0; $i < count($collection); $i++) {
            $priklady = $this->prikladRepository->findBy(["name" => $collection[$i]->getNameOfBlock()]);
            foreach ($priklady as $priklad) {
                $priklad = $this->prikladRepository->findOneBy(["id" => $priklad]);
                $data[] = [
                    'id' => $priklad->getId(),
                    'prikladId' => $priklad->getPrikladId(),
                    'name' => $priklad->getName(),
                    'data' => $priklad->getData(),
                    'image' => $priklad->getImage(),
                    'maxPoints' => $priklad->getMaxPoints(),
                    'isSubmitted' => $priklad->isIsSubmitted(),
                    'isCorrect' => $priklad->isIsCorrect(),
                    'solution' => $priklad->getSolution(),
                    'students' => json_decode($serializer->serialize($priklad->getStudent(), 'json'), true),
                    "CollectionId" => $priklad->getCollectionId(),
                ];
            }
        }
        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/generateExample', methods: 'GET')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function generateExample()
    {
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        $priklady = $this->prikladRepository->findAll();

        if (!$priklady) {
            return new JsonResponse([], Response::HTTP_OK);
        }

        $randomIndex = rand(0, count($priklady) - 1);
        $randomPriklad = $priklady[$randomIndex];

        $data = [
            'id' => $randomPriklad->getId(),
            'prikladId' => $randomPriklad->getPrikladId(),
            'name' => $randomPriklad->getName(),
            'data' => $randomPriklad->getData(),
            'image' => $randomPriklad->getImage(),
            'maxPoints' => $randomPriklad->getMaxPoints(),
            'isSubmitted' => $randomPriklad->isIsSubmitted(),
            'isCorrect' => $randomPriklad->isIsCorrect(),
            'solution' => $randomPriklad->getSolution(),
            'students' => json_decode($serializer->serialize($randomPriklad->getStudent(), 'json'), true),
            "CollectionId" => $randomPriklad->getCollectionId(),
        ];

        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/submit', methods: 'POST')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function submit(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $foundPriklad = $this->prikladRepository->findOneBy(["id" => $data["id"]]);
        if (!$foundPriklad) {
            return new JsonResponse(['error' => 'Priklad not found'], Response::HTTP_NOT_FOUND);
        }
        foreach ($foundPriklad["student"] as $student) {
            if ($student->getId() == $data["studentId"]) {
                $foundPriklad->setIsSubmitted(true);
                $foundPriklad->setSolution($data["solution"]);
                $this->prikladRepository->save($foundPriklad, true);
                break;
            }
        }

        $response = new JsonResponse([
            'id' => $foundPriklad->getId(),
            'prikladId' => $foundPriklad->getPrikladId(),
            'data' => $foundPriklad->getData(),
            'image' => $foundPriklad->getImage(),
            'maxPoints' => $foundPriklad->getMaxPoints(),
            'isSubmitted' => $foundPriklad->isIsSubmitted(),
            'isCorrect' => $foundPriklad->isIsCorrect(),
            'solution' => $foundPriklad->getSolution(),
            'students' => $foundPriklad->getStudent(),
            "CollectionId" => $foundPriklad->getCollectionId(),
            'message' => 'Priklad was updated successfully!'
        ]);
        $response->setStatusCode(Response::HTTP_CREATED);

        return $response;
    }
}
