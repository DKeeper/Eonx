<?php
declare(strict_types=1);

namespace Tests\App\Unit\Http\Controllers\MailChimp;

use App\Http\Controllers\MailChimp\MembersController;
use Illuminate\Support\Collection;
use Mailchimp\Mailchimp;
use Mockery;
use Tests\App\TestCases\MailChimp\MemberTestCase;

class MembersControllerTest extends MemberTestCase
{
    /**
     * @inheritDoc
     */
    protected function getListId(): string
    {
        return 'fakeListId';
    }

    /**
     * Test controller returns error response when exception is thrown during create MailChimp request.
     *
     * @return void
     */
    public function testCreateListMailChimpException(): void
    {
        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new MembersController($this->entityManager, $this->mockMailChimpForException('post', self::MAILCHIMP_EXCEPTION_MESSAGE));

        $this->assertExceptionResponse(
            $controller->create($this->getRequest(static::$memberData), $this->getListId()),
            self::MAILCHIMP_EXCEPTION_MESSAGE,
            400
        );
    }

    /**
     * Test controller returns successful response
     *
     * @return void
     */
    public function testShowMember(): void
    {
        $member = $this->createMember(static::$memberData);
        $expected = $member->toArray();
        unset($expected['member_id']);

        $controller = new MembersController($this->entityManager, Mockery::mock(Mailchimp::class));

        $this->assertSuccessfulResponse(
            $controller->showMember($this->getListId(), $member->getEmailAddress()),
            $expected
        );
    }

    /**
     * Test controller returns error response when exception is thrown during show MailChimp request.
     *
     * @return void
     */
    public function testShowMemberMailChimpException(): void
    {
        $email = 'fake@email.com';
        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new MembersController($this->entityManager, $this->mockMailChimpForException('get', static::MAILCHIMP_EXCEPTION_MESSAGE));

        $this->assertExceptionResponse(
            $controller->showMember($this->getListId(), $email),
            \sprintf(static::ENTITY_NOT_FOUND_EXCEPTION, $email, $this->getListId()),
            404
        );
    }

    /**
     * Test controller returns successful response
     *
     * @return void
     */
    public function testRemove(): void
    {
        $member = $this->createMember(static::$memberData);

        $mailChimp = Mockery::mock(Mailchimp::class);

        $mailChimp
            ->shouldReceive('delete')
            ->once()
            ->withArgs(static function (string $method, ?array $options = null) {
                return !empty($method) && (null === $options || \is_array($options));
            })
        ;

        $controller = new MembersController($this->entityManager, $mailChimp);

        $this->assertSuccessfulResponse(
            $controller->remove($this->getListId(), $member->getEmailAddress()),
            []
        );
    }

    /**
     * Test controller returns error response when exception is thrown during remove MailChimp request.
     *
     * @return void
     */
    public function testRemoveMailChimpException(): void
    {
        $member = $this->createMember(static::$memberData);

        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new MembersController($this->entityManager, $this->mockMailChimpForException('delete', static::MAILCHIMP_EXCEPTION_MESSAGE));

        $this->assertExceptionResponse(
            $controller->remove($this->getListId(), $member->getEmailAddress()),
            static::MAILCHIMP_EXCEPTION_MESSAGE,
            400
        );
    }

    /**
     * Test controller returns error response when exception is thrown during update MailChimp request.
     *
     * @return void
     */
    public function testUpdateMailChimpException(): void
    {
        $email = 'fake@email.com';
        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new MembersController($this->entityManager, $this->mockMailChimpForException('get', static::MAILCHIMP_EXCEPTION_MESSAGE));

        $this->assertExceptionResponse(
            $controller->update($this->getRequest(), $this->getListId(), $email),
            \sprintf(static::ENTITY_NOT_FOUND_EXCEPTION, $email, $this->getListId()),
            404
        );
    }

