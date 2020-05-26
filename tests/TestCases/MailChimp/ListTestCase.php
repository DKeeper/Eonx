<?php
declare(strict_types=1);

namespace Tests\App\TestCases\MailChimp;

use App\Database\Entities\MailChimp\MailChimpList;
use Faker\Provider\Base;
use Illuminate\Http\JsonResponse;
use Mailchimp\Mailchimp;
use Mockery;
use Mockery\MockInterface;
use Tests\App\TestCases\WithDatabaseTestCase;

abstract class ListTestCase extends WithDatabaseTestCase
{
    protected const MAILCHIMP_EXCEPTION_MESSAGE = 'MailChimp exception';
    protected const API_INVALID_DATA_EXCEPTION_MESSAGE = 'Invalid data given';
    protected const ENTITY_NOT_FOUND_EXCEPTION = 'MailChimpList[%s] not found';

    /**
     * @var array
     */
    protected $createdListIds = [];

    /**
     * @var array
     */
    protected static $listData = [
        'name' => 'New list',
        'permission_reminder' => 'You signed up for updates on Greeks economy.',
        'email_type_option' => false,
        'contact' => [
            'company' => 'Doe Ltd.',
            'address1' => 'DoeStreet 1',
            'address2' => '',
            'city' => 'Doesy',
            'state' => 'Doedoe',
            'zip' => '1672-12',
            'country' => 'US',
            'phone' => '55533344412'
        ],
        'campaign_defaults' => [
            'from_name' => 'John Doe',
            'from_email' => 'john@doe.com',
            'subject' => 'My new campaign!',
            'language' => 'US'
        ],
        'visibility' => 'prv',
        'use_archive_bar' => false,
        'notify_on_subscribe' => 'notify@loyaltycorp.com.au',
        'notify_on_unsubscribe' => 'notify@loyaltycorp.com.au'
    ];

    /**
     * @var array
     */
    protected static $invalidListData = [
        'permission_reminder' => 'You signed up for updates on Greeks economy.',
        'email_type_option' => false,
        'contact' => [
            'company' => 'Doe Ltd.',
            'address1' => 'DoeStreet 1',
            'address2' => '',
            'city' => 'Doesy',
            'state' => 'Doedoe',
            'zip' => '1672-12',
            'country' => 'US',
            'phone' => '55533344412'
        ],
        'campaign_defaults' => [
            'from_email' => 'john@doe.com',
            'subject' => 'My new campaign!',
            'language' => 'US'
        ],
        'visibility' => 'prv',
        'use_archive_bar' => false,
        'notify_on_subscribe' => 'notify@loyaltycorp.com.au',
        'notify_on_unsubscribe' => 'notify@loyaltycorp.com.au'
    ];

    /**
     * @var array
     */
    protected static $notRequired = [
        'notify_on_subscribe',
        'notify_on_unsubscribe',
        'use_archive_bar',
        'visibility'
    ];

    /**
     * Call MailChimp to delete lists created during test.
     *
     * @return void
     */
    public function tearDown(): void
    {
        /** @var Mailchimp $mailChimp */
        $mailChimp = $this->app->make(Mailchimp::class);

        foreach ($this->createdListIds as $listId) {
            // Delete list on MailChimp after test
            $mailChimp->delete(\sprintf('lists/%s', $listId));
        }

        parent::tearDown();
    }

    /**
     * Asserts error response when list not found.
     *
     * @param string $listId
     *
     * @return void
     */
    protected function assertListNotFoundResponse(string $listId): void
    {
        $content = \json_decode($this->response->content(), true);

        $this->assertResponseStatus(404);
        self::assertArrayHasKey('message', $content);
        self::assertEquals(\sprintf('MailChimpList[%s] not found', $listId), $content['message']);
    }

    /**
     * Asserts error response when MailChimp exception is thrown.
     *
     * @param JsonResponse $response
     * @param string $message
     * @param int $statusCode
     *
     * @return void
     */
    protected function assertExceptionResponse(JsonResponse $response, string $message, int $statusCode): void
    {
        $content = \json_decode($response->content(), true);

        self::assertEquals($statusCode, $response->getStatusCode());
        self::assertArrayHasKey('message', $content);
        self::assertEquals($message, $content['message']);
    }

    /**
     * Asserts error response when MailChimp exception is thrown.
     *
     * @param JsonResponse $response
     * @param array $expected
     *
     * @return void
     */
    protected function assertSuccessfulResponse(JsonResponse $response, array $expected): void
    {
        $content = \json_decode($response->content(), true);
        // Remove unique ID
        unset($content['list_id']);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($expected, $content);
    }

    /**
     * Create MailChimp list into database.
     *
     * @param array $data
     *
     * @return \App\Database\Entities\MailChimp\MailChimpList
     */
    protected function createList(array $data): MailChimpList
    {
        $list = new MailChimpList($data);
        $list->setMailChimpId(Base::randomAscii());

        $this->entityManager->persist($list);
        $this->entityManager->flush();

        return $list;
    }

    /**
     * Remove MailChimp list from database.
     *
     * @param string $listId
     */
    protected function removeList(string $listId): void
    {
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);

        if ($list !== null) {
            $this->entityManager->remove($list);
            $this->entityManager->flush();
        }
    }

    /**
     * Returns mock of MailChimp to trow exception when requesting their API.
     *
     * @param string $method
     * @param string $message
     *
     * @return \Mockery\MockInterface
     *
     * @SuppressWarnings(PHPMD.StaticAccess) Mockery requires static access to mock()
     */
    protected function mockMailChimpForException(string $method, string $message): MockInterface
    {
        $mailChimp = Mockery::mock(Mailchimp::class);

        $mailChimp
            ->shouldReceive($method)
            ->once()
            ->withArgs(static function (string $method, ?array $options = null) {
                return !empty($method) && (null === $options || \is_array($options));
            })
            ->andThrow(new \Exception($message));

        return $mailChimp;
    }
}
