<?php

declare(strict_types=1);

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait RelationsModifiedAtTrait
{
    private const DB_DATETIME_FORMAT = 'Y-m-d H:i:s';

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $relationsModifiedAt;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::JSON, nullable: false, options: ["default" => "{}"])]
    private array $relationsModified = [];

    public function getRelationsModifiedAt(): ?\DateTimeImmutable
    {
        return $this->relationsModifiedAt;
    }

    public function setRelationsModifiedAt(?\DateTimeImmutable $modifiedAt): self
    {
        $this->relationsModifiedAt = $modifiedAt;

        return $this;
    }

    public function getRelationsModified(): array
    {
        return array_map(function ($value) {
            $dateTime = \DateTimeImmutable::createFromFormat(self::DB_DATETIME_FORMAT, $value);

            if (false === $dateTime) {
                throw new \InvalidArgumentException(sprintf('Datetime value must be by format "%s", "%s" given', self::DB_DATETIME_FORMAT, $value));
            }

            return \DateTimeImmutable::createFromFormat(self::DB_DATETIME_FORMAT, $value);
        }, array_filter($this->relationsModified));
    }

    public function setRelationsModified(array $relationsModified): void
    {
        $maxModified = null;

        foreach ($relationsModified as $key => $value) {
            if (null === $value) {
                continue;
            }
            if ($value instanceof \DateTimeInterface) {
                $relationsModified[$key] = $value->format(self::DB_DATETIME_FORMAT);
                $maxModified = max($maxModified, $value);
            } else {
                // Validate string format is valid date
                $dateTime = \DateTimeImmutable::createFromFormat(self::DB_DATETIME_FORMAT, $value);
                if (false === $dateTime) {
                    throw new \InvalidArgumentException(sprintf('Datetime value must be by format "%s", "%s" given', self::DB_DATETIME_FORMAT, $value));
                }
                $maxModified = max($maxModified, $dateTime);
            }
        }

        $this->setRelationsModifiedAt($maxModified);

        $this->relationsModified = $relationsModified;
    }


}
