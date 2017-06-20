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

namespace Pimcore\Tests\Unit\Document\Tag\NamingStrategy\Migration;

use Pimcore\Document\Tag\NamingStrategy\Migration\Analyze\ElementTree;
use Pimcore\Document\Tag\NamingStrategy\NestedNamingStrategy;
use Pimcore\Model\Document;
use Pimcore\Tests\Test\TestCase;
use Pimcore\Tests\Unit\Document\Tag\NamingStrategy\Migration\Analyze\MappingConflictResolver;

class ElementTreeTest extends TestCase
{
    /**
     * @dataProvider treeDataProvider
     *
     * @param array $config
     */
    public function testElementTree(array $config)
    {
        $resolutions = $config['resolutions'] ?? [];

        $namingStrategy   = new NestedNamingStrategy();
        $conflictResolver = new MappingConflictResolver($namingStrategy, $resolutions);

        /** @var Document\PageSnippet|\PHPUnit_Framework_MockObject_MockObject $document */
        $document = $this
            ->getMockBuilder(Document\PageSnippet::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $tree = new ElementTree($document, $conflictResolver);
        foreach ($config['elements'] as $configElement) {
            $tree->add(
                $configElement['name'],
                $configElement['type'],
                $configElement['data'],
                $configElement['inherited']
            );
        }

        $mapping = [];
        foreach ($tree->getElements() as $element) {
            $newName = $element->getNameForStrategy($namingStrategy);

            if ($newName === $element->getName()) {
                continue;
            }

            $mapping[$element->getName()] = $newName;
        }

        $this->assertEquals($config['mapping'], $mapping);
    }

    public function treeDataProvider()
    {
        return [
            [
                [
                    'elements' => [
                        [
                            'name'      => 'AB-B-ABcontent1_AB-Bcontent111_1',
                            'type'      => 'areablock',
                            'data'      => 'a:1:{i:0;a:2:{s:3:"key";s:1:"1";s:4:"type";s:7:"wysiwyg";}}',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'AB-B-ABcontent_AB-Bcontent11_1',
                            'type'      => 'areablock',
                            'data'      => 'a:1:{i:0;a:2:{s:3:"key";s:1:"1";s:4:"type";s:7:"wysiwyg";}}',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'AB-Bcontent1',
                            'type'      => 'block',
                            'data'      => 'a:1:{i:0;s:1:"1";}',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'AB-Bcontent11',
                            'type'      => 'block',
                            'data'      => 'a:1:{i:0;s:1:"1";}',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'content',
                            'type'      => 'areablock',
                            'data'      => 'a:1:{i:0;a:2:{s:3:"key";s:1:"1";s:4:"type";s:5:"block";}}',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'content1',
                            'type'      => 'areablock',
                            'data'      => 'a:1:{i:0;a:2:{s:3:"key";s:1:"1";s:4:"type";s:5:"block";}}',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'contentcontent1_AB-Bcontent11_AB-B-ABcontent1_AB-Bcontent111_11_1_1',
                            'type'      => 'wysiwyg',
                            'data'      => "<p>bar!</p>\n",
                            'inherited' => false
                        ],
                        [
                            'name'      => 'contentcontent_AB-Bcontent1_AB-B-ABcontent_AB-Bcontent11_11_1_1',
                            'type'      => 'wysiwyg',
                            'data'      => "<p>foo!</p>\n",
                            'inherited' => false
                        ],
                        [
                            'name'      => 'headDescription',
                            'type'      => 'input',
                            'data'      => '',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'headline',
                            'type'      => 'input',
                            'data'      => '',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'headTitle',
                            'type'      => 'input',
                            'data'      => '',
                            'inherited' => false
                        ]
                    ],
                    'mapping'  => [
                        'AB-Bcontent1'                                                        => 'content:1.AB-B',
                        'AB-Bcontent11'                                                       => 'content1:1.AB-B',
                        'AB-B-ABcontent1_AB-Bcontent111_1'                                    => 'content1:1.AB-B:1.AB-B-AB',
                        'AB-B-ABcontent_AB-Bcontent11_1'                                      => 'content:1.AB-B:1.AB-B-AB',
                        'contentcontent1_AB-Bcontent11_AB-B-ABcontent1_AB-Bcontent111_11_1_1' => 'content1:1.AB-B:1.AB-B-AB:1.content',
                        'contentcontent_AB-Bcontent1_AB-B-ABcontent_AB-Bcontent11_11_1_1'     => 'content:1.AB-B:1.AB-B-AB:1.content'
                    ]
                ]
            ],
            [
                [
                    'resolutions' => [
                        'accordioncontent11' => 'content1:1.accordion',
                        'imagescontent11'    => 'content:11.images',
                        'contentcontent11'   => 'content:11.content'
                    ],
                    'elements'    => [
                        [
                            'name'      => 'accordioncontent11',
                            'type'      => 'block',
                            'data'      => 'a:2:{i:0;s:1:"1";i:1;s:1:"2";}',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'accordioncontent7',
                            'type'      => 'block',
                            'data'      => 'a:4:{i:0;s:1:"1";i:1;s:1:"2";i:2;s:1:"3";i:3;s:1:"4";}',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'authorcontent5',
                            'type'      => 'input',
                            'data'      => 'Albert Einstein',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'blockcontent1',
                            'type'      => 'block',
                            'data'      => 'a:2:{i:0;s:1:"1";i:1;s:1:"2";}',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'content',
                            'type'      => 'areablock',
                            'data'      => 'a:11:{i:0;a:2:{s:3:"key";s:1:"6";s:4:"type";s:9:"headlines";}i:1;a:2:{s:3:"key";s:2:"11";s:4:"type";s:19:"wysiwyg-with-images";}i:2;a:2:{s:3:"key";s:1:"2";s:4:"type";s:21:"gallery-single-images";}i:3;a:2:{s:3:"key";s:1:"3";s:4:"type";s:7:"wysiwyg";}i:4;a:2:{s:3:"key";s:1:"5";s:4:"type";s:10:"blockquote";}i:5;a:2:{s:3:"key";s:1:"9";s:4:"type";s:15:"horizontal-line";}i:6;a:2:{s:3:"key";s:1:"1";s:4:"type";s:10:"featurette";}i:7;a:2:{s:3:"key";s:1:"8";s:4:"type";s:15:"horizontal-line";}i:8;a:2:{s:3:"key";s:1:"4";s:4:"type";s:5:"image";}i:9;a:2:{s:3:"key";s:1:"7";s:4:"type";s:14:"text-accordion";}i:10;a:2:{s:3:"key";s:2:"10";s:4:"type";s:15:"icon-teaser-row";}}',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'content1',
                            'type'      => 'areablock',
                            'data'      => 'a:1:{i:0;a:2:{s:3:"key";s:1:"1";s:4:"type";s:14:"text-accordion";}}',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'contentcontent11',
                            'type'      => 'wysiwyg',
                            'data'      => "<p>Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca:</p>\n\n<p>&nbsp;</p>\n\n<p>On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles. Ma quande lingues coalesce, li grammatica del resultant lingue es plu simplic e regulari quam ti del coalescent lingues. Li nov lingua franca va esser plu simplic e regulari quam li existent Europan lingues. It va esser tam simplic quam Occidental in fact, it va esser Occidental.</p>\n\n<p>&nbsp;</p>\n\n<p>A un Angleso it va semblar un simplificat Angles, quam un skeptic Cambridge amico dit me que Occidental es.Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles.</p>\n",
                            'inherited' => false
                        ],
                        [
                            'name'      => 'contentcontent3',
                            'type'      => 'wysiwyg',
                            'data'      => "<p>Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim.</p>\n\n<p>&nbsp;</p>\n\n<ul>\n\t<li>Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus.</li>\n\t<li>Phasellus viverra nulla ut metus varius laoreet.</li>\n\t<li>Quisque rutrum. Aenean imperdiet.</li>\n</ul>\n\n<p>&nbsp;</p>\n\n<p>Etiam ultricies nisi vel augue. Curabitur <a href=\"/basic-examples/galleries\">ullamcorper </a>ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus.</p>\n",
                            'inherited' => false
                        ],
                        [
                            'name'      => 'contentcontent_blockcontent11_1',
                            'type'      => 'wysiwyg',
                            'data'      => "<p>Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.</p>\n",
                            'inherited' => false
                        ],
                        [
                            'name'      => 'contentcontent_blockcontent11_2',
                            'type'      => 'wysiwyg',
                            'data'      => "<p>Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna.</p>\n",
                            'inherited' => false
                        ],
                        [
                            'name'      => 'gallerycontent2',
                            'type'      => 'block',
                            'data'      => 'a:4:{i:0;s:1:"1";i:1;s:1:"2";i:2;s:1:"3";i:3;s:1:"4";}',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'headDescription',
                            'type'      => 'input',
                            'data'      => '',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'headline',
                            'type'      => 'input',
                            'data'      => 'This is just a simple Content-Page ...',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'headlinecontent1_accordioncontent111_1',
                            'type'      => 'input',
                            'data'      => 'Foo',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'headlinecontent1_accordioncontent111_2',
                            'type'      => 'input',
                            'data'      => 'Baz',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'headlinecontent6',
                            'type'      => 'input',
                            'data'      => 'Where some Content-Blocks are mixed together.',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'headlinecontent_accordioncontent77_1',
                            'type'      => 'input',
                            'data'      => 'Lorem ipsum dolor sit amet',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'headlinecontent_accordioncontent77_2',
                            'type'      => 'input',
                            'data'      => ' Cum sociis natoque penatibus et magnis dis parturient montes',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'headlinecontent_accordioncontent77_3',
                            'type'      => 'input',
                            'data'      => 'Donec pede justo, fringilla vel',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'headlinecontent_accordioncontent77_4',
                            'type'      => 'input',
                            'data'      => 'Maecenas tempus, tellus eget condimentum rhoncus',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'headlinecontent_blockcontent11_1',
                            'type'      => 'input',
                            'data'      => 'Lorem ipsum.',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'headlinecontent_blockcontent11_2',
                            'type'      => 'input',
                            'data'      => 'Etiam ultricies.',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'headTitle',
                            'type'      => 'input',
                            'data'      => '',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'icon_0content10',
                            'type'      => 'select',
                            'data'      => 'thumbs-up',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'icon_1content10',
                            'type'      => 'select',
                            'data'      => 'qrcode',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'icon_2content10',
                            'type'      => 'select',
                            'data'      => 'trash',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'imagecontent4',
                            'type'      => 'image',
                            'data'      => 'a:9:{s:2:"id";i:53;s:3:"alt";s:0:"";s:11:"cropPercent";N;s:9:"cropWidth";N;s:10:"cropHeight";N;s:7:"cropTop";N;s:8:"cropLeft";N;s:8:"hotspots";a:0:{}s:6:"marker";a:0:{}}',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'imagecontent_blockcontent11_1',
                            'type'      => 'image',
                            'data'      => 'a:9:{s:2:"id";i:48;s:3:"alt";s:0:"";s:11:"cropPercent";N;s:9:"cropWidth";N;s:10:"cropHeight";N;s:7:"cropTop";N;s:8:"cropLeft";N;s:8:"hotspots";a:0:{}s:6:"marker";a:0:{}}',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'imagecontent_gallerycontent22_1',
                            'type'      => 'image',
                            'data'      => 'a:9:{s:2:"id";i:51;s:3:"alt";s:0:"";s:11:"cropPercent";N;s:9:"cropWidth";N;s:10:"cropHeight";N;s:7:"cropTop";N;s:8:"cropLeft";N;s:8:"hotspots";a:0:{}s:6:"marker";a:0:{}}',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'imagecontent_gallerycontent22_2',
                            'type'      => 'image',
                            'data'      => 'a:9:{s:2:"id";i:52;s:3:"alt";s:0:"";s:11:"cropPercent";N;s:9:"cropWidth";N;s:10:"cropHeight";N;s:7:"cropTop";N;s:8:"cropLeft";N;s:8:"hotspots";a:0:{}s:6:"marker";a:0:{}}',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'imagecontent_gallerycontent22_3',
                            'type'      => 'image',
                            'data'      => 'a:9:{s:2:"id";i:44;s:3:"alt";s:0:"";s:11:"cropPercent";N;s:9:"cropWidth";N;s:10:"cropHeight";N;s:7:"cropTop";N;s:8:"cropLeft";N;s:8:"hotspots";a:0:{}s:6:"marker";a:0:{}}',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'imagecontent_gallerycontent22_4',
                            'type'      => 'image',
                            'data'      => 'a:9:{s:2:"id";i:49;s:3:"alt";s:0:"";s:11:"cropPercent";N;s:9:"cropWidth";N;s:10:"cropHeight";N;s:7:"cropTop";N;s:8:"cropLeft";N;s:8:"hotspots";a:0:{}s:6:"marker";a:0:{}}',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'imagecontent_imagescontent1111_1',
                            'type'      => 'image',
                            'data'      => 'a:9:{s:2:"id";i:22;s:3:"alt";s:0:"";s:11:"cropPercent";N;s:9:"cropWidth";N;s:10:"cropHeight";N;s:7:"cropTop";N;s:8:"cropLeft";N;s:8:"hotspots";a:0:{}s:6:"marker";a:0:{}}',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'imagecontent_imagescontent1111_2',
                            'type'      => 'image',
                            'data'      => 'a:9:{s:2:"id";i:24;s:3:"alt";s:0:"";s:11:"cropPercent";N;s:9:"cropWidth";N;s:10:"cropHeight";N;s:7:"cropTop";N;s:8:"cropLeft";N;s:8:"hotspots";a:0:{}s:6:"marker";a:0:{}}',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'imagescontent11',
                            'type'      => 'block',
                            'data'      => 'a:2:{i:0;s:1:"1";i:1;s:1:"2";}',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'leadcontent6',
                            'type'      => 'wysiwyg',
                            'data'      => '',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'link_0content10',
                            'type'      => 'link',
                            'data'      => 'a:15:{s:4:"text";s:13:"See in Action";s:4:"path";s:30:"/en/basic-examples/html5-video";s:6:"target";s:0:"";s:10:"parameters";s:0:"";s:6:"anchor";s:0:"";s:5:"title";s:0:"";s:9:"accesskey";s:0:"";s:3:"rel";s:0:"";s:8:"tabindex";s:0:"";s:5:"class";s:0:"";s:10:"attributes";s:0:"";s:8:"internal";b:1;s:10:"internalId";i:7;s:12:"internalType";s:8:"document";s:4:"type";s:8:"internal";}',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'link_1content10',
                            'type'      => 'link',
                            'data'      => 'a:15:{s:4:"text";s:9:"Read More";s:4:"path";s:29:"/en/basic-examples/thumbnails";s:6:"target";s:0:"";s:10:"parameters";s:0:"";s:6:"anchor";s:0:"";s:5:"title";s:0:"";s:9:"accesskey";s:0:"";s:3:"rel";s:0:"";s:8:"tabindex";s:0:"";s:5:"class";s:0:"";s:10:"attributes";s:0:"";s:8:"internal";b:1;s:10:"internalId";i:21;s:12:"internalType";s:8:"document";s:4:"type";s:8:"internal";}',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'link_2content10',
                            'type'      => 'link',
                            'data'      => 'a:15:{s:4:"text";s:10:"Try it now";s:4:"path";s:23:"/en/basic-examples/news";s:6:"target";s:0:"";s:10:"parameters";s:0:"";s:6:"anchor";s:0:"";s:5:"title";s:0:"";s:9:"accesskey";s:0:"";s:3:"rel";s:0:"";s:8:"tabindex";s:0:"";s:5:"class";s:0:"";s:10:"attributes";s:0:"";s:8:"internal";b:1;s:10:"internalId";i:27;s:12:"internalType";s:8:"document";s:4:"type";s:8:"internal";}',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'positioncontent_blockcontent11_1',
                            'type'      => 'select',
                            'data'      => null,
                            'inherited' => false
                        ],
                        [
                            'name'      => 'positioncontent_blockcontent11_2',
                            'type'      => 'select',
                            'data'      => 'left',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'quotecontent5',
                            'type'      => 'input',
                            'data'      => "We can't solve problems by using the same kind of thinking we used when we created them.",
                            'inherited' => false
                        ],
                        [
                            'name'      => 'sublinecontent_blockcontent11_1',
                            'type'      => 'input',
                            'data'      => 'Dolor sit amet.',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'sublinecontent_blockcontent11_2',
                            'type'      => 'input',
                            'data'      => 'Nam eget dui.',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'textcontent1_accordioncontent111_1',
                            'type'      => 'wysiwyg',
                            'data'      => "<p>Bar</p>\n",
                            'inherited' => false
                        ],
                        [
                            'name'      => 'textcontent1_accordioncontent111_2',
                            'type'      => 'wysiwyg',
                            'data'      => "<p>Inga</p>\n",
                            'inherited' => false
                        ],
                        [
                            'name'      => 'textcontent_accordioncontent77_1',
                            'type'      => 'wysiwyg',
                            'data'      => "<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean <a href=\"/en/basic-examples/thumbnails\" pimcore_id=\"21\" pimcore_type=\"document\">commodo </a>ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim.</p>\n\n<p>&nbsp;</p>\n\n<p>Donec pede justo, fringilla vel, aliquet nec, <strong>vulputate </strong>eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus <a href=\"/en/basic-examples/form\" pimcore_id=\"26\" pimcore_type=\"document\">elementum </a>semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet.</p>\n\n<p>&nbsp;</p>\n\n<p>Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget <u>condimentum </u>rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc,</p>\n",
                            'inherited' => false
                        ],
                        [
                            'name'      => 'textcontent_accordioncontent77_2',
                            'type'      => 'wysiwyg',
                            'data'      => "<p>Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca:</p>\n\n<p>&nbsp;</p>\n\n<p>On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles. Ma quande lingues coalesce, li grammatica del resultant lingue es plu simplic e regulari quam ti del coalescent lingues. Li nov lingua franca va esser plu simplic e regulari quam li existent Europan lingues. It va esser tam simplic quam Occidental in fact, it va esser Occidental.</p>\n\n<p>&nbsp;</p>\n\n<p>A un Angleso it va semblar un simplificat Angles, quam un skeptic Cambridge amico dit me que Occidental es.Li Europan lingues es membres del sam familie. Lor separat existentie es un myth. Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles.</p>\n",
                            'inherited' => false
                        ],
                        [
                            'name'      => 'textcontent_accordioncontent77_3',
                            'type'      => 'wysiwyg',
                            'data'      => "<p>Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum.</p>\n",
                            'inherited' => false
                        ],
                        [
                            'name'      => 'textcontent_accordioncontent77_4',
                            'type'      => 'wysiwyg',
                            'data'      => "<p>It va esser tam simplic quam Occidental in fact, it va esser Occidental. A un Angleso it va semblar un simplificat Angles, quam un skeptic Cambridge amico dit me que Occidental es.Li Europan lingues es membres del sam familie. Lor separat existentie es un myth.</p>\n\n<p>&nbsp;</p>\n\n<p>Por scientie, musica, sport etc, litot Europa usa li sam vocabular. Li lingues differe solmen in li grammatica, li pronunciation e li plu commun vocabules. Omnicos directe al desirabilite de un nov lingua franca: On refusa continuar payar custosi traductores. At solmen va esser necessi far uniform grammatica, pronunciation e plu sommun paroles.</p>\n",
                            'inherited' => false
                        ],
                        [
                            'name'      => 'text_0content10',
                            'type'      => 'textarea',
                            'data'      => 'At solmen va esser necessi far uniform grammatica.',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'text_1content10',
                            'type'      => 'textarea',
                            'data'      => 'Curabitur ullamcorper ultricies nisi. Nam eget dui.',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'text_2content10',
                            'type'      => 'textarea',
                            'data'      => 'On refusa continuar payar custosi traductores.',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'title_0content10',
                            'type'      => 'input',
                            'data'      => 'Social Media Integration',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'title_1content10',
                            'type'      => 'input',
                            'data'      => 'QR-Code Management',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'title_2content10',
                            'type'      => 'input',
                            'data'      => 'Recycle Bin',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'typecontent_blockcontent11_1',
                            'type'      => 'select',
                            'data'      => null,
                            'inherited' => false
                        ],
                        [
                            'name'      => 'typecontent_blockcontent11_2',
                            'type'      => 'select',
                            'data'      => 'video',
                            'inherited' => false
                        ],
                        [
                            'name'      => 'videocontent_blockcontent11_2',
                            'type'      => 'video',
                            'data'      => 'a:5:{s:2:"id";i:27;s:4:"type";s:5:"asset";s:5:"title";s:0:"";s:11:"description";s:0:"";s:6:"poster";i:49;}',
                            'inherited' => false
                        ]
                    ],
                    'mapping'     => [
                        'accordioncontent11'                     => 'content1:1.accordion',
                        'accordioncontent7'                      => 'content:7.accordion',
                        'blockcontent1'                          => 'content:1.block',
                        'gallerycontent2'                        => 'content:2.gallery',
                        'imagescontent11'                        => 'content:11.images',
                        'authorcontent5'                         => 'content:5.author',
                        'contentcontent11'                       => 'content:11.content',
                        'contentcontent3'                        => 'content:3.content',
                        'contentcontent_blockcontent11_1'        => 'content:1.block:1.content',
                        'contentcontent_blockcontent11_2'        => 'content:1.block:2.content',
                        'headlinecontent1_accordioncontent111_1' => 'content1:1.accordion:1.headline',
                        'headlinecontent1_accordioncontent111_2' => 'content1:1.accordion:2.headline',
                        'headlinecontent6'                       => 'content:6.headline',
                        'headlinecontent_accordioncontent77_1'   => 'content:7.accordion:1.headline',
                        'headlinecontent_accordioncontent77_2'   => 'content:7.accordion:2.headline',
                        'headlinecontent_accordioncontent77_3'   => 'content:7.accordion:3.headline',
                        'headlinecontent_accordioncontent77_4'   => 'content:7.accordion:4.headline',
                        'headlinecontent_blockcontent11_1'       => 'content:1.block:1.headline',
                        'headlinecontent_blockcontent11_2'       => 'content:1.block:2.headline',
                        'icon_0content10'                        => 'content:10.icon_0',
                        'icon_1content10'                        => 'content:10.icon_1',
                        'icon_2content10'                        => 'content:10.icon_2',
                        'imagecontent4'                          => 'content:4.image',
                        'imagecontent_blockcontent11_1'          => 'content:1.block:1.image',
                        'imagecontent_gallerycontent22_1'        => 'content:2.gallery:1.image',
                        'imagecontent_gallerycontent22_2'        => 'content:2.gallery:2.image',
                        'imagecontent_gallerycontent22_3'        => 'content:2.gallery:3.image',
                        'imagecontent_gallerycontent22_4'        => 'content:2.gallery:4.image',
                        'imagecontent_imagescontent1111_1'       => 'content:11.images:1.image',
                        'imagecontent_imagescontent1111_2'       => 'content:11.images:2.image',
                        'leadcontent6'                           => 'content:6.lead',
                        'link_0content10'                        => 'content:10.link_0',
                        'link_1content10'                        => 'content:10.link_1',
                        'link_2content10'                        => 'content:10.link_2',
                        'positioncontent_blockcontent11_1'       => 'content:1.block:1.position',
                        'positioncontent_blockcontent11_2'       => 'content:1.block:2.position',
                        'quotecontent5'                          => 'content:5.quote',
                        'sublinecontent_blockcontent11_1'        => 'content:1.block:1.subline',
                        'sublinecontent_blockcontent11_2'        => 'content:1.block:2.subline',
                        'text_0content10'                        => 'content:10.text_0',
                        'text_1content10'                        => 'content:10.text_1',
                        'text_2content10'                        => 'content:10.text_2',
                        'textcontent1_accordioncontent111_1'     => 'content1:1.accordion:1.text',
                        'textcontent1_accordioncontent111_2'     => 'content1:1.accordion:2.text',
                        'textcontent_accordioncontent77_1'       => 'content:7.accordion:1.text',
                        'textcontent_accordioncontent77_2'       => 'content:7.accordion:2.text',
                        'textcontent_accordioncontent77_3'       => 'content:7.accordion:3.text',
                        'textcontent_accordioncontent77_4'       => 'content:7.accordion:4.text',
                        'title_0content10'                       => 'content:10.title_0',
                        'title_1content10'                       => 'content:10.title_1',
                        'title_2content10'                       => 'content:10.title_2',
                        'typecontent_blockcontent11_1'           => 'content:1.block:1.type',
                        'typecontent_blockcontent11_2'           => 'content:1.block:2.type',
                        'videocontent_blockcontent11_2'          => 'content:1.block:2.video'
                    ]
                ]
            ]
        ];
    }
}
