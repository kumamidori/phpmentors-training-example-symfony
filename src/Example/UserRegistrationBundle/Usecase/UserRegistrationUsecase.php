<?php
/*
 * Copyright (c) 2012-2013 KUBO Atsuhiro <kubo@iteman.jp>,
 *               2014 YAMANE Nana <shigematsu.nana@gmail.com>,
 * All rights reserved.
 *
 * This program and the accompanying materials are made available under
 * the terms of the BSD 2-Clause License which accompanies this
 * distribution, and is available at http://opensource.org/licenses/BSD-2-Clause
 */

namespace Example\UserRegistrationBundle\Usecase;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\Util\SecureRandomInterface;
use PHPMentors\DomainKata\Entity\EntityInterface;
use PHPMentors\DomainKata\Usecase\CommandUsecaseInterface;

use Example\UserRegistrationBundle\Entity\User;
use Example\UserRegistrationBundle\Transfer\UserTransfer;

class UserRegistrationUsecase implements CommandUsecaseInterface
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    /**
     * @var \Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface
     */
    protected $passwordEncoder;

    /**
     * @var \Symfony\Component\Security\Core\Util\SecureRandomInterface
     */
    protected $secureRandom;

    /**
     * @var \Example\UserRegistrationBundle\Transfer\UserTransfer
     */
    protected $userTransfer;

    /**
     * @param \Doctrine\ORM\EntityManager                                       $entityManager
     * @param \Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface $passwordEncoder
     * @param \Symfony\Component\Security\Core\Util\SecureRandomInterface       $secureRandom
     * @param \Example\UserRegistrationBundle\Transfer\UserTransfer             $userTransfer
     */
    public function __construct(EntityManager $entityManager, PasswordEncoderInterface $passwordEncoder, SecureRandomInterface $secureRandom, UserTransfer $userTransfer)
    {
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->secureRandom = $secureRandom;
        $this->userTransfer = $userTransfer;
    }

    /**
     * @param  \PHPMentors\DomainKata\Entity\EntityInterface $user
     * @throws \UnexpectedValueException
     */
    public function run(EntityInterface $user)
    {
        $user->setActivationKey(base64_encode($this->secureRandom->nextBytes(24)));
        $user->setPassword($this->passwordEncoder->encodePassword($user->getPassword(), User::SALT));
        $user->setRegistrationDate(new \DateTime());

        $this->entityManager->getRepository('Example\UserRegistrationBundle\Entity\User')->add($user);
        $this->entityManager->flush();

        $emailSent = $this->userTransfer->sendActivationEmail($user);
        if (!$emailSent) {
            throw new \UnexpectedValueException('アクティベーションメールの送信に失敗しました。');
        }
    }

    /**
     * @param string $activationKey
     * @throws \UnexpectedValueException
     */
    public function activate($activationKey)
    {
        $user = $this->entityManager->getRepository('Example\UserRegistrationBundle\Entity\User')->findOneByActivationKey($activationKey);
        if (is_null($user)) {
            throw new \UnexpectedValueException('アクティベーションキーが見つかりません。');
        }
        if (!is_null($user->getActivationDate())) {
            throw new \UnexpectedValueException('ユーザーはすでに有効です。');
        }
        $user->setActivationDate(new \DateTime());
        $this->entityManager->flush();
    }
}
