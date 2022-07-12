<?php

declare(strict_types=1);

namespace Camelot\Api\Authentication\Command;

use Doctrine\Persistence\ObjectRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\LogicException;
use Throwable;

#[AsCommand(
    name: 'user:token:login',
    description: 'Login as a site user and generate a JWT token',
)]
class UserTokenLoginCommand extends Command
{
    private ObjectRepository $repository;
    private UserPasswordHasherInterface $passwordHasher;
    private RequestStack $requestStack;
    private Security $security;
    private JWTTokenManagerInterface $tokenManager;
    private string $env;

    public function __construct(ObjectRepository $repository, UserPasswordHasherInterface $passwordHasher, RequestStack $requestStack, Security $security, JWTTokenManagerInterface $tokenManager, string $env)
    {
        parent::__construct();

        $this->repository = $repository;
        $this->passwordHasher = $passwordHasher;
        $this->requestStack = $requestStack;
        $this->security = $security;
        $this->tokenManager = $tokenManager;
        $this->env = $env;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Email address')
            ->addArgument('firewall', InputArgument::OPTIONAL, 'Firewall name')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $firewall = $input->getArgument('firewall');

        if (!$email) {
            $users = array_map(fn ($u) => $u->getUserIdentifier(), $this->repository->findAll());

            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion('Please select account to generate token for', $users);
            $question->setErrorMessage('Account %s is invalid.');
            $email = $helper->ask($input, $output, $question);
        }
        $user = $this->repository->findOneBy(['email' => $email]);

        if (!$user) {
            return $this->invalid($io, new AccessDeniedHttpException('Email address invalid'));
        }

        if ($this->env === 'prod') {
            $password = $io->askHidden('Enter your password');
            if (!$password || !$this->passwordHasher->isPasswordValid($user, $password)) {
                return $this->invalid($io, new AccessDeniedHttpException('Password invalid'));
            }
        }

        $this->requestStack->push(Request::create('/'));

        $io->note(sprintf('Generating token for: %s', $email));

        try {
            $this->security->login($user, 'json_login', $firewall);
        } catch (LogicException $e) {
            return $this->invalid($io, $e);
        }

        $token = $this->tokenManager->create($user);

        $io->success(['Generated token']);
        $io->writeln("Bearer {$token}");

        return Command::SUCCESS;
    }

    private function invalid(SymfonyStyle $io, Throwable $e): int
    {
        if ($io->isVerbose()) {
            throw $e;
        }

        $io->error($e->getMessage());

        return Command::INVALID;
    }
}
