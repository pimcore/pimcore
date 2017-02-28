<?php

namespace WebsiteDemoBundle\Controller\Category;

use WebsiteDemoBundle\Controller\AbstractController;

class ExampleController extends AbstractController
{
    public function testAction()
    {

        /*
         * This is an example of a categorization of controllers
         * you can create folders to structure your controllers into sub-modules
         *
         * The controller name is then the name of the folder and the controller, separated by an underscore (_)
         * in this case this is "category_example"
         *
         * For this example there's a static route and a document defined
         * Name of static route: "category-example"
         * Path of document: /en/advanced-examples/sub-modules
         */

//        $this->enableLayout();

        // this is needed so that the layout can be rendered
//        $this->setDocument(\Pimcore\Model\Document::getById(1));
    }
}
