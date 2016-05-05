<?php

namespace AppBundle\Controller;

use AppBundle\Entity\ProvideService;
use AppBundle\Entity\Service;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\FOSRestController;
use AppBundle\Entity\Provider;
use PhoneBundle\PhoneBundle;

class ProvidersController extends FOSRestController{

    /**
     * @Route("/providers")
     * @Method("GET")
     */
    public function getProviders() {

        $resp_code = 404;

        $providers = $this->getDoctrine()
            ->getRepository('AppBundle:Provider')
            ->findAll();

        // FixMe: return Providers Not Found?

        if ($providers) {

            $em = $this->getDoctrine()->getManager();
            foreach ($providers as $provider) {

                $services = $em->createQueryBuilder('c')
                    ->select('s.name')
                    ->from('AppBundle\Entity\Service', 's')
                    ->innerJoin('AppBundle\Entity\ProvideService', 'ps', 'WITH', 'ps.serviceId = s.id')
                    ->where('ps.providerId = :provider_id')
                    ->setParameter('provider_id', $provider->getId())
                    ->getQuery()
                    ->getResult();

                $service_data = array();
                for($i=0; $i < sizeof($services); $i++) {
                    $service_data[] = $services[$i]['name'];
                }


                $data[] = array(
                    'id' => $provider->getId(),
                    'name' => $provider->getName(),
                    'location' => $provider->getLocation(),
                    'phone_number' => $provider->getPhoneNumber(),
                    'provides' => $service_data
                );
            }

            $resp_code = 200;
        }
        return new Response(json_encode($data), $resp_code);
    }

    /**
     * @Route("/providers/{id}")
     * @Method("GET")
     */
    public function getProvider($id) {

        // FixMe: return Provider Not Found?
        $data = array();
        $resp_code = 404;

        $provider = $this->getDoctrine()
            ->getRepository('AppBundle:Provider')
            ->find($id);

        if ($provider) {

            $em = $this->getDoctrine()->getManager();

            $services = $em->createQueryBuilder('c')
                ->select('s.name')
                ->from('AppBundle\Entity\Service', 's')
                ->innerJoin('AppBundle\Entity\ProvideService', 'ps', 'WITH', 'ps.serviceId = s.id')
                ->where('ps.providerId = :provider_id')
                ->setParameter('provider_id', $provider->getId())
                ->getQuery()
                ->getResult();

            $service_data = array();
            for($i=0; $i < sizeof($services); $i++) {
                $service_data[] = $services[$i]['name'];
            }

            $data = array(
                'id' => $provider->getId(),
                'name' => $provider->getName(),
                'location' => $provider->getLocation(),
                'phone_number' => $provider->getPhoneNumber(),
                'provides' => $service_data
            );

            $resp_code = 200;
        }

        return new Response(json_encode($data), $resp_code);
    }

    /**
     * @Route("/providers")
     * @Method("POST")
     */
    public function createProvider(Request $request) {

        $resp_code = 422;
        $data = json_decode($request->getContent(), true);
        // FixMe: dynamic?
        $provider = new Provider();
        $provider->setName($data['name']);
        $provider->setLocation($data['location']);

        if(isset($data['phone_number'])) {
            $helper = new PhoneBundle();
            $provider->setPhoneNumber($helper->formatPhoneNumber($data['phone_number']));
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($provider);
        $em->flush();
        $provider_id = $provider->getId();

        // FixMe: fix query
        if(isset($data["provides"])) {
            $provides = $data['provides'];
            $em = $this->getDoctrine()->getManager();
            $provide_service = new ProvideService();

            #$ps = $em->getRepository('AppBundle:ProviderService');
            for($i=0; $i<sizeof($provides);$i++) {

                $service = $em->getRepository('AppBundle:Service')->findOneByName($provides[$i]);
                $provide_service->setServiceId($service->getId());
                $provide_service->setProviderId($provider_id);
            }
            $em->persist($provide_service);
            $em->flush();

        }

        $resp_code = 201;
        return new Response(json_encode($data), $resp_code);
    }

    /**
     * @Route("/providers/{id}")
     * @Method("PUT")
     */
    public function updateProvider($id, Request $request) {

        $resp_code = 422;
        // FixMe: function to handle phone numbers

        $em = $this->getDoctrine()->getManager();
        $provider = $em->getRepository('AppBundle:Provider')->find($id);
        // FixMe: Do it right way? Dynamic way.
        if ($provider) {

            $data = json_decode($request->getContent(), true);
            if(isset($data['name'])) $provider->setName($data['name']);
            if(isset($data['location'])) $provider->setLocation($data['location']);
            if(isset($data['phone_number'])) $provider->setPhoneNumber($data['phone_number']);
            else if(is_null($data['phone_number'])) $provider->setPhoneNumber(null);
            $em->flush();
            $resp_code = 201;
        }

        return new Response(json_encode($data), $resp_code);
    }


    /**
     * @Route("/providers/{id}")
     * @Method("DELETE")
     */
    public function deleteProvider($id) {

        $resp_code = 404;
        $resp = "Provider not found";

        $em = $this->getDoctrine()->getManager();
        $provider = $em->getRepository('AppBundle:Provider')->find($id);

        if ($provider) {
            $em->remove($provider);
            $em->flush();
            $resp = "Provider deleted";
            $resp_code = 204;
        }

        return new Response(json_encode($resp), $resp_code);
    }
}
?>