<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    #[Route('/api/user', name: 'create_user', methods: 'POST')]
    public function createUser(Request $request, EntityManagerInterface $em): Response
    {
        try {
            $data = json_decode($request->getContent(), true);

            $user = new User();
            $user->setFullName($data['fullName']);
            $user->setEmail($data['email']);
            $user->setPhone($data['phone'] ?? null);
            $user->setAge($data['age'] ?? null);

            $em->persist($user);
            $em->flush();

            return $this->json(['id' => $user->getId()], Response::HTTP_CREATED);
        } catch (\Exception $exception) {
            return $this->json(['message' => 'Server encounter an error. Please try again'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/user/{id}', name: 'update_user', methods: 'PUT')]
    public function updateUser(int $id, Request $request, EntityManagerInterface $em): Response
    {
        try {
            $user = $em->getRepository(User::class)->find($id);

            if (!$user) {
                return $this->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);

            if (isset($data['fullName'])) {
                $user->setFullName($data['fullName']);
            }

            if (isset($data['email'])) {
                $user->setEmail($data['email']);
            }

            if (isset($data['phone'])) {
                $user->setPhone($data['phone']);
            }

            if (isset($data['age'])) {
                $user->setAge($data['age']);
            }

            $em->flush();

            return $this->json(['message' => 'User updated successfully']);
        } catch (\Exception $exception) {
            return $this->json(['message' => 'Server encounter an error. Please try again'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/user/{id}', name: 'delete_user', methods: 'DELETE')]
    public function deleteUser(int $id, EntityManagerInterface $em): Response
    {
        try {
            $user = $em->getRepository(User::class)->find($id);

            if (!$user) {
                return $this->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            $em->remove($user);
            $em->flush();

            return $this->json(['message' => 'User deleted successfully']);
        } catch (\Exception $exception) {
            return $this->json(['message' => 'Server encounter an error. Please try again'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/users/search', name: 'search_users', methods: 'GET')]
    public function searchUsers(Request $request, EntityManagerInterface $em): Response
    {
        try {
            $email = $request->query->get('email');
            $phone = $request->query->get('phone');
            $sortField = $request->query->get('sortField', 'id');

            $conn = $em->getConnection();

            if (!empty($email) && !empty($phone)) {
                $filter = " AND email like :email AND phone like :phone ";
            } else if (!empty($email) && empty($phone)) {
                $filter = " AND email like :email ";
            } else if (empty($email) && !empty($phone)) {
                $filter = " AND phone like :phone ";
            }  else {
                $filter = "";
            }

            $sql = "SELECT *
            FROM user u
            WHERE 1 = 1
            $filter
            ORDER BY $sortField ASC";

            $stmt = $conn->prepare($sql);
            if (!empty($email)) {
                $stmt->bindValue(':email', "%$email%");
            }
            if (!empty($phone)) {
                $stmt->bindValue(':phone', "%$phone%");
            }
            $resultSet = $stmt->executeQuery();

            return $this->json($resultSet->fetchAllAssociative());
        } catch (\Exception $exception) {
            return $this->json(['message' => 'Server encounter an error. Please try again'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
