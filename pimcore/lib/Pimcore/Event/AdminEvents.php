<?php

namespace Pimcore\Event;

final class AdminEvents
{
    /**
     * The LOGIN_CREDENTIALS event is triggered after login credentials were resolved from request.
     *
     * This event allows you to influence the credentials resolved in the authenticator before
     * they are passed to getUser().
     *
     * @Event("Pimcore\Event\Admin\Login\LoginCredentialsEvent")
     *
     * @var string
     */
    const LOGIN_CREDENTIALS = 'pimcore.admin.login.credentials';

    /**
     * The LOGIN_FAILED event is triggered when credentials were invalid.
     *
     * This event allows you to set a custom user which is resolved from the given credentials
     * from a third-party authentication system (e.g. an external service).
     *
     * @Event("Pimcore\Event\Admin\Login\LoginFailedEvent")
     *
     * @var string
     */
    const LOGIN_FAILED = 'pimcore.admin.login.failed';

    /**
     * The LOGIN_LOSTPASSWORD event is triggered before the lost password email
     * is sent.
     *
     * This event allows you to alter the lost password mail or to prevent
     * mail sending at all. For full control, it allows you to set the response
     * to be returned.
     *
     * @Event("Pimcore\Event\Admin\Login\LostPasswordEvent")
     *
     * @var string
     */
    const LOGIN_LOSTPASSWORD = 'pimcore.admin.login.lostpassword';

    /**
     * The LOGIN_LOGOUT event is triggered before the user is logged out.
     *
     * By setting a response on the event, you're able to control the response
     * returned after logout.
     *
     * @Event("Pimcore\Event\Admin\Login\LogoutEvent")
     *
     * @var string
     */
    const LOGIN_LOGOUT = 'pimcore.admin.login.logout';

    /**
     * Fired before the request params are parsed.
     *
     * Subject: \Pimcore\Bundle\PimcoreAdminBundle\Controller\Searchadmin\SearchController
     * Arguments:
     *  - requestParams | contains the request parameters
     *
     * @Event("Pimcore\Event\Model\GenericEvent")
     * @var string
     */
    const SEARCH_LIST_BEFORE_FILTER_PREPARE = "pimcore.admin.search.list.beforeFilterPrepare";

    /**
     * Allows you to modify the search backend list before it is loaded.
     *
     * Subject: \Pimcore\Bundle\PimcoreAdminBundle\Controller\Searchadmin\SearchController
     * Arguments:
     *  - list | the search backend list
     *
     * @Event("Pimcore\Event\Model\GenericEvent")
     * @var string
     */
    const SEARCH_LIST_BEFORE_LIST_LOAD = "pimcore.admin.search.list.beforeListLoad";

    /**
     * Allows you to modify the the result after the list was loaded.
     *
     * Subject: \Pimcore\Bundle\PimcoreAdminBundle\Controller\Searchadmin\SearchController
     * Arguments:
     *  - list | raw result as an array
     *
     * @Event("Pimcore\Event\Model\GenericEvent")
     * @var string
     */
    const SEARCH_LIST_AFTER_LIST_LOAD = "pimcore.admin.search.list.afterListLoad";


    /**
     * Fired before the request params are parsed.
     *
     * Subject: \Pimcore\Bundle\PimcoreAdminBundle\Controller\Admin\AssetController
     * Arguments:
     *  - data | array | the response data, this can be modified
     *  - asset | Asset | the current asset
     *
     * @Event("Pimcore\Event\Model\GenericEvent")
     * @var string
     */
    const ASSET_GET_PRE_SEND_DATA = "pimcore.admin.asset.get.preSendData";

    /**
     * Fired before the request params are parsed.
     *
     * Subject: \Pimcore\Bundle\PimcoreAdminBundle\Controller\Admin\ElementControllerBase
     * Arguments:
     *  - data | array | the response data, this can be modified
     *  - document | Document | the current document
     *
     * @Event("Pimcore\Event\Model\GenericEvent")
     * @var string
     */
    const DOCUMENT_GET_PRE_SEND_DATA = "pimcore.admin.document.get.preSendData";

    /**
     * Fired before the request params are parsed.
     *
     * Subject: \Pimcore\Bundle\PimcoreAdminBundle\Controller\Admin\ObjectController
     * Arguments:
     *  - data | array | the response data, this can be modified
     *  - object | AbstractObject | the current object
     *
     * @Event("Pimcore\Event\Model\GenericEvent")
     * @var string
     */
    const OBJECT_GET_PRE_SEND_DATA = "pimcore.admin.object.get.preSendData";

    /**
     * Subject: \Pimcore\Bundle\PimcoreAdminBundle\Controller\Admin\ObjectController
     * Arguments:
     *  - objects | array | the list of object tree nodes
     *
     * @Event("Pimcore\Event\Model\GenericEvent")
     * @var string
     */
    const OBJECT_TREE_GET_CHILDREN_BY_ID_PRE_SEND_DATA = "pimcore.admin.object.treeGetChildsById.preSendData";

    /**
     * Subject: \Pimcore\Bundle\PimcoreAdminBundle\Controller\Admin\ClassController
     * Arguments:
     *  - list | array | the list of object bricks
     *
     * @Event("Pimcore\Event\Model\GenericEvent")
     * @var string
     */
    const CLASS_OBJECTBRICK_LIST_PRE_SEND_DATA = "pimcore.admin.class.objectbrickList.preSendData";


}
