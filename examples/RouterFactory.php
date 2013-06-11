<?php

namespace h4kuna; //remove

use Nette\Application\Routers\RouteList,
    Nette\Application\Routers\Route;

/**
 * Router factory.
 */
class RouterFactory {

    /**
     * @return Nette\Application\IRouter
     */
    public function createRouter(\h4kuna\GettextLatte $translator) {
        $router = new RouteList();
        $router[] = new Route('index.php', 'Homepage:default', Route::ONE_WAY);
        $router[] = new Route('[<lang ' . $translator->routerAccept() . '>/]<presenter>/<action>/[<id>/]', array(
            'presenter' => 'Homepage',
            'action' => 'default',
            'lang' => $translator->getDefault()
        ));

        return $router;
    }

}
