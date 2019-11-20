<?php

namespace App\Security;

use Coffreo\Bundle\AuthenticationBundle\Security\CoffreoBaseUser;
use Coffreo\Bundle\AuthenticationBundle\Security\CoffreoUserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserProvider implements CoffreoUserProviderInterface
{
    /**
     * This method allow you to build a more specific user (based on a "generic" user)
     * to match with your needs.
     *
     * @param CoffreoBaseUser $coffreoBaseUser
     *
     * @return CoffreoBaseUser
     */
    public function promoteUser(CoffreoBaseUser $coffreoBaseUser)
    {
        return $coffreoBaseUser;
    }

    /**
     * This method allow you to refresh a user in any way you want, or just return the current user.
     *
     * @param UserInterface $user
     * @return UserInterface
     */
    public function refreshUser(UserInterface $user)
    {
        return $user;
    }
}
