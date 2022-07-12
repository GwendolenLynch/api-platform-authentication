<?php

declare(strict_types=1);

namespace Camelot\Api\Authentication\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken as BaseRefreshToken;

#[ORM\Entity]
#[ORM\Table('refresh_tokens')]
class RefreshToken extends BaseRefreshToken
{
}
