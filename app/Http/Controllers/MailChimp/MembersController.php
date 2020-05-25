<?php
declare(strict_types=1);

namespace App\Http\Controllers\MailChimp;

use App\Database\Entities\MailChimp\MailChimpMember;
use App\Http\Controllers\Controller;
use App\Utils\ResponseConverter;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Mailchimp\Mailchimp;

class MembersController extends Controller
{
    /**
     * @var \Mailchimp\Mailchimp
     */
    private $mailChimp;

    /**
     * ListsController constructor.
     *
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \Mailchimp\Mailchimp $mailchimp
     */
    public function __construct(EntityManagerInterface $entityManager, Mailchimp $mailchimp)
    {
        parent::__construct($entityManager);

        $this->mailChimp = $mailchimp;
    }

    /**
     * @param string $listId
     *
     * @return JsonResponse
     */
    public function show(string $listId): JsonResponse
    {
        /** @var MailChimpMember[] $members */
        $members = $this->entityManager->getRepository(MailChimpMember::class)->findByListId($listId);

        if (empty($members)) {
            try {
                /** @var Collection $response */
                $response = $this->mailChimp->get(\sprintf('/lists/%s/members', $listId));
            } catch (Exception $e) {
                return $this->errorResponse(
                    ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                    404
                );
            }

            foreach (Arr::get($response->all(), 'members', []) as $item) {
                $member = new MailChimpMember(ResponseConverter::prepareResponse($item));
                // Validate entity
                $validator = $this->getValidationFactory()->make($member->toMailChimpArray(), $member->getValidationRules());

                if ($validator->fails()) {
                    // Return error response if validation failed
                    return $this->errorResponse([
                        'message' => 'Invalid data given during update data in DB',
                        'errors' => $validator->errors()->toArray()
                    ]);
                }

                $this->entityManager->beginTransaction();

                try {
                    // Save member into db
                    $this->saveEntity($member);
                    $this->entityManager->commit();
                } catch (Exception $exception) {
                    $this->entityManager->rollback();
                    // Return error response if something goes wrong
                    return $this->errorResponse(['message' => $exception->getMessage()]);
                }

                $members[] = $member;
            }
        }

        $members = \array_map(static function (MailChimpMember $item) {
            return $item->toArray();
        }, $members);

        return $this->successfulResponse($members);
    }

    /**
     * @param string $listId
     * @param string $email
     *
     * @return JsonResponse
     */
    public function showMember(string $listId, string $email): JsonResponse
    {
        /** @var MailChimpMember|null $members */
        $member = $this->entityManager
            ->getRepository(MailChimpMember::class)
            ->findOneBy([
                'listId' => $listId,
                'emailAddress' => $email,
            ])
        ;

        if ($member === null) {
            try {
                /** @var Collection $response */
                $response = $this->mailChimp->get(\sprintf('/lists/%s/members/%s', $listId, $email));
            } catch (Exception $e) {
                return $this->errorResponse(
                    ['message' => \sprintf('MailChimpMember[%s] not found in list %s', $email, $listId)],
                    404
                );
            }

            $member = new MailChimpMember(ResponseConverter::prepareResponse($response->all()));
            // Validate entity
            $validator = $this->getValidationFactory()->make($member->toMailChimpArray(), $member->getValidationRules());

            if ($validator->fails()) {
                // Return error response if validation failed
                return $this->errorResponse([
                    'message' => 'Invalid data given during update data in DB',
                    'errors' => $validator->errors()->toArray()
                ]);
            }

            $this->entityManager->beginTransaction();

            try {
                // Save member into db
                $this->saveEntity($member);
                $this->entityManager->commit();
            } catch (Exception $exception) {
                $this->entityManager->rollback();
                // Return error response if something goes wrong
                return $this->errorResponse(['message' => $exception->getMessage()]);
            }
        }

        return $this->successfulResponse($member->toArray());
    }