    /**
     * Test controller returns error response when exception is thrown during validate response from MailChimp.
     *
     * @return void
     */
    public function testUpdateEmptyRequest(): void
    {
        $collection = Collection::make(static::$memberData);
        $mailChimp = Mockery::mock(Mailchimp::class);

        $mailChimp
            ->shouldReceive('get')
            ->once()
            ->withArgs(static function (string $method, ?array $options = null) {
                return !empty($method) && (null === $options || \is_array($options));
            })
            ->andReturn($collection)
        ;
        $controller = new MembersController($this->entityManager, $mailChimp);

        $this->assertExceptionResponse(
            $controller->update($this->getRequest(), $this->getListId(), 'fake@email.com'),
            static::API_MAIL_CHIMP_INVALID_DATA_EXCEPTION_MESSAGE,
            400
        );
    }

    /**
     * Test controller returns error response when exception is thrown during validate API request.
     *
     * @return void
     */
    public function testUpdateInvalidRequest(): void
    {
        $collection = Collection::make(array_merge(static::$memberData, ['list_id' => $this->getListId()]));
        $mailChimp = Mockery::mock(Mailchimp::class);
        $requestData = static::$memberData;
        $requestData['status'] = '';

        $mailChimp
            ->shouldReceive('get')
            ->once()
            ->withArgs(static function (string $method, ?array $options = null) {
                return !empty($method) && (null === $options || \is_array($options));
            })
            ->andReturn($collection)
        ;
        $controller = new MembersController($this->entityManager, $mailChimp);

        $this->assertExceptionResponse(
            $controller->update($this->getRequest($requestData), $this->getListId(), 'fake@email.com'),
            static::API_INVALID_DATA_EXCEPTION_MESSAGE,
            400
        );
    }

    /**
     * Test controller returns error response when exception is thrown during update MailChimp request.
     *
     * @return void
     */
    public function testUpdateMailChimpPatchException(): void
    {
        $collection = Collection::make(array_merge(static::$memberData, ['list_id' => $this->getListId()]));
        $mailChimp = Mockery::mock(Mailchimp::class);
        $requestData = static::$memberData;
        $requestData['language'] = 'ru';

        $mailChimp
            ->shouldReceive('get')
            ->once()
            ->withArgs(static function (string $method, ?array $options = null) {
                return !empty($method) && (null === $options || \is_array($options));
            })
            ->andReturn($collection)
        ;
        $mailChimp
            ->shouldReceive('patch')
            ->once()
            ->withArgs(static function (string $method, ?array $options = null) {
                return !empty($method) && (null === $options || \is_array($options));
            })
            ->andThrow(new \Exception(static::MAILCHIMP_EXCEPTION_MESSAGE))
        ;
        $controller = new MembersController($this->entityManager, $mailChimp);

        $this->assertExceptionResponse(
            $controller->update($this->getRequest($requestData), $this->getListId(), 'fake@email.com'),
            static::MAILCHIMP_EXCEPTION_MESSAGE,
            400
        );
    }

    /**
     * Test controller returns successful response
     *
     * @return void
     */
    public function testUpdate(): void
    {
        $collection = Collection::make(array_merge(static::$memberData, ['list_id' => $this->getListId()]));
        $mailChimp = Mockery::mock(Mailchimp::class);
        $requestData = static::$memberData;
        $requestData['language'] = 'ru';

        $mailChimp
            ->shouldReceive('get')
            ->once()
            ->withArgs(static function (string $method, ?array $options = null) {
                return !empty($method) && (null === $options || \is_array($options));
            })
            ->andReturn($collection)
        ;
        $mailChimp
            ->shouldReceive('patch')
            ->once()
            ->withArgs(static function (string $method, ?array $options = null) {
                return !empty($method) && (null === $options || \is_array($options));
            })
        ;
        $controller = new MembersController($this->entityManager, $mailChimp);

        $this->assertSuccessfulResponse(
            $controller->update($this->getRequest($requestData), $this->getListId(), 'fake@email.com'),
            array_merge($requestData, ['list_id' => $this->getListId()])
        );
    }
}
