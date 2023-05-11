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



use MathPHP\Algebra;
use MathPHP\ExpressionParser;
use MathPHP\Algebra\Term;
use MathPHP\Expression\Expression;

use MathParser\StdMathParser;
use MathParser\Interpreting\Evaluator;
use MathParser;





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
            foreach ($foundPriklady as $priklad) {
                $newPriklad = new Priklad();
                $newPriklad->setData($priklad->getData());
                $newPriklad->setImage($priklad->getImage());
                $newPriklad->setSolution($priklad->getSolution());
                $newPriklad->setPrikladId($priklad->getPrikladId());
                $newPriklad->setName($priklad->getName());
                $newPriklad->setCollectionId($foundCollection->getId());
                $newPriklad->setMaxPoints($data["maxPoints"] / count($foundPriklady));
                $newPriklad->setSingleStudent($foundStudent->getId());
                $newPriklad->setStudent([]); // Initialize the property
                $this->prikladRepository->save($newPriklad, true);
        
                $existingPriklady = $foundStudent->getPriklady();
                $existingPriklady[] = $newPriklad->getId();
                $existingPriklady = array_unique($existingPriklady);
                $foundStudent->setPriklady($existingPriklady);
        
                $existingStudents = $newPriklad->getStudent();
                $existingStudents[] = $foundStudent->getId();
                $existingStudents = array_unique($existingStudents);
                $newPriklad->setStudent($existingStudents);
        
                $foundCollection->setStudent($existingStudents);
                $this->kolekciaRepository->save($foundCollection, true);
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
        $priklady = $this->prikladRepository->findBy(["singleStudent" => $id]);
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
                'result' => $priklad->getResult(),
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
            return new JsonResponse([], Response::HTTP_NOT_FOUND);
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

    #[Route('/generateExample/{id}', methods: 'GET')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function generateExample($id)
    {
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        $student = $this->userRepository->findOneBy(["id" => $id]);
        if (!$student) {
            return new JsonResponse([], Response::HTTP_NOT_FOUND);
        }

        $priklady = $this->prikladRepository->findAll();

        if (!$priklady) {
            return new JsonResponse([], Response::HTTP_OK);
        }

        $randomIndex = rand(0, count($priklady) - 1);
        $randomPriklad = $priklady[$randomIndex];
        $newPriklad = new Priklad();
        $newPriklad->setData($randomPriklad->getData());
        $newPriklad->setImage($randomPriklad->getImage());
        $newPriklad->setSolution($randomPriklad->getSolution());
        $newPriklad->setPrikladId($randomPriklad->getPrikladId());
        $newPriklad->setName($randomPriklad->getName());
        $newPriklad->setCollectionId(-1);
        $newPriklad->setMaxPoints(1);
        $newPriklad->setSingleStudent($student->getId());
        $newPriklad->setStudent([]); // Initialize the property
        $this->prikladRepository->save($newPriklad, true);

        $data = [
            'id' => $newPriklad->getId(),
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
        
        $expr1 = $data["solution"];
        $result1 =0;
        $result2=0;
        $expr2=0;
        

        $foundPriklad->setIsSubmitted(true);
        $foundPriklad->setResult($data["solution"]);

        $expr2 = $foundPriklad->getSolution();

        $expr1 = preg_replace('/\s+/', '', $expr1);
        $expr2 = preg_replace('/\s+/', '', $expr2);

        $expr1 = str_replace('\frac', '', $expr1);
        $expr1 = str_replace('\\','',$expr1);
        $expr2 = str_replace('\dfrac', '', $expr2);
        $expr2 = str_replace('\frac', '', $expr2);
        $expr1 = str_replace('{', '', $expr1);
        $expr2 = str_replace('{', '', $expr2);
        $expr1 = str_replace('}', '', $expr1);
        $expr2 = str_replace('}', '', $expr2);
        $expr1 = str_replace('(', '', $expr1);
        $expr2 = str_replace('(', '', $expr2);
        $expr1 = str_replace(')', '', $expr1);
        $expr2 = str_replace(')', '', $expr2);
        $foundPriklad->setIsCorrect(false);
        $foundPriklad->setIsSubmitted(true);

        /*if (strpos($expr2, "=") !== false) {
            $parts = explode("=", $expr2);


            $y_t = $parts[0]; 
            $result1 = $parts[1]; 
            $result2 = $parts[2]; 

            $result2 = $y_t . '=' . $result2;
            $result1 = $y_t . '=' . $result1;

            if(($expr1 == $result1) || ($expr1== $result2) ){
                $point = $foundPriklad->getMaxPoints();
                $foundPriklad->setGainedPoints($point);
                $foundPriklad->setIsCorrect(true);
            }
            else{
                $foundPriklad->setGainedPoints(0);
                $foundPriklad->setIsCorrect(false);
            }
        } 

        elseif($expr1 == $expr2){
            $point = $foundPriklad->getMaxPoints();
            $foundPriklad->setGainedPoints($point);
            $foundPriklad->setIsCorrect(true);
        }
        else{
            $foundPriklad->setGainedPoints(0);
            $foundPriklad->setIsCorrect(false);
        }*/
            
        
        
        $this->prikladRepository->save($foundPriklad, true);
        
        $response = new JsonResponse([
            'res1'=>$result1,
            'res2'=>$result2,
            'expr1'=>$expr1,
            'expr2'=>$expr2,
            'id' => $foundPriklad->getId(),
            'prikladId' => $foundPriklad->getPrikladId(),
            'data' => $foundPriklad->getData(),
            'image' => $foundPriklad->getImage(),
            'maxPoints' => $foundPriklad->getMaxPoints(),
            'isSubmitted' => $foundPriklad->isIsSubmitted(),
            'isCorrect' => $foundPriklad->isIsCorrect(),
            'solution' => $foundPriklad->getSolution(),
            "CollectionId" => $foundPriklad->getCollectionId(),
            'message' => 'Priklad was updated successfully!'
        ]);
        $response->setStatusCode(Response::HTTP_CREATED);

        return $response;
    }
}
