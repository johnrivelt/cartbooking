<?php

namespace CartBooking\Application\Provider;

use Bigcommerce\Injector\InjectorServiceProvider;
use CartBooking\Application\Web\BookingController;
use CartBooking\Application\Web\CommunicationController;
use CartBooking\Application\Web\ExperiencesController;
use CartBooking\Application\Web\LocationsController;
use CartBooking\Application\Web\PlacementsController;
use CartBooking\Application\Web\PublishersController;
use CartBooking\Application\Web\ReportsController;
use CartBooking\Application\Web\StatisticsController;
use Pimple\Container;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class ControllerProvider extends InjectorServiceProvider implements ControllerProviderInterface
{

    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $app
     * @return void
     */
    public function register(Container $app)
    {
    }

    /**
     * Returns routes to connect to the given application.
     *
     * @param Application $app An Application instance
     *
     * @return ControllerCollection A ControllerCollection instance
     */
    public function connect(Application $app)
    {
        $controllers = $this->get(ControllerCollection::class);
        $controllers->get('/', function (Application $app) {
            if ($app['security.token_storage']->getToken() === null) {
                return new RedirectResponse('/login');
            }
            return new RedirectResponse('/booking');
        });

        $controllers->get('/booking/', function () {
            return $this->injector->create(BookingController::class)->indexAction();
        });
        $controllers->post('/booking/', function () {
            return $this->injector->create(BookingController::class)->postAction();
        });
        $controllers->post('/placements/', function () {
            return $this->injector->create(PlacementsController::class)->postAction();
        });
        $controllers->get('/placements/{bookingId}', function ($bookingId) {
            return $this->injector->create(PlacementsController::class)->reportAction((int)$bookingId);
        })->assert('bookingId', '\d+');
        $controllers->get('/placements/', function () {
            return $this->injector->create(PlacementsController::class)->indexAction();
        })->bind('/placements');
        $controllers->get('/communication/', function () {
            return $this->injector->create(CommunicationController::class)->indexAction();
        });
        $controllers->post('/communication/', function (Request $request) {
            $controller = $this->injector->create(CommunicationController::class);
            switch ($request->get('action')) {
                case 'placement_reminder':
                    return $controller->sendBookingReminderEmailsAction();
                case 'volunteer_needed':
                    return $controller->sendVolunteerNeededEmailsAction();
                case 'overseer_needed':
                    return $controller->sendOverseerNeededEmailsAction();
            }
        });
        $controllers->get('/experiences/', function () {
            return $this->injector->create(ExperiencesController::class)->indexAction();
        });
        $controllers->post('/experiences/', function (Request $request) {
            return $this->injector->create(ExperiencesController::class)->postAction((int)$request->get('dismissed'));
        });
        $controllers->get('/locations/{locationId}', function ($locationId) {
            return $this->injector->create(
                LocationsController::class,
                ['settings' => $this->get('initParams')]
            )->locationAction($locationId);
        });
        $controllers->get('/locations/', function () {
            return $this->injector->create(
                LocationsController::class,
                ['settings' => $this->get('initParams')]
            )->indexAction();
        })->bind('/locations');
        $controllers->get('/publishers/low-participation', function () {
            return $this->injector->create(PublishersController::class)->lowParticipants();
        });
        $controllers->post('/publishers/search', function (Request $request) {
            return $this->injector->create(PublishersController::class)->searchAction($request->get('name'));
        });
        $controllers->match('/publishers/', function () {
            return $this->injector->create(PublishersController::class)->indexAction();
        });
        $controllers->match('/publishers/{publisherId}', function ($publisherId) {
            return $this->injector->create(PublishersController::class)->editAction($publisherId);
        });
        $controllers->get('/statistics/', function () {
            return $this->injector->create(StatisticsController::class)->indexAction();
        });
        $controllers->get('/reports', function () {
            return $this->injector->create(ReportsController::class)->indexAction();
        });
        $controllers->post('/reports', function (Request $request) {
            if ($request->get('action') === 'List Brothers') {
                return $this->injector->create(ReportsController::class)->listBrothersAction()->send();
            }
            if ($request->get('action') === 'List Invitees') {
                return $this->injector->create(ReportsController::class)->listInviteesAction()->send();
            }
            return new RedirectResponse('/');
        });
        $app->get('/login', function(Request $request) use ($app) {
            return $app['twig']->render('login.twig', [
                'error'         => $app['security.last_error']($request),
                'last_username' => $app['session']->get('_security.last_username'),
                'title' => 'Log in'
            ]);
        });

        return $controllers;
    }
}
