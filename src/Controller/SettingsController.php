<?php

namespace App\Controller;

use App\Entity\Privacy;
use App\Entity\Tag;
use App\Repository\TagRepository;
use App\Service\TagService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use League\CommonMark\CommonMarkConverter;

#[IsGranted('ROLE_EMAIL_VERIFIED')]
class SettingsController extends AbstractController {
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private TagRepository $tagRepo;
    private TagService $tagService;
    private readonly string $picPath;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger, TagRepository $tagRepo, TagService $tagService) {
        $this->picPath = $_ENV['PIC_PATH'] . DIRECTORY_SEPARATOR;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->tagRepo = $tagRepo;
        $this->tagService = $tagService;
    }

    #[Route('/settings', name: 'settings', methods: ['GET'])]
    public function settings(): Response {
        return $this->render('settings/index.html.twig');
    }

    #[Route('/password/reset', name: 'reset_password', methods: ['GET'])]
    public function passwordReset(): Response {
        return $this->render('settings/password.html.twig');
    }

    #[Route('/password/reset', name: 'reset_password_action', methods: ['POST'])]
    public function passwordResetAction(): Response {
        return new Response();
    }

    #[Route('/settings/display', name: 'display_settings', methods: ['GET'])]
    public function displaySettings(): Response {
        $user = $this->getUser();
        return $this->render('settings/display.html.twig', [
            'useCompactView' => $user->getOptions()->useCompactView(),
            'privacy' => $user->getPrivacy(),
            'publicPagesEnabled' => (bool)$_ENV['PUBLIC_PAGES_ENABLED'],
        ]);
    }

    #[Route('/settings/display', name: 'display_settings_action', methods: ['POST'])]
    public function displaySettingsAction(Request $request, EntityManagerInterface $entityManager): Response {
        $useCompactView = $request->get('useCompactView') ?? false;
        $options = $this->getUser()->getOptions();
        $options->setCompactView($useCompactView);
        $entityManager->flush($options);

        $privacy = $this->getUser()->getPrivacy();
        if($_ENV['PUBLIC_PAGES_ENABLED']) {
            $privacy->makeCallNamePrivate((bool)$request->get('privacy_callname'))
                ->makeShowNamePrivate((bool)$request->get('privacy_showname'))
                ->makeNotesPrivate((bool)$request->get('privacy_notes'))
                ->makeTypePrivate((bool)$request->get('privacy_type'))
                ->makeRetiredPrivate((bool)$request->get('privacy_retired'))
                ->makeSexPrivate((bool)$request->get('privacy_sex'))
                ->makePrefixPrivate((bool)$request->get('privacy_prefix'))
                ->makeHexerOrBreederPrivate((bool)$request->get('privacy_hexer'))
                ->makeBirthdayPrivate((bool)$request->get('privacy_birthday'));
            $entityManager->persist($privacy);
            $entityManager->flush($privacy);
        }

        return $this->render('settings/display.html.twig', [
            'saved' => true,
            'useCompactView' => $options->useCompactView(),
            'privacy' => $privacy,
        ]);
    }

    #[Route('/settings/profile', name: 'profile_settings', methods: ['GET'])]
    public function profileSettings(): Response {
        return $this->render('settings/profile.html.twig', [
            'profile' => $this->getUser()->getProfile(),
        ]);
    }

    #[Route('/settings/profile', name: 'profile_settings_save', methods: ['POST'])]
    public function profileSettingsAction(Request $request): Response {
        $user = $this->getUser();
        $profile = $user->getProfile();
        $profile->setDescription($request->get('description', null));
        $profile->setUsername($request->get('username', null));
        $profile->setDisplayName($request->get('displayName', null));
        $profile->setWebsite($request->get('website', null));
        $profile->setPrivate($request->get('private') === 'true');
        $profile->setHideName($request->get('hideName') === 'true');
        $deletePic = $request->get('deletePic') === 'true';
        $pic = $request->files->get('file');

        $userPicDir = $this->picPath . $user->getHash() . DIRECTORY_SEPARATOR;

        $delete = function(string $dir, string $pic) {
            if(file_exists($dir . $pic)) {
                unlink($dir . $pic);
            }
        };

        if($pic !== null) {
            if($profile->getPic()) {
                $delete($userPicDir, $profile->getPic());
            }

            $name = 'profile_' . md5_file($pic->getRealPath()) . '.' . $pic->getClientOriginalExtension();
            if(!is_dir($userPicDir)) {
                mkdir($userPicDir);
            }
            $pic->move($userPicDir, $name);
            $profile->setPic($name);

            try {
                $size = getimagesize($userPicDir . $name);
                $profile->setPicWidth($size[0]);
                $profile->setPicHeight($size[1]);
            } catch(Exception $e) {
                $this->logger->error('Error getting image size for image: ' . $userPicDir . $name);
                $profile->setPicWidth(null);
                $profile->setPicHeight(null);
            }
        } else {
            // Only do this if we're not adding/replacing a pic since that would delete the old one anyway.
            if($deletePic) {
                $delete($userPicDir, $profile->getPic());
                $profile->setPic(null);
                $profile->setPicWidth(null);
                $profile->setPicHeight(null);
            }
        }
        $this->entityManager->persist($profile);
        $this->entityManager->flush();
        return new JsonResponse(array_merge(['hash' => $this->getUser()->getHash(), 'deletePic' => false], $this->getUser()->getProfile()->jsonSerialize()), Response::HTTP_OK);
    }

    #[Route('/settings/validateUsername', name: 'profile_validate_username', methods: ['POST'])]
    public function profileValidateUsername(Request $request): JsonResponse {
        $content = json_decode($request->getContent(), true);
        $username = null;
        if($content && array_key_exists('username', $content)) {
            $username = $content['username'];
        } else {
            return new JsonResponse([
                'valid' => false,
                'message' => 'Could not get username from request',
            ]);
        }

        $resultMapping = new ResultSetMapping();
        $resultMapping->addScalarResult('number', 'number', 'integer');
        $query = $this->entityManager->createNativeQuery('select count(*) as number from user_profile where username = :username', $resultMapping);
        $query->setParameter(1, $username);
        $result = $query->execute([
            'username' => $username,
        ]);

        return new JsonResponse([
            'valid' => $result[0]['number'] === 0,
            'message' => null
        ]);
    }

    #[Route('/settings/profile/data', name: 'profile_settings_data', methods: ['GET'])]
    public function profileSettingsData(): JsonResponse {
        return new JsonResponse(array_merge(['hash' => $this->getUser()->getHash()], $this->getUser()->getProfile()->jsonSerialize()));
    }

    #[Route('/settings/profile/preview', name: 'profile_preview', methods: ['POST'])]
    public function profilePreview(Request $request): Response {
        $converter = new CommonMarkConverter([
            'html_input' => 'allow',
        ]);
        return new Response($converter->convert($request->get('md') ?? ''), Response::HTTP_OK);
    }

    #[Route('/tags', name: 'tags_page', methods: ['GET'])]
    public function tagsPage(Request $request): Response {
        return $this->render('settings/tags/list.html.twig');
    }

    #[Route('/tags/list', name: 'list_tags', methods: ['GET'])]
    public function listTags(Request $request): Response {
        try {
            return new JsonResponse($this->tagRepo->findBy(['user' => $this->getUser()], ['name' => 'asc']), Response::HTTP_OK);
        } catch(Exception $e) {}

        return new JsonResponse([
            'message' => 'There was an error getting tags.',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    #[Route('/tags/add', name: 'add_tags_action', methods: ['POST'])]
    public function addTagsAction(Request $request): JsonResponse {
        try {
            $tagObjects = new ArrayCollection();
            $tagsToAdd = json_decode($request->getContent(), true);
            $user = $this->getUser();
            foreach($tagsToAdd as $tag) {
                $tagObjects->add(new Tag($user, $tag['name'], hash($_ENV['SMALL_HASH'], $user->getId() . $tag['name']), $tag['private'] ?? true));
            }
            if($tagObjects->count() > 0) {
                $this->tagRepo->saveCollectionIgnoreDuplicates($tagObjects, true);
            }
            return new JsonResponse($this->tagRepo->findBy(['user' => $this->getUser()], ['name' => 'asc']), Response::HTTP_OK);
        } catch(Exception $e) {
            $this->logger->error('Error adding tags.', [
                'exception' => $e
            ]);
        }

        return new JsonResponse([
            'message' => 'There was an error adding tags.',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    #[Route('/tags/{hash}', name: 'edit_tag_action', methods: ['POST'])]
    public function editTagAction(Request $request, string $hash): JsonResponse {
        try {
            $tag = $this->tagService->getTagIfOwnedByUser($this->getUser(), $hash);
            $data = json_decode($request->getContent(), true);
            $tag->setName($data['name']);
            $tag->setPrivate($data['private'] ?? true);
            $this->tagRepo->save($tag, true);
            return new JsonResponse('', Response::HTTP_OK);
        } catch(Exception $e) {
            $this->logger->error('Could not save tag', [
                'exception' => $e,
            ]);
            return new JsonResponse([
                'message' => 'There was an saving the tag.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/tags/{hash}', name: 'delete_tag', methods: ['DELETE'])]
    public function deleteTag(string $hash): JsonResponse {
        try {
            $tag = $this->tagService->getTagIfOwnedByUser($this->getUser(), $hash);
            $this->tagRepo->remove($tag, true);
        } catch(Exception $e) {
            $this->logger->error('Could not remove tag', [
                'exception' => $e,
            ]);
            return new JsonResponse([
                'message' => 'There was an error adding tags.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse('', Response::HTTP_OK);
    }
}