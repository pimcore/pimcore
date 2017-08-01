<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Document\Tag\NamingStrategy\Migration;

use Pimcore\Bundle\AdminBundle\Security\User\UserLoader;
use Pimcore\Bundle\AdminBundle\Session\AdminSessionBagConfigurator;
use Pimcore\Bundle\AdminBundle\Session\Handler\AdminSessionHandler;
use Pimcore\Cache;
use Pimcore\Document\Tag\NamingStrategy\Migration\Exception\NameMappingException;
use Pimcore\Document\Tag\NamingStrategy\Migration\Render\MigrationSubscriber;
use Pimcore\Model\Document;
use Pimcore\Model\User;
use Pimcore\Routing\Dynamic\DocumentRouteHandler;
use Pimcore\Service\Request\EditmodeResolver;
use Pimcore\Tool;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Stopwatch\Stopwatch;

class RenderMigrationStrategy extends AbstractMigrationStrategy
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var EditmodeResolver
     */
    private $editmodeResolver;

    /**
     * @var UserLoader
     */
    private $userLoader;

    /**
     * @var AdminSessionBagConfigurator
     */
    private $adminSessionConfigurator;

    /**
     * @var DocumentRouteHandler
     */
    private $documentRouteHandler;

    /**
     * @var MigrationSubscriber
     */
    private $subscriber;

    /**
     * @param EventDispatcherInterface $dispatcher
     * @param RequestStack $requestStack
     * @param EditmodeResolver $editmodeResolver
     * @param UserLoader $userLoader
     * @param AdminSessionBagConfigurator $adminSessionConfigurator
     * @param DocumentRouteHandler $documentRouteHandler
     */
    public function __construct(
        EventDispatcherInterface $dispatcher,
        RequestStack $requestStack,
        EditmodeResolver $editmodeResolver,
        UserLoader $userLoader,
        AdminSessionBagConfigurator $adminSessionConfigurator,
        DocumentRouteHandler $documentRouteHandler
    ) {
        $this->dispatcher               = $dispatcher;
        $this->requestStack             = $requestStack;
        $this->editmodeResolver         = $editmodeResolver;
        $this->userLoader               = $userLoader;
        $this->adminSessionConfigurator = $adminSessionConfigurator;
        $this->documentRouteHandler     = $documentRouteHandler;
    }

    public function getName(): string
    {
        return 'render';
    }

    public function getStepDescription(): string
    {
        return 'Rendering all documents to gather new element mapping...';
    }

    protected function initializeEnvironment()
    {
        try {
            $this->initializeUser($this->io->getInput());
        } catch (\InvalidArgumentException $e) {
            $this->io->error($e->getMessage());

            $e = new NameMappingException($e->getMessage(), 1, $e);
            $e->setShowMessage(true);

            throw $e;
        }

        $this->initializeAdminMode();
        $this->initializeSession();

        if (!$this->askRunConfirmation()) {
            throw new NameMappingException('Aborting migration', 0);
        }

        // register migration subscriber which renders tags through new naming strategy and
        // builds a mapping of old -> new names
        $this->subscriber = new MigrationSubscriber($this->namingStrategy);
        $this->dispatcher->addSubscriber($this->subscriber);

        // push dummy request to stack to make document renderer work
        $this->requestStack->push(Request::create('/'));

        // set editmode resolver to force editmode (always resolves to editmode no matter if request params are set or not)
        $this->editmodeResolver->setForceEditmode(true);

        // configure document route handler to build routes for unpublished documents
        $this->documentRouteHandler->setForceHandleUnpublishedDocuments(true);
    }

    /**
     * Initializes given user and checks if the user is admin
     *
     * @param InputInterface $input
     */
    private function initializeUser(InputInterface $input)
    {
        $username = $input->getOption('user');

        /** @var User $user */
        $user = User::getByName($username);

        if (!$user) {
            throw new \InvalidArgumentException(sprintf('User "%s" could not be loaded'));
        }

        if (!$user->isAdmin()) {
            throw new \InvalidArgumentException(sprintf('User "%s" does not have admin rights'));
        }

        // See ElementListener. The UserLoader will be used to fetch the admin user when rendering
        // documents to make sure unpublished documents can't be seen by non-admin requests. By setting
        // the user on the loader, no session lookup will be done and this user will be used instead.
        $this->userLoader->setUser($user);
    }

    private function initializeAdminMode()
    {
        Cache::disable();
        \Pimcore::setAdminMode();
        Document::setHideUnpublished(false);
    }

    /**
     * Sets admin session to a mock array session to make sure any session related functionality works
     */
    private function initializeSession()
    {
        $session = new Session(new MockArraySessionStorage());
        $this->adminSessionConfigurator->configure($session);

        $handler = new AdminSessionHandler($session);

        Tool\Session::setHandler($handler);
    }

    public function getNameMapping(\Generator $documents, CacheInterface $cache): array
    {
        $stopwatch     = new Stopwatch();
        $totalDuration = 0; // TODO can total duration be read directly from Stopwatch?

        $errors = [];

        $cachedMapping = $cache->get('mapping', []);
        if (!empty($cachedMapping)) {
            $this->subscriber->setNameMapping($cachedMapping);
        }

        $i = 0;
        foreach ($documents as $document) {
            $event = $stopwatch->start('document_' . $document->getId());

            if (isset($cachedMapping[$document->getId()])) {
                $this->io->writeln(sprintf(
                    'Loading document <info>%s</info> with ID <info>%d</info> from cache',
                    $document->getRealFullPath(),
                    $document->getId()
                ));
            } else {
                $this->io->writeln(sprintf(
                    'Rendering document <info>%s</info> with ID <info>%d</info>',
                    $document->getRealFullPath(),
                    $document->getId()
                ));

                try {
                    Document\Service::render(
                        $document,
                        [],
                        false,
                        ['pimcore_editmode' => true]
                    );

                    $cache->set('mapping', $this->subscriber->getNameMapping());
                } catch (\Throwable $e) {
                    $errors[$document->getId()] = new MappingError($document, $e);
                    $this->io->error($e->getMessage());
                }
            }

            $event->stop();
            $totalDuration += $event->getDuration();

            $this->io->writeln(sprintf(
                'Duration: <comment>%d ms</comment> - Total duration: <comment>%s</comment> - Current Memory: <comment>%s</comment>',
                $event->getDuration(),
                Helper::formatTime($totalDuration / 1000),
                Helper::formatMemory($event->getMemory())
            ));

            $this->io->newLine();

            if ($i++ % 25 === 0) {
                \Pimcore::collectGarbage();
            }
        }

        $this->io->newLine();

        if (count($errors) > 0) {
            $this->io->error('Not all documents could be rendered.');

            $this->showMappingErrors(
                $errors,
                'The following errors were encountered while rendering the selected documents:',
                'Please fix any rendering issues before proceeding. You can try if rendering works on single documents by passing the --document and --dry-run options.'
            );

            throw new NameMappingException('Aborting migration as not all documents could be rendered', 3);
        }

        $this->io->success('All documents were rendered successfully, now proceeding to update names based on the gathered mapping');

        $mapping = $this->subscriber->getNameMapping();

        return $mapping;
    }

    private function askRunConfirmation(): bool
    {
        $this->io->writeln(
            <<<EOF
<comment>[WARNING]</comment> Please be aware that the <comment>render</comment> migration
strategy can only migrate documents which can be successfully rendered and will only handle elements which
are used in your template. If you have any elements which are not used in the template (e.g. because they
are commented out or depend on a certain logic) they can't be automatically migrated and will be removed
the next time you save the document in the admin interface.
EOF
        );

        $this->io->newLine();

        $this->io->writeln(
            <<<EOF
To make the transition as smooth as possible it's recommended to update all your templates to render any
needed editables at least in editmode. The command simulates the editmode, so you can rely on the editmode
parameter to be set in your views.
EOF
        );

        if (!$this->io->getInput()->isInteractive()) {
            $this->io->newLine(2);

            return true;
        }

        return (bool)$this->io->confirm(
            'Do you wish to continue?',
            false
        );
    }
}
