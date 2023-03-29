<?php

namespace App\Controller;

use App\Entity\Comments;
use App\Entity\Posts;
use App\Form\PostType;
use App\Repository\PostsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\CommentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Doctrine\Persistence\ManagerRegistry as PersistenceManagerRegistry;


class PostController extends AbstractController
{
    #[Route('/post/{id}', name: 'app_post')]
    public function index(EntityManagerInterface $entityManager, int $id): Response
    {
        $posts = $entityManager->getRepository(Posts::class)->findBy(['id' => $id], []);

        //On met le $post[0] à null, comme ça le twig peut handle l'erreur
        if (!$posts[0]) {
            $posts = [null];
        }

        $commentForm = $this->createForm(CommentType::class);

        return $this->render('post/index.html.twig', [
            'posts' => $posts[0],
            'user' => $this->getUser(),
            'commentForm' => $commentForm->createView()
        ]);
    }
    
    #[Route('/posts', name: 'app_posts')]
    public function indexArticle(EntityManagerInterface $entityManager): Response
    {
        $posts = $entityManager->getRepository(Posts::class)->findAll();

        return $this->render('post/indexAll.html.twig', [
            'posts' => $posts,
            'user' => $this->getUser()
        ]);
    }


    
    #[Route("/post/{id}/comment/add", name: "add_comment")]
    public function addComment(Request $request, EntityManagerInterface $entityManager, Posts $post): Response
    {
        $comments = new Comments();
        $comments->setCreator($this->getUser());
        $comments->setPost($post);

        $commentForm = $this->createForm(CommentType::class, $comments);
        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $entityManager->persist($comments);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_post', [
            'id' => $post->getId()
        ]);
    }
    #[Route("/comment/{id}/like", name:"like_comment")]
    public function likeComment(Comments $comment, EntityManagerInterface $entityManager)
    {
        $comment->setLikes($comment->getLikes() + 1);
        $entityManager->flush();
    
        return $this->redirectToRoute('app_post', [
            'id' => $comment->getPost()->getId()
        ]);
    }


#[Route("/comment/{id}/dislike", name:"dislike_comment")]
public function dislikeComment(Comments $comment, EntityManagerInterface $entityManager)
{
    $comment->setDislikes($comment->getDislikes() + 1);
    $entityManager->flush();

    return $this->redirectToRoute('app_post', [
        'id' => $comment->getPost()->getId()
    ]);
}

    #[Route("/posts/remove/{id}", name:"app_delete_post")]
    
    public function deletePost(int $id, Posts $post, Comments $comment, PersistenceManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();
        $postsRepository = $entityManager->getRepository(Posts::class);
        
        if (!$post) {
            throw $this->createNotFoundException(sprintf('The post with id "%s" could not be found', $request->get('id')));
        }

        // Supprimer les commentaires du post
        $comments = $post->getComments();
        foreach ($comments as $comment) {
            $entityManager = $doctrine->getManager();
            $entityManager->remove($comment);
            $entityManager->flush();
        }

        // Supprimer le post lui-même
        $entityManager = $doctrine->getManager();
        $entityManager->remove($post);
        $entityManager->flush();

        // Redirection vers la liste des posts
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/new/post', name: 'app_new_post')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $post = new Posts();
        $form = $this->createForm(PostType::class, $post);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Ajouter le créateur du post
            $post->setCreator($this->getUser());

            // Enregistrer le post dans la base de données
            $entityManager->persist($post);
            $entityManager->flush();

            $this->addFlash('success', 'Le post a été créé avec succès !');
            return $this->redirectToRoute('app_posts');
        }

        return $this->render('post/new.html.twig', [
            'user' => $this->getUser(),
                        'form' => $form->createView(),
        ]);
    }
}