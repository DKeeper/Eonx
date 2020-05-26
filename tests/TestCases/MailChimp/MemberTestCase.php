<?php
declare(strict_types=1);

namespace Tests\App\TestCases\MailChimp;

use App\Database\Entities\MailChimp\MailChimpMember;
use Illuminate\Http\JsonResponse;
use Mailchimp\Mailchimp;
use Mockery;
use Mockery\MockInterface;
use Tests\App\TestCases\WithDatabaseTestCase;

abstract class MemberTestCase extends WithDatabaseTestCase
{
    protected const MAILCHIMP_EXCEPTION_MESSAGE = 'MailChimp exception';
    protected const ENTITY_NOT_FOUND_EXCEPTION = 'MailChimpMember[%s] not found in list %s';
    protected const API_MAIL_CHIMP_INVALID_DATA_EXCEPTION_MESSAGE = 'Invalid data given during update data in DB';
    protected const API_INVALID_DATA_EXCEPTION_MESSAGE = 'Invalid data given';

    /**
     * @var array
     */
    protected $createdMemberEmails = [];

    /**
     * @var array
     */
    protected static $memberData = [
        "email_address" => "test4@noname.com",
        "status" => "cleaned",
        "merge_fields" => [
            "FNAME" => "John",
            "LNAME" => "Doe",
            "PHONE" => "+12345678901",
            "ADDRESS" => [
                "zip" => "1234-56",
                "city" => "Unknown",
                "addr1" => "New street",
                "addr2" => "",
                "state" => "fake reg.",
                "country" => "ZZ",
            ],
            "BIRTHDAY" => "12/01",
        ],
        "language" => "zz",
        "vip" => true,
    ];

    /**
     * @return string
     */
    abstract protected function getListId(): string;

    /**
     * Call MailChimp to delete members created during test.
     *
     * @return void
     */
    public function tearDown(): void
    {
        /** @var Mailchimp $mailChimp */
        $mailChimp = $this->app->make(Mailchimp::class);

        foreach ($this->createdMemberEmails as $email) {
            // Delete members on MailChimp after test
            $mailChimp->delete(\sprintf('lists/%s/members/%s', $this->getListId(), $email));
        }

        parent::tearDown();
    }

    /**
     * Create MailChimp member into database.
     *
     * @param array $data
     *
     * @return MailChimpMember
     */
    protected function createMember(array $data): MailChimpMember
    {
        $member = new MailChimpMember(array_merge($data, ['list_id' => $this->getListId()]));

        $this->entityManager->persist($member);
        $this->entityManager->flush();

        return $member;
    }

    /**
     * Remove MailChimp member from database.
     *
     * @param string $memberId
     */
    protected function removeMember(string $memberId): void
    {
        $member = $this->entityManager->getRepository(MailChimpMember::class)->find($memberId);

        if ($member !== null) {
            $this->entityManager->remove($member);
            $this->entityManager->flush();
        }
    }

    /**
     * Returns mock of MailChimp to trow exception when requesting their API.
     *
     * @param string $method
     * @param string $message
     *
     * @return MockInterface
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
        fwrite(STDERR, $response->content() . PHP_EOL);

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
        unset($content['member_id']);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($expected, $content);
    }
}
