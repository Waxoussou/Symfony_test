<?php

namespace App\Controller;

use App\Entity\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class ApiController extends AbstractController
{
    /**
     * @Route("/api/registration", name="api_registration", methods={"POST"})
     */
    public function index(Request $req, UserPasswordEncoderInterface $encoder)
    {
        $body = $req->request;
        $email = $body->get('email');
        $password = $body->get('password');
        $name = $body->get('name');
        $lastname = $body->get('lastname');

        try {
            $entityManager = $this->getDoctrine()->getManager();
            $user = new User();
            $user->setEmail($email);
            $user->setName($name);
            $user->setLastname($lastname);
            $encoded = $encoder->encodePassword($user, $password);
            $user->setPassword($encoded);

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->json(
                'new User ' .  $user->getId(),
                200,
                ["Content-Type" => "application/json"]
            );
        } catch (\Throwable $th) {
            //throw $th;
            dd($th);
        }
    }

    /**
     * @Route("/api/login", name="login", methods={"POST"})
     */
    public function apiLogin(Request $req, UserPasswordEncoderInterface $encoder)
    {
        $body = $req->request;
        $email = $body->get('email');
        $password = $body->get('password');

        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->findOneBy(array('email' => $email));
        $isValidPassword = $encoder->isPasswordValid($user, $password);

        if (!$isValidPassword) {
            return $this->json('wrong password', 401, ["Content-Type" => "application/json"]);
        }
        $token = json_encode(["user" => $user->getEmail(), "uid" => $user->getId()]);
        // sets a HTTP response header
        $resp = new Response();
        $resp->headers->set('X-AUTH-TOKEN',  $token);
        $resp->headers->set('Content-Type',  "application/json");
        $resp->setContent(json_encode(["name" => $user->getName(), "lastname" => $user->getLastname()]));

        // prints the HTTP headers followed by the content
        $resp->send();


        return $this->json(["name" => $user->getName(), "lastname" => $user->getLastname()], 200, ["Content-Type" => "application/json"]);
    }

    /**
     * @Route("/api/userDetails", name="user_details", methods={"GET"})
     */
    public function getUserDetails(Request $request)
    {
        $has_token = $request->headers->has('X-AUTH-TOKEN');
        if (!$has_token) {
            throw new BadCredentialsException("no token found", 1);
        } else {
            $auth_token = $request->headers->get('X-AUTH-TOKEN');
            dd($auth_token);
        }

        return $this->json('name & lastname', 200, ["Content-Type" => "application/json"]);
    }

    /**
     * @Route("/api/logout", name="logout", methods={"GET"})
     */
    public function apiLogout()
    {
        return $this->json('logout', 200, ["Content-Type" => "application/json"]);
    }
}
