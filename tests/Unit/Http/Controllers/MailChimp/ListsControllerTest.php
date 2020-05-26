<?php
declare(strict_types=1);

namespace Tests\App\Unit\Http\Controllers\MailChimp;

use App\Http\Controllers\MailChimp\ListsController;
use Illuminate\Support\Collection;
use Mailchimp\Mailchimp;
use Mockery;
use Tests\App\TestCases\MailChimp\ListTestCase;

class ListsControllerTest extends ListTestCase
{
    /**
     * Test controller returns error response when exception is thrown during create MailChimp request.
     *
     * @return void
     */
    public function testCreateListMailChimpException(): void
    {
        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new ListsController($this->entityManager, $this->mockMailChimpForException('post', self::MAILCHIMP_EXCEPTION_MESSAGE));

        $this->assertExceptionResponse(
            $controller->create($this->getRequest(static::$listData)),
            self::MAILCHIMP_EXCEPTION_MESSAGE,
            400
        );
    }

    /**
     * Test controller returns error response when API request has invalid data.
     *
     * @return void
     */
    public function testCreateListInvalidApiRequest(): void
    {
        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new ListsController($this->entityManager, Mockery::mock(Mailchimp::class));

        $this->assertExceptionResponse(
            $controller->create($this->getRequest(static::$invalidListData)),
            self::API_INVALID_DATA_EXCEPTION_MESSAGE,
            400
        );
    }

    /**
     * Test controller returns error response when exception is thrown during remove MailChimp request.
     *
     * @return void
     */
    public function testRemoveListMailChimpException(): void
    {
        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new ListsController($this->entityManager, $this->mockMailChimpForException('delete', self::MAILCHIMP_EXCEPTION_MESSAGE));
        $list = $this->createList(static::$listData);

        // If there is no list id, skip
        if (null === $list->getId()) {
            self::markTestSkipped('Unable to remove, no id provided for list');

            return;
        }

        $this->assertExceptionResponse(
            $controller->remove($list->getId()),
            self::MAILCHIMP_EXCEPTION_MESSAGE,
            400
        );
    }

    /**
     * Test controller returns error response when remove non exists list.
     *
     * @return void
     */
    public function testRemoveNonExistsList(): void
    {
        $listId = 'Fake ID';
        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new ListsController($this->entityManager, Mockery::mock(Mailchimp::class));

        $this->assertExceptionResponse(
            $controller->remove($listId),
            \sprintf(self::ENTITY_NOT_FOUND_EXCEPTION, $listId),
            404
        );
    }

    /**
     * Test controller returns error response when exception is thrown during update MailChimp request.
     *
     * @return void
     */
    public function testUpdateListMailChimpException(): void
    {
        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new ListsController($this->entityManager, $this->mockMailChimpForException('patch', self::MAILCHIMP_EXCEPTION_MESSAGE));
        $list = $this->createList(static::$listData);

        // If there is no list id, skip
        if (null === $list->getId()) {
            self::markTestSkipped('Unable to update, no id provided for list');

            return;
        }

        $this->assertExceptionResponse(
            $controller->update($this->getRequest(), $list->getId()),
            self::MAILCHIMP_EXCEPTION_MESSAGE,
            400
        );
    }

    /**
     * Test controller returns error response when update non exists list.
     *
     * @return void
     */
    public function testUpdateNonExistsList(): void
    {
        $listId = 'Fake ID';
        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new ListsController($this->entityManager, Mockery::mock(Mailchimp::class));

        $this->assertExceptionResponse(
            $controller->update($this->getRequest(), $listId),
            \sprintf(self::ENTITY_NOT_FOUND_EXCEPTION, $listId),
            404
        );
    }

    /**
     * Test controller returns success response if list exists in DB
     *
     * @return void
     */
    public function testShowList(): void
    {
        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new ListsController($this->entityManager, Mockery::mock(Mailchimp::class));
        $list = $this->createList(static::$listData);
        $mailChimpId = $list->getMailChimpId();

        // If there is no list id, skip
        if (null === $list->getId()) {
            self::markTestSkipped('Unable to update, no id provided for list');

            return;
        }

        $this->assertSuccessfulResponse(
            $controller->show($mailChimpId),
            array_merge(self::$listData, ['mail_chimp_id' => $mailChimpId])
        );
    }

    /**
     * Test controller returns success response if list exists in MailChimp and stored into DB
     *
     * @return void
     */
    public function testShowListFromMailChimp(): void
    {
        $collection = Collection::make(array_merge(self::$listData, ['id' => 'fake_id']));
        $mailChimp = Mockery::mock(Mailchimp::class);
        $mailChimp
            ->shouldReceive('get')
            ->once()
            ->withArgs(static function (string $method, ?array $options = null) {
                return !empty($method) && (null === $options || \is_array($options));
            })
            ->andReturn($collection)
        ;
        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new ListsController($this->entityManager, $mailChimp);

        $this->assertSuccessfulResponse(
            $controller->show('fake'),
            array_merge(self::$listData, ['mail_chimp_id' => 'fake_id'])
        );
    }

    /**
     * Test controller returns error response when exception is thrown during show MailChimp request.
     *
     * @return void
     */
    public function testShowListMailChimpException(): void
    {
        $listId = 'Fake ID';
        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new ListsController($this->entityManager, $this->mockMailChimpForException('get', ''));

        $this->assertExceptionResponse(
            $controller->show($listId),
            \sprintf(self::ENTITY_NOT_FOUND_EXCEPTION, $listId),
            404
        );
    }
}