    /**
     * @param Request $request
     * @param string $listId
     *
     * @return JsonResponse
     */
    public function create(Request $request, string $listId): JsonResponse
    {
        $member = new MailChimpMember($request->all());
        $member->setListId($listId);

        // Validate entity
        $validator = $this->getValidationFactory()->make($member->toMailChimpArray(), $member->getValidationRules());

        if ($validator->fails()) {
            // Return error response if validation failed
            return $this->errorResponse([
                'message' => 'Invalid data given',
                'errors' => $validator->errors()->toArray()
            ]);
        }

        $this->entityManager->beginTransaction();

        try {
            // Save list into db
            $this->saveEntity($member);
            // Save list into MailChimp
            $this->mailChimp->post(sprintf('lists/%s/members', $listId), $member->toMailChimpArray());
            $this->entityManager->commit();
        } catch (Exception $exception) {
            $this->entityManager->rollback();
            // Return error response if something goes wrong
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse($member->toArray());
    }

    /**
     * @param string $listId
     * @param string $email
     *
     * @return JsonResponse
     */
    public function remove(string $listId, string $email): JsonResponse
    {
        /** @var MailChimpMember|null $member */
        $member = $this->entityManager
            ->getRepository(MailChimpMember::class)
            ->findOneBy([
                'listId' => $listId,
                'emailAddress' => $email,
            ])
        ;

        if ($member === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpMember[%s] not found in list %s', $email, $listId)],
                404
            );
        }

        $this->entityManager->beginTransaction();

        try {
            // Remove list from database
            $this->removeEntity($member);
            // Remove list from MailChimp
            $this->mailChimp->delete(\sprintf('lists/%s/members/%s', $listId, $email));
            $this->entityManager->commit();
        } catch (Exception $exception) {
            $this->entityManager->rollback();

            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse([]);
    }

    /**
     * @param Request $request
     * @param string $listId
     * @param string $email
     *
     * @return JsonResponse
     */
    public function update(Request $request, string $listId, string $email): JsonResponse
    {
        /** @var MailChimpMember|null $member */
        $member = $this->entityManager
            ->getRepository(MailChimpMember::class)
            ->findOneBy([
                'listId' => $listId,
                'emailAddress' => $email,
            ])
        ;

        if ($member === null) {
            try {
                /** @var Collection $response */
                $response = $this->mailChimp->get(\sprintf('/lists/%s/members/%s', $listId, $email));
            } catch (Exception $e) {
                return $this->errorResponse(
                    ['message' => \sprintf('MailChimpMember[%s] not found in list %s', $email, $listId)],
                    404
                );
            }

            $member = new MailChimpMember(ResponseConverter::prepareResponse($response->all()));
            // Validate entity
            $validator = $this->getValidationFactory()->make($member->toMailChimpArray(), $member->getValidationRules());

            if ($validator->fails()) {
                // Return error response if validation failed
                return $this->errorResponse([
                    'message' => 'Invalid data given during update data in DB',
                    'errors' => $validator->errors()->toArray()
                ]);
            }

            $this->entityManager->beginTransaction();

            try {
                // Save member into db
                $this->saveEntity($member);
                $this->entityManager->commit();
            } catch (Exception $exception) {
                $this->entityManager->rollback();
                // Return error response if something goes wrong
                return $this->errorResponse(['message' => $exception->getMessage()]);
            }
        }

        $member->fill($request->all());

        $validator = $this->getValidationFactory()->make($member->toMailChimpArray(), $member->getValidationRules());

        if ($validator->fails()) {
            // Return error response if validation failed
            return $this->errorResponse([
                'message' => 'Invalid data given',
                'errors' => $validator->errors()->toArray()
            ]);
        }

        $this->entityManager->beginTransaction();

        try {
            // Update list into database
            $this->saveEntity($member);
            // Update list into MailChimp
            $this->mailChimp->patch(\sprintf('lists/%s/members/%s', $listId, $email), $member->toMailChimpArray());
            $this->entityManager->commit();
        } catch (Exception $exception) {
            $this->entityManager->rollback();

            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse($member->toArray());
    }
}
