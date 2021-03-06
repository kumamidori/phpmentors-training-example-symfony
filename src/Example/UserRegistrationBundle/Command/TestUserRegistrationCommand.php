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

namespace Example\UserRegistrationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Example\UserRegistrationBundle\Entity\Factory\UserFactory;

class TestUserRegistrationCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:test:user:registration');
        $this->setDescription('ユーザー登録テスト');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $userFactory = new UserFactory();
        $user = $userFactory->create();
        $user->setLastName('久保');
        $user->setFirstName('敦啓');
        $user->setEmail('foo@iteman.jp');
        $user->setPassword('password');

        $userRegistrationUsecase = $this->getContainer()->get('example_user_registration.user_registration_usecase');
        $userRegistrationUsecase->run($user);

        $this->getContainer()->get('doctrine')->getManager()->detach($user);
        $userRegistrationUsecase->activate($user->getActivationKey());

        return 0;
    }
}
