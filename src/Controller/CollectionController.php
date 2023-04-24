<?php

namespace App\Controller;
use App\Entity\Kolekcia;
use App\Entity\Priklad;
use App\Entity\PrikladUserRelation;
use App\Repository\KolekciaRepository;
use App\Repository\PrikladRepository;
use App\Repository\PrikladUserRelationRepository;
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
class CollectionController extends AbstractController{

    public function __construct(
        private UserRepository $userRepository,
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
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function createCollection(Request $request, ManagerRegistry $managerRegistry): JsonResponse {

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
                $this->kolekciaRepository->save($kolekcia,true);
            }

            $foundPriklad = $this->prikladRepository->findOneBy(['prikladId' => $item['id']]);
            if ($foundPriklad) {
                continue;
            }

            $priklad = new Priklad();
            $priklad->setData($item['data']);
            $priklad->setImage($item['image']);
            $priklad->setSolution($item['solution']);
            $priklad->setPrikladId($item['id']);
            $priklad->setName($item['name']);
            $priklad->setCollectionId($kolekcia->getId());

            $this->prikladRepository->save($priklad,true);
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
            foreach ($foundPriklady as $priklady){
                $foundStudent->setPriklady((array)$priklady->getId());
                $students = $priklady->getStudent();
                $students[] = $foundStudent->getId();
                $priklady->setStudent($students);
                $priklady->setMaxPoints($data["maxPoints"]/count($foundPriklady));
                $this->prikladRepository->save($priklady,true);
            }
            $foundStudent->setTeacher($data["teacherId"]);
            $this->userRepository->save($foundStudent,true);
        }

        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

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