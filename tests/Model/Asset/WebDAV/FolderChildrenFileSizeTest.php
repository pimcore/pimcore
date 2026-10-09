<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Tests\Model\Asset\WebDAV;

use Pimcore;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\WebDAV\File;
use Pimcore\Model\Asset\WebDAV\Folder;
use Pimcore\Model\User;
use Pimcore\Security\User\TokenStorageUserResolver;
use Pimcore\Security\User\User as SecurityUser;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool\Storage;
use ReflectionProperty;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * A PROPFIND on a folder asks for the size of every child. Folder::getChildren() reads all of
 * those sizes with one storage listing, because asking the storage per file costs one request per
 * child on remote storage (S3, Azure Blob, ...) and does not finish in time for large folders.
 *
 * @group model.asset.webdav
 */
class FolderChildrenFileSizeTest extends ModelTestCase
{
    private ?User $createdUser = null;

    private ?TokenInterface $originalToken = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalToken = $this->tokenStorage()->getToken();
        $this->loginAsAdmin();
    }

    protected function tearDown(): void
    {
        $this->tokenStorage()->setToken($this->originalToken);

        $this->createdUser?->delete();

        parent::tearDown();
    }

    public function testChildrenTakeTheirSizesFromTheFolderListing(): void
    {
        $folder = TestHelper::createAssetFolder('webdav-sizes-');
        $small = $this->createAsset($folder, 'small.txt', 'abc');
        $large = $this->createAsset($folder, 'large.txt', str_repeat('x', 4096));
        $subfolder = new Asset\Folder();
        $subfolder->setParent($folder);
        $subfolder->setFilename('subfolder');
        $subfolder->save();

        $children = (new Folder($folder))->getChildren();

        // remove the files behind the assets' backs: a per-file lookup would now fail and report 0,
        // so the sizes asserted below can only come from the listing made by getChildren()
        $storage = Storage::get('asset');
        $storage->delete($small->getRealFullPath());
        $storage->delete($large->getRealFullPath());

        $sizes = [];
        $folderNames = [];
        foreach ($children as $child) {
            if ($child instanceof File) {
                $sizes[$child->getName()] = $child->getSize();
            } elseif ($child instanceof Folder) {
                $folderNames[] = $child->getName();
            }
        }
        ksort($sizes);

        $this->assertSame(['large.txt' => 4096, 'small.txt' => 3], $sizes);
        $this->assertSame(['subfolder'], $folderNames);
    }

    public function testFileWithoutAListedSizeFallsBackToAPerFileLookup(): void
    {
        $folder = TestHelper::createAssetFolder('webdav-sizes-');
        $asset = $this->createAsset($folder, 'unlisted.txt', 'hello');

        $this->assertSame(5, (new File($asset))->getSize());
    }

    public function testPutDiscardsTheListedSize(): void
    {
        $folder = TestHelper::createAssetFolder('webdav-sizes-');
        $this->createAsset($folder, 'replaced.txt', 'abc');

        $children = (new Folder($folder))->getChildren();
        $this->assertCount(1, $children);
        $file = $children[0];
        $this->assertInstanceOf(File::class, $file);

        $newContent = 'new, longer content';
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $newContent);
        rewind($stream);
        $file->put($stream);
        fclose($stream);

        $this->assertSame(strlen($newContent), $file->getSize());
    }

    private function createAsset(Asset $parent, string $filename, string $data): Asset
    {
        $asset = new Asset();
        $asset->setParent($parent);
        $asset->setFilename($filename);
        $asset->setData($data);
        $asset->save();

        return $asset;
    }

    private function loginAsAdmin(): void
    {
        $user = new User();
        $user->setParentId(0);
        $user->setName('webdav_sizes_user_' . uniqid());
        $user->setActive(true);
        $user->setAdmin(true);
        $user->save();
        $this->createdUser = $user;

        $this->tokenStorage()->setToken(new UsernamePasswordToken(new SecurityUser($user), 'pimcore_admin'));
    }

    private function tokenStorage(): TokenStorageInterface
    {
        // security.token_storage is inlined out of the compiled container and cannot be fetched by
        // id. Admin::getCurrentUser() reads the token storage held by the public
        // TokenStorageUserResolver service, so we reach the same shared instance through it.
        $resolver = Pimcore::getContainer()->get(TokenStorageUserResolver::class);

        return (new ReflectionProperty(TokenStorageUserResolver::class, 'tokenStorage'))->getValue($resolver);
    }
}
