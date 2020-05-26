<?php
declare(strict_types=1);

namespace Tests\App\TestCases\MailChimp;

use App\Database\Entities\MailChimp\MailChimpMember;
use Mailchimp\Mailchimp;
use Tests\App\TestCases\WithDatabaseTestCase;

abstract class MemberTestCase extends WithDatabaseTestCase
{
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
        $member = new MailChimpMember($data);

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
}
