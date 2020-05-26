<?php
declare(strict_types=1);

namespace App\Database\Entities\MailChimp;

use Doctrine\ORM\Mapping as ORM;
use EoneoPay\Utils\Str;

/**
 * @ORM\Entity()
 */
class MailChimpMember extends MailChimpEntity
{
    /**
     * @ORM\Id()
     * @ORM\Column(name="id", type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     *
     * @var string
     */
    private $memberId;

    /**
     * @ORM\Column(name="list_id", type="string")
     *
     * @var string
     */
    private $listId;

    /**
     * @ORM\Column(name="email_address", type="string")
     *
     * @var string
     */
    private $emailAddress;

    /**
     * @ORM\Column(name="status", type="string")
     * @var string
     */
    private $status;

    /**
     * @ORM\Column(name="merge_fields", type="json", nullable=true)
     * @var array
     */
    private $mergeFields;

    /**
     * @ORM\Column(name="language", type="string", nullable=true, length=2)
     * @var string
     */
    private $language;

    /**
     * @ORM\Column(name="vip", type="boolean", options={"default": false})
     * @var bool
     */
    private $vip;

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->memberId;
    }

    /**
     * Get mailchimp id of the member.
     *
     * @return null|string
     */
    public function getMailChimpId(): ?string
    {
        return null !== $this->emailAddress ? strtolower(md5($this->emailAddress)) : null;
    }

    public function getValidationRules(): array
    {
        return [
            'list_id' => 'required|string',
            'email_address' => 'required|email',
            'status' => 'required|string|in:subscribed,unsubscribed,cleaned,pending,transactional',
            'merge_fields' => 'nullable|array',
            'merge_fields.FNAME' => 'nullable|string',
            'merge_fields.LNAME' => 'nullable|string',
            'merge_fields.PHONE' => 'nullable|regex:/\+[0-9]{11}/',
            'merge_fields.ADDRESS' => 'nullable|array',
            'merge_fields.ADDRESS.zip' => 'required|string',
            'merge_fields.ADDRESS.city' => 'required|string',
            'merge_fields.ADDRESS.addr1' => 'required|string',
            'merge_fields.ADDRESS.addr2' => 'nullable|string',
            'merge_fields.ADDRESS.state' => 'required|string',
            'merge_fields.ADDRESS.country' => 'nullable|string|size:2',
            'merge_fields.BIRTHDAY' => 'nullable|date_format:m/d',
            'language' => 'nullable|string|size:2',
            'vip' => 'nullable|boolean',
        ];
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $array = [];
        $str = new Str();

        foreach (\get_object_vars($this) as $property => $value) {
            $array[$str->snake($property)] = $value;
        }

        return $array;
    }

    /**
     * @param string $listId
     *
     * @return MailChimpMember
     */
    public function setListId(string $listId): MailChimpMember
    {
        $this->listId = $listId;

        return $this;
    }

    /**
     * @param string $emailAddress
     *
     * @return MailChimpMember
     */
    public function setEmailAddress(string $emailAddress): MailChimpMember
    {
        $this->emailAddress = $emailAddress;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    /**
     * @param string $status
     *
     * @return MailChimpMember
     */
    public function setStatus(string $status): MailChimpMember
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @param array $mergeFields
     *
     * @return MailChimpMember
     */
    public function setMergeFields(array $mergeFields): MailChimpMember
    {
        $this->mergeFields = $mergeFields;

        return $this;
    }

    /**
     * @param string $language
     *
     * @return MailChimpMember
     */
    public function setLanguage(string $language): MailChimpMember
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @param bool $vip
     *
     * @return MailChimpMember
     */
    public function setVip(bool $vip): MailChimpMember
    {
        $this->vip = $vip;

        return $this;
    }
}
