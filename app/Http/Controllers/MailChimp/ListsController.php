<?php
declare(strict_types=1);

namespace App\Http\Controllers\MailChimp;

use App\Database\Entities\MailChimp\MailChimpList;
use App\Http\Controllers\Controller;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Mailchimp\Mailchimp;

class ListsController extends Controller
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
     * Create MailChimp list.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        // Instantiate entity
        $list = new MailChimpList($request->all());
        // Validate entity
        $validator = $this->getValidationFactory()->make($list->toMailChimpArray(), $list->getValidationRules());

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
            $this->saveEntity($list);
            // Save list into MailChimp
            $response = $this->mailChimp->post('lists', $list->toMailChimpArray());
            // Set MailChimp id on the list and save list into db
            $this->saveEntity($list->setMailChimpId($response->get('id')));
            $this->entityManager->commit();
        } catch (Exception $exception) {
            $this->entityManager->rollback();
            // Return error response if something goes wrong
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse($list->toArray());
    }

    /**
     * Remove MailChimp list.
     *
     * @param string $listId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove(string $listId): JsonResponse
    {
        /** @var \App\Database\Entities\MailChimp\MailChimpList|null $list */
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);

        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                404
            );
        }

        $this->entityManager->beginTransaction();

        try {
            // Remove list from database
            $this->removeEntity($list);
            // Remove list from MailChimp
            $this->mailChimp->delete(\sprintf('lists/%s', $list->getMailChimpId()));
            $this->entityManager->commit();
        } catch (Exception $exception) {
            $this->entityManager->rollback();

            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse([]);
    }

    /**
     * Retrieve and return MailChimp list.
     *
     * @param string $listId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $listId): JsonResponse
    {
        /** @var \App\Database\Entities\MailChimp\MailChimpList|null $list */
        $list = $this->entityManager->getRepository(MailChimpList::class)->findOneByMailChimpId($listId);

        if ($list === null) {
            try {
                /** @var Collection $response */
                $response = $this->mailChimp->get(\sprintf('lists/%s', $listId));
            } catch (Exception $e) {
                return $this->errorResponse(
                    ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                    404
                );
            }

            // Instantiate entity
            $list = MailChimpList::fromMailChimpResponse($response->all());
            // Validate entity
            $validator = $this->getValidationFactory()->make($list->toMailChimpArray(), $list->getValidationRules());

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
                $this->saveEntity($list);
                $this->entityManager->commit();
            } catch (Exception $exception) {
                $this->entityManager->rollback();
                // Return error response if something goes wrong
                return $this->errorResponse(['message' => $exception->getMessage()]);
            }
        }

        return $this->successfulResponse($list->toArray());
    }

    /**
     * Update MailChimp list.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $listId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $listId): JsonResponse
    {
        /** @var \App\Database\Entities\MailChimp\MailChimpList|null $list */
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);

        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                404
            );
        }

        // Update list properties
        $list->fill($request->all());

        // Validate entity
        $validator = $this->getValidationFactory()->make($list->toMailChimpArray(), $list->getValidationRules());

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
            $this->saveEntity($list);
            // Update list into MailChimp
            $this->mailChimp->patch(\sprintf('lists/%s', $list->getMailChimpId()), $list->toMailChimpArray());
            $this->entityManager->commit();
        } catch (Exception $exception) {
            $this->entityManager->rollback();

            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse($list->toArray());
    }
}
